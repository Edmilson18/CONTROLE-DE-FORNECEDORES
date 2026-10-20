import { Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { Auth } from '../core/auth';
import { errorMessage } from '../core/api';
import { Feedback } from '../shared/feedback';

@Component({
  imports: [RouterLink, Feedback],
  template: `<main class="login-layout">
    <section class="login-story"><a class="brand-mark" routerLink="/entrar">CF<span>Controle de fornecedores</span></a>
      <div><p class="eyebrow">ACESSO APROVADO</p><h1>Sua conta<br>está pronta<br><em>para ativar.</em></h1>
        <p>Estamos validando o link enviado pelo administrador e preparando seu perfil.</p></div>
      <span class="muted-light">Acesso inicial com perfil de operador</span>
    </section>
    <section class="login-form"><div class="w-full max-w-md"><p class="eyebrow">ATIVAÇÃO DA CONTA</p><h2>Entrando no seu perfil</h2>
      <p class="muted mb-8">Depois da validação, você poderá definir sua senha em Minha conta.</p>
      <app-feedback [error]="error()" [loading]="busy()" />
      @if (error()) {<a routerLink="/entrar" class="text-link">Voltar para o login</a>}
    </div></section>
  </main>`,
})
export class ActivateAccount implements OnInit {
  private auth = inject(Auth); private router = inject(Router); private route = inject(ActivatedRoute);
  busy = signal(true); error = signal('');

  ngOnInit(): void { void this.activate(); }

  private async activate(): Promise<void> {
    const token = this.route.snapshot.queryParamMap.get('token');
    if (!token) { this.error.set('O link está incompleto. Confira o e-mail de aprovação.'); this.busy.set(false); return; }
    try {
      await this.auth.activateAccess(token);
      await this.router.navigateByUrl('/conta?primeiro-acesso=1');
    } catch (error) {
      this.error.set(errorMessage(error));
    } finally {
      this.busy.set(false);
    }
  }
}
