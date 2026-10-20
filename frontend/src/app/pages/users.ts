import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { User, Role } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';
@Component({
  imports: [ReactiveFormsModule, Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">ADMINISTRAÇÃO</p><h1>Usuários</h1><p class="muted">Controle quem pode consultar, operar e administrar o sistema.</p></div><button class="primary" (click)="edit()">+ Novo usuário</button></div>
    <app-feedback [error]="error()" [success]="success()" [loading]="loading()" />
    @if (editing()) {<section class="panel form-panel"><h2>{{ id() ? 'Editar usuário' : 'Novo usuário' }}</h2><form [formGroup]="form" (ngSubmit)="save()"><div class="form-grid">
      <label>Nome *<input formControlName="name" maxlength="200" required></label><label>E-mail *<input type="email" formControlName="email" autocomplete="off" required></label>
      <label>{{ id() ? 'Nova senha (opcional)' : 'Senha *' }}<input type="password" formControlName="password" autocomplete="new-password" minlength="12" maxlength="128"><small>12 caracteres, maiúscula, minúscula e número.</small></label>
      <label>Perfil<select formControlName="role"><option value="consulta">Consulta</option><option value="operador">Operador</option><option value="admin">Administrador</option></select></label>
      <label class="checkbox-label"><input type="checkbox" formControlName="ativo"> Conta ativa</label>
      </div><div class="form-actions"><button type="button" class="secondary" (click)="editing.set(false)">Cancelar</button><button class="primary" [disabled]="saving()">Salvar usuário</button></div></form></section>}
    <section class="panel"><form class="toolbar" (submit)="$event.preventDefault(); load()"><label class="search-field"><span class="sr-only">Buscar usuário</span><input #search placeholder="Buscar por nome" (input)="query = search.value"></label><button class="secondary">Buscar</button></form>
      <div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
        @for (row of rows(); track row.id) {<tr><td>{{ row.name }}</td><td>{{ row.email }}</td><td>{{ roleName(row.role) }}</td><td><span class="badge" [attr.data-status]="row.ativo ? 'pago' : 'cancelado'">{{ row.ativo ? 'Ativo' : 'Desativado' }}</span></td><td><button class="link-button" (click)="edit(row)">Editar</button></td></tr>}
        @empty {<tr><td colspan="5" class="empty">Nenhum usuário encontrado.</td></tr>}</tbody></table></div>
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" /></section>
    <p class="muted mt-4 text-sm">Consulta: somente leitura. Operador: cadastros e pagamentos. Administrador: exclusões, estornos, usuários e auditoria.</p>`,
})
export class Users implements OnInit {
  private api = inject(Api); form = inject(FormBuilder).nonNullable.group({name: ['', Validators.required], email: ['', [Validators.required, Validators.email]], password: '', role: 'consulta', ativo: true});
  rows = signal<User[]>([]); editing = signal(false); id = signal<number | undefined>(undefined); loading = signal(false); saving = signal(false); error = signal(''); success = signal('');
  page = signal(1); last = signal(1); total = signal(0); query = '';
  roleName(role: Role): string { return {admin: 'Administrador', operador: 'Operador', consulta: 'Consulta'}[role]; }
  ngOnInit(): void { void this.load(); }
  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try { const data = await this.api.list<User>('usuarios', {q: this.query, page}); this.rows.set(data.data); this.page.set(data.current_page!); this.last.set(data.last_page!); this.total.set(data.total!); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
  edit(row?: User): void { this.id.set(row?.id); this.form.reset(row ? {...row, password: ''} : {name: '', email: '', password: '', role: 'consulta', ativo: true}); this.editing.set(true); this.error.set(''); this.success.set(''); }
  async save(): Promise<void> {
    if (this.form.invalid) { this.form.markAllAsTouched(); this.error.set('Informe nome e e-mail válidos.'); return; }
    this.saving.set(true); this.error.set('');
    try { await this.api.save<User>('usuarios', this.form.getRawValue(), this.id()); this.editing.set(false); await this.load(); this.success.set('Usuário salvo.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
}
