import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Auth } from '../core/auth';
import { errorMessage } from '../core/api';
import { Feedback } from '../shared/feedback';
@Component({
  imports: [ReactiveFormsModule, Feedback],
  template: `<div class="page-heading"><div><p class="eyebrow">SEU ACESSO</p><h1>Minha conta</h1><p class="muted">{{ auth.user()?.name }} · {{ auth.user()?.email }}</p></div></div>
    @if (auth.user()?.password_setup_required) {<div class="alert info" role="status">Seu acesso foi aprovado. Defina uma senha para concluir a ativação da conta; o restante do sistema ficará disponível em seguida.</div>}
    <app-feedback [error]="error()" [success]="success()" />
    <section class="panel form-panel max-w-2xl"><h2>{{ auth.user()?.password_setup_required ? 'Defina sua senha' : 'Alterar senha' }}</h2><form [formGroup]="form" (ngSubmit)="save()" class="grid gap-5 mt-6">
      @if (!auth.user()?.password_setup_required) {<label>Senha atual<input type="password" formControlName="current_password" autocomplete="current-password"></label>}
      <label>Nova senha<input type="password" formControlName="password" autocomplete="new-password" minlength="8" maxlength="128" required><small>Use pelo menos 8 caracteres, maiúscula, minúscula e número.</small></label>
      @if (!auth.user()?.password_setup_required) {<label>Confirme a nova senha<input type="password" formControlName="password_confirmation" autocomplete="new-password" required></label>}
      <div class="form-actions"><button class="primary" [disabled]="saving()">{{ auth.user()?.password_setup_required ? 'Definir senha e concluir' : 'Atualizar senha' }}</button></div></form></section>`,
})
export class Account {
  auth = inject(Auth); saving = signal(false); error = signal(''); success = signal('');
  form = inject(FormBuilder).nonNullable.group({current_password: '', password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(128), Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/)]], password_confirmation: ''});
  async save(): Promise<void> {
    const firstAccess = this.auth.user()?.password_setup_required ?? false;
    if (this.form.invalid || (!firstAccess && (!this.form.controls.current_password.value || !this.form.controls.password_confirmation.value))) {
      this.form.markAllAsTouched();
      this.error.set(firstAccess ? 'Digite uma senha com pelo menos 8 caracteres, letras maiúsculas e minúsculas, e números.' : 'Informe a senha atual e confirme uma nova senha válida.');
      return;
    }
    if (!firstAccess && this.form.controls.password.value !== this.form.controls.password_confirmation.value) { this.error.set('A confirmação da nova senha não confere.'); return; }
    const {current_password, ...newPassword} = this.form.getRawValue();
    this.saving.set(true); this.error.set(''); this.success.set('');
    try {
      await this.auth.changePassword(firstAccess ? {password: newPassword.password} : {current_password, ...newPassword});
      await this.auth.load(); this.form.reset();
      this.success.set(firstAccess ? 'Senha definida. Sua conta foi ativada e o acesso completo está liberado.' : 'Senha atualizada.');
    }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.saving.set(false); }
  }
}
