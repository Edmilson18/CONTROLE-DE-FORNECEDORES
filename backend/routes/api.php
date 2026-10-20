<?php

use App\Http\Controllers\AccountAccessController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('solicitacoes-acesso', [AccountAccessController::class, 'store'])->middleware('throttle:3,10');
Route::post('acessos/ativar', [AccountAccessController::class, 'activate'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'active', 'throttle:120,1'])->group(function (): void {
    Route::get('auth/me', fn (Request $request) => ['data' => $request->user()]);
    Route::get('dashboard', DashboardController::class);
    Route::apiResource('fornecedores', FornecedorController::class)->parameters(['fornecedores' => 'fornecedor']);
    Route::apiResource('compras', CompraController::class)->parameters(['compras' => 'compra']);
    Route::post('compras/{compra}/pagamentos', [CompraController::class, 'pay']);
    Route::post('pagamentos/{pagamento}/estorno', [CompraController::class, 'reverse']);
    Route::get('relatorios', [ReportController::class, 'index']);
    Route::get('relatorios/exportar', [ReportController::class, 'export'])->middleware('throttle:10,1');
    Route::get('notificacoes', fn (Request $request) => $request->user()->notifications()->paginate(20));
    Route::get('notificacoes/contagem', fn (Request $request) => ['data' => ['nao_lidas' => $request->user()->unreadNotifications()->count()]]);
    Route::patch('notificacoes/{id}/ler', function (Request $request, string $id) {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->noContent();
    });
    Route::middleware('can:admin')->group(function (): void {
        Route::get('solicitacoes-acesso', [AccountAccessController::class, 'index']);
        Route::patch('solicitacoes-acesso/{accountAccessRequest}', [AccountAccessController::class, 'decide']);
        Route::post('solicitacoes-acesso/{accountAccessRequest}/reenviar', [AccountAccessController::class, 'resend']);
        Route::apiResource('usuarios', UserController::class)->only(['index', 'store', 'update'])->parameters(['usuarios' => 'user']);
        Route::get('auditoria', fn () => AuditLog::with('user:id,name')->latest('id')->paginate(25));
    });
});
