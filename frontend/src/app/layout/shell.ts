import { Component, inject, signal, OnInit } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Auth } from '../core/auth';
import { Api, errorMessage } from '../core/api';
import { Envelope } from '../core/models';
@Component({
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  template: `<a href="#main-content" class="skip-link">Pular para o conteúdo</a>
    <div class="workspace"><aside class="sidebar" [class.mobile-open]="open()">
      <a class="brand-mark" routerLink="/" (click)="open.set(false)">CF<span>Controle de<br>fornecedores</span></a>
      <p class="nav-caption">ESPAÇO DE TRABALHO</p>
      <nav aria-label="Menu principal">
      @for (link of links; track link.path) {
        <a [routerLink]="link.path" routerLinkActive="active" [routerLinkActiveOptions]="{exact: link.path === '/'}" (click)="open.set(false)">
          <span class="nav-icon" aria-hidden="true">{{ link.icon }}</span>{{ link.name }}
        </a>
      }
      @if (auth.admin()) {
        <p class="nav-caption mt-8">ADMINISTRAÇÃO</p>
        <a routerLink="/usuarios" routerLinkActive="active" (click)="open.set(false)"><span class="nav-icon" aria-hidden="true">♙</span>Usuários</a>
        <a routerLink="/auditoria" routerLinkActive="active" (click)="open.set(false)"><span class="nav-icon" aria-hidden="true">≡</span>Histórico de alterações</a>
      }</nav>
      <div class="sidebar-foot"><span class="status-dot"></span> Seu controle, em dia.</div>
    </aside><div class="main-column">
      <header class="topbar"><button class="secondary mobile-toggle" (click)="open.set(!open())" [attr.aria-expanded]="open()" aria-label="Abrir menu">☰</button>
        <span class="topbar-title">GESTÃO DE FORNECEDORES</span>
        <div class="flex items-center gap-4"><a routerLink="/notificacoes" class="notification-link">Notificações @if (unread() > 0) {<span class="count">{{ unread() }}</span>}</a>
        <a routerLink="/conta" class="user-link"><span class="avatar">{{ auth.user()?.name?.slice(0,1) }}</span><span>{{ auth.user()?.name }}<small>{{ auth.user()?.role === 'admin' ? 'Administrador' : auth.user()?.role === 'operador' ? 'Operador' : 'Consulta' }}</small></span></a>
        <button class="link-button" (click)="logout()">Sair</button></div>
      </header>
      @if (error()) {<p class="alert error" role="alert">{{ error() }}</p>}
      <main id="main-content" class="page-content" tabindex="-1"><router-outlet /></main>
      <footer class="page-footer">Controle de fornecedores <span>Valores em reais · Datas no horário de Brasília</span></footer>
    </div></div>`,
})
export class Shell implements OnInit {
  auth = inject(Auth); private api = inject(Api); private router = inject(Router);
  open = signal(false); unread = signal(0); error = signal('');
  links = [{path: '/', name: 'Visão geral', icon: '◫'}, {path: '/fornecedores', name: 'Fornecedores', icon: '▦'},
    {path: '/compras', name: 'Compras e pagamentos', icon: '↗'}, {path: '/relatorios', name: 'Relatórios', icon: '▤'}];
  ngOnInit(): void { void this.api.get<Envelope<{nao_lidas: number}>>('notificacoes/contagem').then(r => this.unread.set(r.data.nao_lidas)).catch(() => {}); }
  async logout(): Promise<void> {
    try { await this.auth.logout(); await this.router.navigateByUrl('/entrar'); }
    catch (e) { this.error.set(errorMessage(e)); }
  }
}
