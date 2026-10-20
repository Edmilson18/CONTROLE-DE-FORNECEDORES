import { Component, inject, OnInit, signal } from '@angular/core';
import { Api, errorMessage } from '../core/api';
import { AccountAccessRequest, AccessRequestStatus } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';

@Component({
  imports: [Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">ADMINISTRAÇÃO</p><h1>Solicitações de acesso</h1>
      <p class="muted">Leia os pedidos recebidos e decida se a conta pode ser criada.</p></div>
      <label class="filter-field">Mostrar<select [value]="filter()" (change)="filterChanged($event)">
        <option value="pending">Pendentes</option><option value="approved">Aprovadas</option><option value="rejected">Recusadas</option><option value="completed">Conta ativada</option>
      </select></label></div>
    <app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    <section class="grid gap-4">
      @for (row of rows(); track row.id) {
        <article class="panel p-6">
          <header class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="eyebrow">{{ date(row.created_at) }}</p><h2>{{ row.name }}</h2>
              <a class="text-link" [href]="'mailto:' + row.email">{{ row.email }}</a></div>
            <span class="badge" [attr.data-status]="row.status === 'pending' ? 'pendente' : row.status === 'approved' || row.status === 'completed' ? 'pago' : 'cancelado'">{{ statusLabel(row.status) }}</span>
          </header>
          <div class="request-message mt-5"><p class="eyebrow">MENSAGEM DO SOLICITANTE</p><p class="whitespace-pre-wrap">{{ row.message }}</p></div>
          @if (row.response_message) {<div class="mt-4"><p class="eyebrow">RESPOSTA ENVIADA</p><p>{{ row.response_message }}</p></div>}
          @if (row.status === 'pending') {
            <div class="mt-5 border-t border-slate-200 pt-5"><label>Motivo da recusa (opcional)<textarea #reason rows="2" maxlength="1000" placeholder="Inclua uma orientação para a pessoa, se desejar."></textarea></label>
              <div class="form-actions"><button class="secondary" [disabled]="working() === row.id" (click)="decide(row, 'rejected', reason.value)">Recusar e enviar e-mail</button>
                <button class="primary" [disabled]="working() === row.id" (click)="decide(row, 'approved')">{{ working() === row.id ? 'Enviando…' : 'Aprovar e enviar link' }}</button></div>
            </div>
          } @else {
            @if (row.reviewer) {<p class="muted mt-4 text-sm">Analisada por {{ row.reviewer.name }}.</p>}
            @if (row.status === 'approved') {<div class="form-actions"><button class="secondary" [disabled]="working() === row.id" (click)="resend(row)">{{ working() === row.id ? 'Enviando…' : 'Reenviar link de ativação' }}</button></div>}
          }
        </article>
      } @empty {<div class="panel empty">Nenhuma solicitação nesta situação.</div>}
    </section>
    <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" />
    <p class="muted mt-4 text-sm">Contas aprovadas recebem perfil de operador, que permite cadastrar fornecedores e compras. Você pode alterar o perfil depois em Usuários.</p>`,
})
export class AccessRequests implements OnInit {
  private api = inject(Api);
  rows = signal<AccountAccessRequest[]>([]); filter = signal<AccessRequestStatus>('pending');
  page = signal(1); last = signal(1); total = signal(0); loading = signal(false);
  working = signal<number | null>(null); error = signal(''); success = signal('');

  ngOnInit(): void { void this.load(); }

  filterChanged(event: Event): void {
    this.filter.set((event.target as HTMLSelectElement).value as AccessRequestStatus);
    void this.load();
  }

  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try {
      const result = await this.api.list<AccountAccessRequest>('solicitacoes-acesso', {status: this.filter(), page, per_page: 15});
      this.rows.set(result.data); this.page.set(result.meta?.current_page ?? result.current_page ?? 1);
      this.last.set(result.meta?.last_page ?? result.last_page ?? 1); this.total.set(result.meta?.total ?? result.total ?? result.data.length);
    } catch (error) { this.error.set(errorMessage(error)); }
    finally { this.loading.set(false); }
  }

  statusLabel(status: AccessRequestStatus): string {
    return {pending: 'Pendente', approved: 'Aprovada · aguardando senha', rejected: 'Recusada', completed: 'Conta ativada'}[status];
  }

  date(value: string): string { return new Date(value).toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short', timeZone: 'America/Sao_Paulo'}); }

  async decide(row: AccountAccessRequest, decision: 'approved' | 'rejected', response_message = ''): Promise<void> {
    this.working.set(row.id); this.error.set(''); this.success.set('');
    try {
      await this.api.patch(`solicitacoes-acesso/${row.id}`, {decision, response_message: response_message || null});
      this.success.set(decision === 'approved' ? 'Solicitação aprovada. O link para criar a senha foi enviado por e-mail.' : 'Solicitação recusada. A resposta foi enviada por e-mail.');
      await this.load(this.page());
    } catch (error) { this.error.set(errorMessage(error)); }
    finally { this.working.set(null); }
  }

  async resend(row: AccountAccessRequest): Promise<void> {
    this.working.set(row.id); this.error.set(''); this.success.set('');
    try {
      await this.api.post(`solicitacoes-acesso/${row.id}/reenviar`);
      this.success.set('Novo link de ativação enviado por e-mail.');
    } catch (error) { this.error.set(errorMessage(error)); }
    finally { this.working.set(null); }
  }
}
