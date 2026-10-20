import { Component, inject, OnInit, signal } from '@angular/core';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { Auth } from '../core/auth';
import { Envelope, Purchase, Payment } from '../core/models';
import { Feedback } from '../shared/feedback';
import { localDate } from '../shared/format';
@Component({
  imports: [CurrencyPipe, DatePipe, RouterLink, ReactiveFormsModule, Feedback],
  template: `<a routerLink="/compras" class="back-link">← Voltar às compras</a><app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    @if (purchase(); as p) {
      <div class="page-heading"><div><p class="eyebrow">DETALHES DA COMPRA</p><h1>Compra #{{ p.id }}</h1><p class="muted">{{ p.fornecedor?.nome ?? 'Fornecedor removido' }} · {{ p.data | date:'dd/MM/yyyy':'UTC' }}</p></div>
      <span class="badge large" [attr.data-status]="p.status">{{ p.status }}</span></div>
      @if (p.quitada_no_legado) {<div class="alert" role="status">Esta compra foi quitada no sistema anterior, sem pagamentos detalhados. O registro foi preservado para conciliação.</div>}
      <section class="metrics three"><article><span>Valor da compra</span><strong>{{ p.valor | currency:'BRL' }}</strong></article><article><span>Total pago</span><strong>{{ p.pago | currency:'BRL' }}</strong></article>
      <article><span>Saldo a pagar</span><strong>{{ p.saldo | currency:'BRL' }}</strong><small>Vencimento: {{ p.vencimento ? (p.vencimento | date:'dd/MM/yyyy':'UTC') : 'Não informado' }}</small></article></section>
      @if (p.descricao) {<p class="description">{{ p.descricao }}</p>}
      @if (auth.canEdit() && p.status === 'pendente') {<section class="panel form-panel"><h2>Registrar pagamento</h2>
        <form class="payment-form" [formGroup]="form" (ngSubmit)="pay()"><label>Valor (R$) *<input type="number" formControlName="valor" min="0.01" [max]="p.saldo" step="0.01" required></label>
          <label>Data do pagamento *<input type="date" formControlName="data" [min]="p.data" [max]="today" required></label><button class="primary" [disabled]="saving()">Registrar pagamento</button></form></section>}
      <section class="panel"><div class="section-heading"><h2>Histórico de pagamentos</h2><span class="tag">{{ p.pagamentos.length }} registro(s)</span></div>
      <div class="table-wrap"><table><thead><tr><th>Pagamento</th><th>Data</th><th class="numeric">Valor</th><th>Situação</th><th>Motivo de estorno</th><th>Ações</th></tr></thead><tbody>
        @for (payment of p.pagamentos; track payment.id) {<tr><td>#{{ payment.id }}</td><td>{{ payment.data | date:'dd/MM/yyyy':'UTC' }}</td><td class="numeric">{{ payment.valor | currency:'BRL' }}</td>
          <td><span class="badge" [attr.data-status]="payment.estornado_em ? 'cancelado' : 'pago'">{{ payment.estornado_em ? 'Estornado' : 'Confirmado' }}</span></td>
          <td>{{ payment.motivo_estorno ?? '—' }}</td><td>@if (auth.admin() && !payment.estornado_em) {<button class="link-button danger-text" (click)="reverse(payment)" [disabled]="saving()">Estornar</button>}</td></tr>}
        @empty {<tr><td colspan="6" class="empty">Nenhum pagamento detalhado registrado.</td></tr>}</tbody></table></div></section>
    }`,
})
export class PurchaseDetail implements OnInit {
  private api = inject(Api); auth = inject(Auth); private route = inject(ActivatedRoute);
  purchase = signal<Purchase | null>(null); loading = signal(false); saving = signal(false); error = signal(''); success = signal('');
  today = localDate(); form = inject(FormBuilder).nonNullable.group({valor: ['', [Validators.required, Validators.min(0.01)]], data: [this.today, Validators.required]});
  ngOnInit(): void { void this.load(); }
  async load(): Promise<void> { this.loading.set(true); try { this.purchase.set((await this.api.get<Envelope<Purchase>>('compras/' + this.route.snapshot.paramMap.get('id'))).data); } catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); } }
  async pay(): Promise<void> {
    if (this.form.invalid || this.saving()) { this.error.set('Informe valor e data válidos.'); return; }
    this.saving.set(true); this.error.set(''); this.success.set('');
    try { await this.api.post(`compras/${this.purchase()!.id}/pagamentos`, this.form.getRawValue()); this.form.reset({valor: '', data: this.today}); await this.load(); this.success.set('Pagamento registrado.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
  async reverse(payment: Payment): Promise<void> {
    const reason = prompt(`Informe o motivo do estorno do pagamento #${payment.id} (mínimo 5 caracteres):`);
    if (reason === null) { return; }
    this.saving.set(true); this.error.set(''); this.success.set('');
    try { await this.api.post(`pagamentos/${payment.id}/estorno`, {motivo: reason}); await this.load(); this.success.set('Pagamento estornado. O saldo da compra foi atualizado.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
}
