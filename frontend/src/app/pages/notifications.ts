import { Component, inject, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Api, errorMessage } from '../core/api';
import { Notice } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';
@Component({
  imports: [DatePipe, RouterLink, Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">COMPROMISSOS</p><h1>Notificações</h1><p class="muted">Avisos de pagamentos vencidos ou próximos do vencimento.</p></div></div>
    <app-feedback [error]="error()" [loading]="loading()" />
    <section class="panel">@for (item of rows(); track item.id) {<article class="notice" [class.unread]="!item.read_at"><div><span class="eyebrow">{{ item.created_at | date:'dd/MM/yyyy HH:mm' }}</span><h2>{{ item.data.titulo }}</h2><p class="muted">{{ item.data.mensagem }}</p>
      <a [routerLink]="['/compras', item.data.compra_id]">Abrir compra →</a></div>@if (!item.read_at) {<button class="secondary" (click)="read(item)">Marcar como lida</button>} @else {<span class="tag">Lida</span>}</article>}
      @empty {<p class="empty">Nenhuma notificação por enquanto. Os avisos são gerados diariamente pelo agendador.</p>}
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" /></section>`,
})
export class Notifications implements OnInit {
  private api = inject(Api); rows = signal<Notice[]>([]); page = signal(1); last = signal(1); total = signal(0); error = signal(''); loading = signal(false);
  ngOnInit(): void { void this.load(); }
  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try { const data = await this.api.list<Notice>('notificacoes', {page}); this.rows.set(data.data); this.page.set(data.current_page!); this.last.set(data.last_page!); this.total.set(data.total!); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
  async read(item: Notice): Promise<void> { try { await this.api.patch(`notificacoes/${item.id}/ler`); await this.load(this.page()); } catch (e) { this.error.set(errorMessage(e)); } }
}
