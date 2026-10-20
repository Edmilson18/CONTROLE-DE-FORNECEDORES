import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Api, errorMessage } from '../core/api';
import { Feedback } from '../shared/feedback';

@Component({
  imports: [ReactiveFormsModule, RouterLink, Feedback],
  template: `<main class="login-layout">
    <section class="login-story"><a class="brand-mark" routerLink="/entrar">CF<span>Controle de fornecedores</span></a>
      <div><p class="eyebrow">ACESSO COM RESPONSABILIDADE</p><h1>Seu trabalho,<br>organizado<br><em>em um só lugar.</em></h1>
        <p>Conte ao administrador por que você precisa de acesso. Sua solicitação será analisada antes da criação da conta.</p></div>
      <span class="muted-light">Fornecedores · Compras · Pagamentos</span>
    </section>
    <section class="login-form"><div class="w-full max-w-md"><p class="eyebrow">SOLICITAR CONTA</p><h2>Peça seu acesso</h2>
      @if (sent()) {
        <div class="alert success" role="status">Solicitação enviada. Se ela puder ser registrada, o administrador verá a mensagem no sistema. A resposta será enviada para o e-mail informado.</div>
        <a routerLink="/entrar" class="primary inline-flex mt-6">Voltar para entrar</a>
      } @else {
        <p class="muted mb-8">Informe seus dados e explique o motivo do pedido em pelo menos 100 caracteres.</p>
        <app-feedback [error]="error()" [loading]="busy()" />
        <form [formGroup]="form" (ngSubmit)="submit()" class="grid gap-5">
          <label>Nome completo<input formControlName="name" autocomplete="name" maxlength="200" required></label>
          <label>E-mail para resposta<input type="email" formControlName="email" autocomplete="email" maxlength="255" placeholder="voce@empresa.com" required></label>
          <label>Por que você precisa de uma conta?<textarea formControlName="message" rows="6" minlength="100" maxlength="2000" required></textarea>
            <small>{{ form.controls.message.value.length }}/2000 caracteres · mínimo 100</small></label>
          <button class="primary w-full" [disabled]="busy()">{{ busy() ? 'Enviando…' : 'Enviar solicitação' }} <span aria-hidden="true">→</span></button>
        </form>
        <p class="muted text-sm mt-8">Já tem uma conta? <a routerLink="/entrar" class="text-link">Voltar para o login</a></p>
      }
    </div></section>
  </main>`,
})
export class RequestAccess {
  private api = inject(Api);
  form = inject(FormBuilder).nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(200)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
    message: ['', [Validators.required, Validators.minLength(100), Validators.maxLength(2000)]],
  });
  busy = signal(false); error = signal(''); sent = signal(false);

  async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.error.set('Preencha seus dados e escreva uma mensagem com pelo menos 100 caracteres.');
      return;
    }
    this.busy.set(true); this.error.set('');
    try {
      await this.api.csrfCookie();
      await this.api.post('solicitacoes-acesso', this.form.getRawValue());
      this.sent.set(true);
    } catch (error) {
      this.error.set(errorMessage(error));
    } finally {
      this.busy.set(false);
    }
  }
}
