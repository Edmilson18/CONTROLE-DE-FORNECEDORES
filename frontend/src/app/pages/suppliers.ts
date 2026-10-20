import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { Auth } from '../core/auth';
import { Supplier } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';
import { Mask } from '../shared/mask';
import { cnpjValidator } from '../shared/format';
@Component({
  imports: [ReactiveFormsModule, Feedback, Pagination, Mask],
  template: `<div class="page-heading"><div><p class="eyebrow">REDE DE PARCEIROS</p><h1>Fornecedores</h1><p class="muted">Cadastros organizados para facilitar cada compra.</p></div>
    @if (auth.canEdit()) {<button class="primary" (click)="edit()">+ Novo fornecedor</button>}</div>
    <app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    @if (editing()) {<section class="panel form-panel"><div class="section-heading"><h2>{{ id() ? 'Editar fornecedor' : 'Novo fornecedor' }}</h2><button class="link-button" (click)="editing.set(false)">Fechar</button></div>
      <form [formGroup]="form" (ngSubmit)="save()"><div class="form-grid">
        <label>Nome / razão social *<input formControlName="nome" maxlength="200" autocomplete="organization" required></label>
        <label>CNPJ *<input formControlName="cnpj" appMask="cnpj" maxlength="18" placeholder="00.000.000/0000-00" required><small>Digite sem pontuação. CNPJ alfanumérico também é aceito.</small></label>
        <label>Telefone<input formControlName="telefone" appMask="phone" maxlength="15" inputmode="tel" autocomplete="tel-national" placeholder="(00) 00000-0000"></label>
        <label>Cidade *<input formControlName="cidade" maxlength="200" autocomplete="address-level2" required></label>
      </div><div class="form-actions"><button type="button" class="secondary" (click)="editing.set(false)">Cancelar</button><button class="primary" [disabled]="saving()">{{ saving() ? 'Salvando…' : 'Salvar fornecedor' }}</button></div></form>
    </section>}
    <section class="panel"><form class="toolbar" (submit)="$event.preventDefault(); load(1)"><label class="search-field"><span class="sr-only">Buscar fornecedor</span><input #search placeholder="Buscar por nome, CNPJ ou cidade" [value]="query" (input)="query = search.value"></label><button class="secondary">Buscar</button></form>
      <div class="table-wrap"><table><thead><tr><th>Fornecedor</th><th>CNPJ</th><th>Telefone</th><th>Cidade</th><th class="numeric">Ações</th></tr></thead><tbody>
      @for (row of rows(); track row.id) {<tr><td class="font-semibold">{{ row.nome }}</td><td class="tabular">{{ row.cnpj }}</td><td class="tabular">{{ row.telefone || '—' }}</td><td>{{ row.cidade }}</td><td class="row-actions">
        @if (auth.canEdit()) {<button class="link-button" (click)="edit(row)" [attr.aria-label]="'Editar ' + row.nome">Editar</button>}
        @if (auth.admin()) {<button class="link-button danger-text" (click)="remove(row)" [attr.aria-label]="'Arquivar ' + row.nome">Arquivar</button>}</td></tr>}
      @empty {<tr><td colspan="5" class="empty">Nenhum fornecedor encontrado.</td></tr>}</tbody></table></div>
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" />
    </section>`,
})
export class Suppliers implements OnInit {
  private api = inject(Api); auth = inject(Auth);
  rows = signal<Supplier[]>([]); loading = signal(false); saving = signal(false); error = signal(''); success = signal('');
  editing = signal(false); id = signal<number | undefined>(undefined); page = signal(1); last = signal(1); total = signal(0); query = '';
  form = inject(FormBuilder).nonNullable.group({
    nome: ['', [Validators.required, Validators.maxLength(200)]], cnpj: ['', [Validators.required, cnpjValidator]],
    telefone: ['', Validators.pattern(/^\([1-9][0-9]\) \d{4,5}-\d{4}$/)], cidade: ['', [Validators.required, Validators.maxLength(200)]],
  });
  ngOnInit(): void { void this.load(); }
  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try { const result = await this.api.list<Supplier>('fornecedores', {q: this.query, page}); this.rows.set(result.data); this.page.set(result.meta!.current_page); this.last.set(result.meta!.last_page); this.total.set(result.meta!.total); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
  edit(row?: Supplier): void { this.id.set(row?.id); this.form.reset(row ?? {nome: '', cnpj: '', telefone: '', cidade: ''}); this.editing.set(true); this.error.set(''); this.success.set(''); }
  async save(): Promise<void> {
    if (this.form.invalid) { this.form.markAllAsTouched(); this.error.set('Confira os campos obrigatórios, o CNPJ e o telefone com DDD.'); return; }
    this.saving.set(true); this.error.set('');
    try { await this.api.save<Supplier>('fornecedores', this.form.getRawValue(), this.id()); this.editing.set(false); await this.load(); this.success.set('Fornecedor salvo.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
  async remove(row: Supplier): Promise<void> {
    if (!confirm(`Arquivar ${row.nome}? O histórico de compras será preservado.`)) { return; }
    try { await this.api.remove('fornecedores/' + row.id); await this.load(); this.success.set('Fornecedor arquivado.'); } catch (e) { this.error.set(errorMessage(e)); }
  }
}
