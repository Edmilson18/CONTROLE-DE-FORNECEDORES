import { Component, inject, OnInit, signal } from '@angular/core';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { Auth } from '../core/auth';
import { Purchase, Supplier, Filters } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';
import { localDate } from '../shared/format';
@Component({
  imports: [CurrencyPipe, DatePipe, RouterLink, ReactiveFormsModule, Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">CONTROLE FINANCEIRO</p><h1>Compras e pagamentos</h1><p class="muted">Acompanhe valores, vencimentos e o saldo de cada compra.</p></div>
    @if (auth.canEdit()) {<button class="primary" (click)="edit()">+ Nova compra</button>}</div>
    <app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    @if (editing()) {<section class="panel form-panel"><div class="section-heading"><h2>{{ id() ? 'Editar compra' : 'Nova compra' }}</h2><button class="link-button" (click)="editing.set(false)">Fechar</button></div>
      <form [formGroup]="form" (ngSubmit)="save()"><div class="form-grid">
        <label class="full-width">Buscar fornecedor<input #supplierSearch placeholder="Digite parte do nome e pressione Buscar" (keydown.enter)="$event.preventDefault(); findSuppliers(supplierSearch.value)"><button type="button" class="secondary" (click)="findSuppliers(supplierSearch.value)">Buscar fornecedor</button></label>
        <label>Fornecedor *<select formControlName="fornecedores_id" required><option value="">Selecione</option>@for (supplier of suppliers(); track supplier.id) {<option [value]="supplier.id">{{ supplier.nome }} · {{ supplier.cnpj }}</option>}</select><small>Exibe até 100 resultados. Refine a busca quando necessário.</small></label>
        <label>Valor (R$) *<input type="number" formControlName="valor" min="0.01" max="9999999999.99" step="0.01" required></label>
        <label>Data da compra *<input type="date" formControlName="data" required></label>
        <label>Vencimento<input type="date" formControlName="vencimento"></label>
        <label>Status<select formControlName="status"><option value="pendente">Pendente</option><option value="cancelado">Cancelado</option></select><small>A quitação é calculada pelos pagamentos.</small></label>
        <label>Descrição<input formControlName="descricao" maxlength="500"></label>
      </div><div class="form-actions"><button type="button" class="secondary" (click)="editing.set(false)">Cancelar</button><button class="primary" [disabled]="saving()">Salvar compra</button></div></form></section>}
    <section class="panel"><form class="toolbar filter-toolbar" [formGroup]="filter" (ngSubmit)="load(1)">
      <label class="grow">Buscar<input formControlName="q" placeholder="Fornecedor ou descrição"></label>
      <label>Status<select formControlName="status"><option value="">Todos</option><option value="pendente">Pendente</option><option value="pago">Pago</option><option value="cancelado">Cancelado</option></select></label>
      <label>De<input type="date" formControlName="de"></label><label>Até<input type="date" formControlName="ate"></label><button class="secondary">Filtrar</button>
    </form><div class="table-wrap"><table><thead><tr><th>Compra / fornecedor</th><th>Data</th><th>Vencimento</th><th>Status</th><th class="numeric">Valor</th><th class="numeric">Saldo</th><th>Ações</th></tr></thead>
      <tbody>@for (row of rows(); track row.id) {<tr><td><a [routerLink]="['/compras', row.id]">#{{ row.id }} · {{ row.fornecedor?.nome ?? 'Fornecedor removido' }}</a><small class="block muted">{{ row.descricao }}</small></td>
        <td>{{ row.data | date:'dd/MM/yyyy':'UTC' }}</td><td>{{ row.vencimento ? (row.vencimento | date:'dd/MM/yyyy':'UTC') : '—' }}</td><td><span class="badge" [attr.data-status]="row.status">{{ row.status }}</span></td>
        <td class="numeric">{{ row.valor | currency:'BRL' }}</td><td class="numeric">{{ row.saldo | currency:'BRL' }}</td><td class="row-actions"><a [routerLink]="['/compras', row.id]">Detalhes</a>
          @if (auth.canEdit() && !row.quitada_no_legado) {<button class="link-button" (click)="edit(row)">Editar</button>}
          @if (auth.admin() && !row.pagamentos.length && row.status !== 'pago') {<button class="link-button danger-text" (click)="remove(row)">Excluir</button>}</td></tr>}
      @empty {<tr><td colspan="7" class="empty">Nenhuma compra encontrada para estes filtros.</td></tr>}</tbody></table></div>
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" />
    </section>`,
})
export class Purchases implements OnInit {
  private api = inject(Api); auth = inject(Auth); private fb = inject(FormBuilder);
  rows = signal<Purchase[]>([]); suppliers = signal<Supplier[]>([]); loading = signal(false); saving = signal(false);
  error = signal(''); success = signal(''); editing = signal(false); id = signal<number | undefined>(undefined);
  page = signal(1); last = signal(1); total = signal(0);
  filter = this.fb.nonNullable.group({q: '', status: '', de: '', ate: ''});
  form = this.fb.nonNullable.group({fornecedores_id: ['', Validators.required], valor: ['', [Validators.required, Validators.min(0.01)]],
    data: [localDate(), Validators.required], vencimento: '', status: 'pendente', descricao: ''});
  ngOnInit(): void { void this.load(); }
  filters(): Filters { return Object.fromEntries(Object.entries(this.filter.getRawValue()).filter(([, v]) => v !== '')); }
  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try { const result = await this.api.list<Purchase>('compras', {...this.filters(), page}); this.rows.set(result.data); this.page.set(result.meta!.current_page); this.last.set(result.meta!.last_page); this.total.set(result.meta!.total); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
  async findSuppliers(q = ''): Promise<void> {
    try { this.suppliers.set((await this.api.list<Supplier>('fornecedores', {q, per_page: 100})).data); } catch (e) { this.error.set(errorMessage(e)); }
  }
  edit(row?: Purchase): void {
    this.id.set(row?.id); this.error.set(''); this.success.set('');
    this.form.reset(row ? {fornecedores_id: String(row.fornecedores_id ?? ''), valor: row.valor, data: row.data, vencimento: row.vencimento ?? '', status: row.status === 'cancelado' ? 'cancelado' : 'pendente', descricao: row.descricao ?? ''}
      : {fornecedores_id: '', valor: '', data: localDate(), vencimento: '', status: 'pendente', descricao: ''});
    this.editing.set(true); void this.findSuppliers(row?.fornecedor?.nome ?? '');
  }
  async save(): Promise<void> {
    if (this.form.invalid) { this.form.markAllAsTouched(); this.error.set('Informe fornecedor, data e um valor maior que zero.'); return; }
    this.saving.set(true); this.error.set('');
    try { await this.api.save<Purchase>('compras', this.form.getRawValue(), this.id()); this.editing.set(false); await this.load(); this.success.set('Compra salva. Registre os pagamentos nos detalhes da compra.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
  async remove(row: Purchase): Promise<void> {
    if (!confirm(`Excluir a compra #${row.id}? A operação ficará registrada na auditoria.`)) { return; }
    try { await this.api.remove('compras/' + row.id); await this.load(); this.success.set('Compra excluída.'); } catch (e) { this.error.set(errorMessage(e)); }
  }
}
