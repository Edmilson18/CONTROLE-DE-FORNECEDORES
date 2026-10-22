import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { Auth } from '../core/auth';
import { User, Role } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';

@Component({
  imports: [ReactiveFormsModule, Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">{{ auth.admin() ? 'ADMINISTRAÇÃO' : 'EQUIPE' }}</p><h1>{{ auth.admin() ? 'Usuários' : 'Contas de consulta' }}</h1><p class="muted">{{ auth.admin() ? 'Controle quem pode consultar, operar e administrar o sistema.' : 'Crie contas de consulta para as pessoas da sua equipe. Elas terão acesso somente de leitura.' }}</p></div>
    @if (auth.admin()) {<button class="primary" (click)="edit()">+ Novo usuário</button>}
    @else if (!editing()) {<button class="primary" (click)="edit()">+ Criar conta de consulta</button>}</div>
    <app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    @if (editing()) {<section class="panel form-panel"><h2>{{ auth.admin() ? (id() ? 'Editar usuário' : 'Novo usuário') : 'Nova conta de consulta' }}</h2>
      @if (!auth.admin()) {<p class="muted">A conta será criada ativa com o perfil Consulta. Defina uma senha inicial e compartilhe-a com a pessoa.</p>}
      <form [formGroup]="form" (ngSubmit)="save()"><div class="form-grid">
        <label>Nome *<input formControlName="name" maxlength="200" required></label><label>E-mail *<input type="email" formControlName="email" autocomplete="off" required></label>
        <label>{{ id() ? 'Nova senha (opcional)' : 'Senha inicial *' }}<input type="password" formControlName="password" autocomplete="new-password" minlength="8" maxlength="128" [required]="!id()"><small>Use pelo menos 8 caracteres, com maiúscula, minúscula e número.</small></label>
        @if (auth.admin()) {<label>Perfil<select formControlName="role"><option value="consulta">Consulta</option><option value="operador">Operador</option><option value="admin">Administrador</option></select></label>
          <label class="checkbox-label"><input type="checkbox" formControlName="ativo"> Conta ativa</label>}
      </div><div class="form-actions">@if (auth.admin()) {<button type="button" class="secondary" (click)="cancel()">Cancelar</button>}<button class="primary" [disabled]="saving()">{{ auth.admin() ? 'Salvar usuário' : 'Criar conta' }}</button></div></form></section>}
    @if (auth.admin()) {<section class="panel"><form class="toolbar" (submit)="$event.preventDefault(); load()"><label class="search-field"><span class="sr-only">Buscar usuário</span><input #search placeholder="Buscar por nome" (input)="query = search.value"></label><button class="secondary">Buscar</button></form>
      <div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
        @for (row of rows(); track row.id) {<tr><td>{{ row.name }}</td><td>{{ row.email }}</td><td>{{ roleName(row.role) }}</td><td><span class="badge" [attr.data-status]="row.ativo ? 'pago' : 'cancelado'">{{ row.ativo ? 'Ativo' : 'Desativado' }}</span></td><td><button class="link-button" (click)="edit(row)">Editar</button></td></tr>}
        @empty {<tr><td colspan="5" class="empty">Nenhum usuário encontrado.</td></tr>}</tbody></table></div>
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" /></section>}
    <p class="muted mt-4 text-sm">Consulta: somente leitura. Operador: cadastros, pagamentos e criação de contas de consulta. Administrador: gestão de usuários, solicitações de acesso e auditoria.</p>`,
})
export class Users implements OnInit {
  private api = inject(Api); auth = inject(Auth);
  form = inject(FormBuilder).nonNullable.group({name: ['', Validators.required], email: ['', [Validators.required, Validators.email]], password: '', role: 'consulta', ativo: true});
  rows = signal<User[]>([]); editing = signal(false); id = signal<number | undefined>(undefined); loading = signal(false); saving = signal(false); error = signal(''); success = signal('');
  page = signal(1); last = signal(1); total = signal(0); query = '';
  roleName(role: Role): string { return {admin: 'Administrador', operador: 'Operador', consulta: 'Consulta'}[role]; }
  ngOnInit(): void { if (this.auth.admin()) void this.load(); else this.edit(); }
  async load(page = 1): Promise<void> {
    if (!this.auth.admin()) return;
    this.loading.set(true); this.error.set('');
    try { const data = await this.api.list<User>('usuarios', {q: this.query, page}); this.rows.set(data.data); this.page.set(data.current_page!); this.last.set(data.last_page!); this.total.set(data.total!); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
  edit(row?: User): void { this.id.set(row?.id); this.form.reset(row ? {...row, password: ''} : {name: '', email: '', password: '', role: 'consulta', ativo: true}); this.editing.set(true); this.error.set(''); this.success.set(''); }
  cancel(): void { this.editing.set(false); if (!this.auth.admin()) this.edit(); }
  async save(): Promise<void> {
    if (this.form.invalid) { this.form.markAllAsTouched(); this.error.set('Informe nome, e-mail válido e uma senha inicial.'); return; }
    const formData = this.form.getRawValue();
    if (!this.id() && (!formData.password || formData.password.length < 8 || !/[A-Z]/.test(formData.password) || !/[a-z]/.test(formData.password) || !/\d/.test(formData.password))) {
      this.error.set('A senha precisa ter ao menos 8 caracteres, incluindo maiúscula, minúscula e número.'); return;
    }
    const payload = this.auth.admin() ? formData : {name: formData.name, email: formData.email, password: formData.password};
    this.saving.set(true); this.error.set('');
    try {
      if (this.auth.admin()) await this.api.save<User>('usuarios', payload, this.id());
      else await this.api.post<{data: User}>('usuarios/consulta', payload);
      if (this.auth.admin()) { this.editing.set(false); await this.load(); this.success.set('Usuário salvo.'); }
      else { this.form.reset({name: '', email: '', password: '', role: 'consulta', ativo: true}); this.success.set('Conta de consulta criada. Compartilhe a senha inicial com a pessoa.'); }
    } catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
}
