<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Fornecedor;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'admin'): User
    {
        $user = User::factory()->create(['role' => $role, 'ativo' => true]);
        $this->actingAs($user);

        return $user;
    }

    private function supplier(): Fornecedor
    {
        return Fornecedor::create(['nome' => 'Fornecedor de teste', 'cnpj' => '11.222.333/0001-81', 'telefone' => '(11) 99999-1234', 'cidade' => 'São Paulo']);
    }

    private function purchase(array $attributes = []): Compra
    {
        return Compra::create([...['fornecedores_id' => $this->supplier()->id, 'data' => today()->subDays(10), 'valor' => '100.00', 'status' => 'pendente', 'vencimento' => today()->subDay()], ...$attributes]);
    }

    public function test_guests_cannot_access_business_data(): void
    {
        $this->getJson('/api/fornecedores')->assertUnauthorized();
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_login_logout_and_disabled_accounts(): void
    {
        User::factory()->create(['email' => 'admin@example.test', 'password' => 'SenhaForte12345']);
        $this->postJson('/api/auth/login', ['email' => 'admin@example.test', 'password' => 'incorreta'])->assertUnprocessable();
        $this->postJson('/api/auth/login', ['email' => 'admin@example.test', 'password' => 'SenhaForte12345'])->assertOk();
        $this->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/logout')->assertOk();
        User::where('email', 'admin@example.test')->update(['ativo' => false]);
        $this->postJson('/api/auth/login', ['email' => 'admin@example.test', 'password' => 'SenhaForte12345'])->assertUnprocessable();
    }

    public function test_supplier_normalizes_masks_and_audits(): void
    {
        $this->actor('operador');
        $this->postJson('/api/fornecedores', ['nome' => 'Empresa', 'cnpj' => '11222333000181', 'telefone' => '11999991234', 'cidade' => 'São Paulo'])
            ->assertCreated()->assertJsonPath('data.cnpj', '11.222.333/0001-81')->assertJsonPath('data.telefone', '(11) 99999-1234');
        $this->assertDatabaseHas('fornecedores', ['cnpj' => '11.222.333/0001-81']);
        $this->assertDatabaseHas('audit_logs', ['acao' => 'criar', 'entidade' => 'fornecedores']);
    }

    public function test_invalid_cnpj_phone_and_duplicate_are_rejected(): void
    {
        $this->actor();
        $data = ['nome' => 'Empresa', 'cnpj' => '00000000000000', 'telefone' => '123', 'cidade' => 'São Paulo'];
        $this->postJson('/api/fornecedores', $data)->assertUnprocessable()->assertJsonValidationErrors(['cnpj', 'telefone']);
        $this->supplier();
        $this->postJson('/api/fornecedores', [...$data, 'cnpj' => '11222333000181', 'telefone' => ''])->assertUnprocessable()->assertJsonValidationErrors('cnpj');
    }

    public function test_alphanumeric_cnpj_is_accepted(): void
    {
        $this->actor();
        $this->postJson('/api/fornecedores', ['nome' => 'Empresa', 'cnpj' => '12abc34501de35', 'telefone' => '', 'cidade' => 'Recife'])
            ->assertCreated()->assertJsonPath('data.cnpj', '12.ABC.345/01DE-35');
    }

    public function test_read_only_role_and_admin_routes(): void
    {
        $this->actor('consulta');
        $this->getJson('/api/fornecedores')->assertOk();
        $this->postJson('/api/fornecedores', [])->assertForbidden();
        $this->getJson('/api/usuarios')->assertForbidden();
        $this->getJson('/api/auditoria')->assertForbidden();
    }

    public function test_disabled_session_is_denied(): void
    {
        $user = $this->actor();
        $user->update(['ativo' => false]);
        $this->getJson('/api/compras')->assertForbidden();
    }

    public function test_payments_update_balance_and_prevent_overpayment(): void
    {
        $this->actor('operador');
        $purchase = $this->purchase();
        $url = "/api/compras/{$purchase->id}/pagamentos";
        $this->postJson($url, ['valor' => '40.01', 'data' => today()->toDateString()])->assertCreated();
        $this->getJson("/api/compras/{$purchase->id}")->assertOk()->assertJsonPath('data.saldo', '59.99');
        $this->postJson($url, ['valor' => '60.00', 'data' => today()->toDateString()])->assertUnprocessable();
        $this->postJson($url, ['valor' => '59.99', 'data' => today()->toDateString()])->assertCreated();
        $this->getJson("/api/compras/{$purchase->id}")->assertJsonPath('data.status', 'pago')->assertJsonPath('data.saldo', '0.00');
        $this->postJson($url, ['valor' => '0.01', 'data' => today()->toDateString()])->assertUnprocessable();
    }

    public function test_payment_dates_and_amount_precision(): void
    {
        $this->actor();
        $purchase = $this->purchase();
        $this->postJson("/api/compras/{$purchase->id}/pagamentos", ['valor' => '0', 'data' => today()->addDay()->toDateString()])->assertUnprocessable();
        $this->postJson("/api/compras/{$purchase->id}/pagamentos", ['valor' => '1.001', 'data' => today()->toDateString()])->assertUnprocessable();
        $this->postJson("/api/compras/{$purchase->id}/pagamentos", ['valor' => '1', 'data' => today()->subYear()->toDateString()])->assertUnprocessable();
    }

    public function test_reversal_reopens_purchase_and_preserves_payment(): void
    {
        $this->actor();
        $purchase = $this->purchase();
        $this->postJson("/api/compras/{$purchase->id}/pagamentos", ['valor' => '100', 'data' => today()->toDateString()])->assertCreated();
        $payment = Pagamento::first();
        $this->postJson("/api/pagamentos/{$payment->id}/estorno", ['motivo' => 'Pagamento incorreto'])->assertNoContent();
        $this->getJson("/api/compras/{$purchase->id}")->assertJsonPath('data.saldo', '100.00')->assertJsonPath('data.status', 'pendente');
        $this->assertDatabaseHas('pagamentos', ['id' => $payment->id, 'motivo_estorno' => 'Pagamento incorreto']);
        $this->postJson("/api/pagamentos/{$payment->id}/estorno", ['motivo' => 'Repetido'])->assertUnprocessable();
        $this->deleteJson("/api/compras/{$purchase->id}")->assertUnprocessable();
    }

    public function test_operator_cannot_reverse_or_delete(): void
    {
        $this->actor('operador');
        $purchase = $this->purchase();
        $payment = $purchase->pagamentos()->create(['valor' => '10', 'data' => today()]);
        $this->postJson("/api/pagamentos/{$payment->id}/estorno", ['motivo' => 'Teste de bloqueio'])->assertForbidden();
        $this->deleteJson("/api/compras/{$purchase->id}")->assertForbidden();
    }

    public function test_cancel_and_reduce_paid_purchase_are_rejected(): void
    {
        $this->actor();
        $purchase = $this->purchase();
        $purchase->pagamentos()->create(['valor' => '40', 'data' => today()]);
        $payload = ['fornecedores_id' => $purchase->fornecedores_id, 'data' => $purchase->data->toDateString(), 'valor' => '100', 'status' => 'cancelado'];
        $this->putJson("/api/compras/{$purchase->id}", $payload)->assertUnprocessable();
        $this->putJson("/api/compras/{$purchase->id}", [...$payload, 'status' => 'pendente', 'valor' => '30'])->assertUnprocessable();
    }

    public function test_paid_legacy_purchase_is_preserved(): void
    {
        $this->actor();
        $purchase = $this->purchase(['status' => 'pago']);
        $this->getJson("/api/compras/{$purchase->id}")->assertJsonPath('data.quitada_no_legado', true)->assertJsonPath('data.saldo', '0.00');
        $this->postJson("/api/compras/{$purchase->id}/pagamentos", ['valor' => '10', 'data' => today()->toDateString()])->assertUnprocessable();
        $this->deleteJson("/api/compras/{$purchase->id}")->assertUnprocessable();
    }

    public function test_archived_supplier_remains_in_purchase_history(): void
    {
        $this->actor();
        $purchase = $this->purchase();
        $this->deleteJson("/api/fornecedores/{$purchase->fornecedores_id}")->assertNoContent();
        $this->getJson('/api/fornecedores')->assertJsonCount(0, 'data');
        $this->getJson("/api/compras/{$purchase->id}")->assertJsonPath('data.fornecedor.nome', 'Fornecedor de teste');
    }

    public function test_filters_pagination_dashboard_and_reports(): void
    {
        $this->actor();
        $this->purchase();
        $this->purchase(['status' => 'cancelado']);
        $this->getJson('/api/compras?status=pendente&per_page=1')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/compras?per_page=1000')->assertUnprocessable();
        $this->getJson('/api/dashboard')->assertJsonPath('data.saldo_pendente', '100.00')->assertJsonPath('data.vencidas', 1);
        $this->getJson('/api/relatorios')->assertJsonPath('data.total', '100.00');
        $this->get('/api/relatorios/exportar?formato=pdf', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/api/relatorios/exportar?formato=csv')->assertOk()->assertDownload('relatorio-compras.csv');
    }

    public function test_notifications_are_deduplicated_and_private(): void
    {
        $user = $this->actor();
        $this->purchase();
        $this->artisan('pagamentos:notificar')->assertSuccessful();
        $this->artisan('pagamentos:notificar')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
        $notice = $user->notifications()->first();
        $this->actor('consulta');
        $this->patchJson("/api/notificacoes/{$notice->id}/ler")->assertNotFound();
        $this->actingAs($user)->patchJson("/api/notificacoes/{$notice->id}/ler")->assertNoContent();
    }

    public function test_admin_management_does_not_expose_password(): void
    {
        $user = $this->actor();
        $data = ['name' => 'Operador', 'email' => 'operador@example.test', 'password' => 'SenhaForte12345', 'role' => 'operador', 'ativo' => true];
        $this->postJson('/api/usuarios', $data)->assertCreated()->assertJsonMissingPath('data.password');
        $this->putJson("/api/usuarios/{$user->id}", [...$data, 'email' => $user->email, 'ativo' => false])->assertUnprocessable();
        $this->assertDatabaseMissing('audit_logs', ['depois' => json_encode(['password' => 'SenhaForte12345'])]);
    }
}
