import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Auth } from '../core/auth';
import { errorMessage } from '../core/api';
import { Feedback } from '../shared/feedback';
@Component({
  imports: [ReactiveFormsModule, Feedback],
  template: `<div class="page-heading"><div><p class="eyebrow">SEU ACESSO</p><h1>Minha conta</h1><p class="muted">{{ auth.user()?.name }} · {{ auth.user()?.email }}</p></div></div>
    <app-feedback [error]="error()" [success]="success()" />
    <section class="panel form-panel max-w-2xl"><h2>Alterar senha</h2><form [formGroup]="form" (ngSubmit)="save()" class="grid gap-5 mt-6">
      <label>Senha atual<input type="password" formControlName="current_password" autocomplete="current-password" required></label>
      <label>Nova senha<input type="password" formControlName="password" autocomplete="new-password" minlength="12" maxlength="128" required><small>Use pelo menos 12 caracteres, maiúscula, minúscula e número.</small></label>
      <label>Confirme a nova senha<input type="password" formControlName="password_confirmation" autocomplete="new-password" required></label>
      <div class="form-actions"><button class="primary" [disabled]="saving()">Atualizar senha</button></div></form></section>`,
})
export class Account {
  auth = inject(Auth); saving = signal(false); error = signal(''); success = signal('');
  form = inject(FormBuilder).nonNullable.group({current_password: ['', Validators.required], password: ['', [Validators.required, Validators.minLength(12)]], password_confirmation: ['', Validators.required]});
  async save(): Promise<void> {
    if (this.form.invalid) { this.error.set('Preencha os campos. A nova senha deve ter ao menos 12 caracteres.'); return; }
    this.saving.set(true); this.error.set(''); this.success.set('');
    try { await this.auth.changePassword(this.form.getRawValue()); this.form.reset(); this.success.set('Senha atualizada.'); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
}
