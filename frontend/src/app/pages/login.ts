import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Auth } from '../core/auth';
import { errorMessage } from '../core/api';
import { Feedback } from '../shared/feedback';

@Component({
  imports: [ReactiveFormsModule, Feedback],
  template: `<main class="login-layout">
    <section class="login-story"><div class="brand-mark">CF<span>Controle de fornecedores</span></div>
      <div><p class="eyebrow">GESTÃO COM CLAREZA</p><h1>Da compra<br>ao pagamento.<br><em>Tudo à vista.</em></h1>
      <p>Organize seus fornecedores, acompanhe compromissos e mantenha o histórico de cada decisão.</p></div>
      <span class="muted-light">Fornecedores · Compras · Pagamentos</span>
    </section>
    <section class="login-form"><div class="w-full max-w-md"><p class="eyebrow">SEU ESPAÇO DE TRABALHO</p><h2>Bem-vindo de volta</h2>
      <p class="muted mb-8">Entre com sua conta para continuar.</p>
      <app-feedback [error]="error()" />
      <form [formGroup]="form" (ngSubmit)="submit()" class="grid gap-5">
        <label>E-mail<input type="email" formControlName="email" autocomplete="username" placeholder="seu@email.com" required></label>
        <label>Senha<input type="password" formControlName="password" autocomplete="current-password" required></label>
        <button class="primary w-full" [disabled]="busy()">{{ busy() ? 'Entrando…' : 'Entrar no sistema' }} <span aria-hidden="true">→</span></button>
      </form><p class="muted text-sm mt-8">Precisa de acesso? Solicite uma conta ao administrador.</p>
    </div></section></main>`,
})
export class Login {
  private auth = inject(Auth); private router = inject(Router);
  form = inject(FormBuilder).nonNullable.group({email: ['', [Validators.required, Validators.email]], password: ['', Validators.required]});
  busy = signal(false); error = signal('');
  async submit(): Promise<void> {
    if (this.form.invalid) { this.form.markAllAsTouched(); this.error.set('Informe um e-mail válido e sua senha.'); return; }
    this.busy.set(true); this.error.set('');
    try { const data = this.form.getRawValue(); await this.auth.login(data.email, data.password); await this.router.navigateByUrl('/'); }
    catch (e) { this.error.set(errorMessage(e)); }
    finally { this.busy.set(false); }
  }
}
