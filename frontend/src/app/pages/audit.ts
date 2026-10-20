import { Component, inject, OnInit, signal } from '@angular/core';
import { DatePipe, JsonPipe } from '@angular/common';
import { Api, errorMessage } from '../core/api';
import { Audit as Entry } from '../core/models';
import { Feedback } from '../shared/feedback';
import { Pagination } from '../shared/pagination';
@Component({
  imports: [DatePipe, JsonPipe, Feedback, Pagination],
  template: `<div class="page-heading"><div><p class="eyebrow">RASTREABILIDADE</p><h1>Histórico de alterações</h1><p class="muted">Quem alterou, quando e quais dados foram modificados.</p></div></div>
    <app-feedback [error]="error()" [loading]="loading()" />
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>Quando</th><th>Responsável</th><th>Ação</th><th>Registro</th><th>Alterações</th></tr></thead><tbody>
      @for (row of rows(); track row.id) {<tr><td>{{ row.created_at | date:'dd/MM/yyyy HH:mm' }}</td><td>{{ row.user?.name ?? 'Sistema' }}</td><td>{{ row.acao }}</td><td>{{ row.entidade }} #{{ row.entidade_id }}</td>
      <td><details><summary>Ver dados</summary><div class="audit-values"><strong>Antes</strong><pre>{{ row.antes | json }}</pre><strong>Depois</strong><pre>{{ row.depois | json }}</pre></div></details></td></tr>}
      @empty {<tr><td colspan="5" class="empty">Nenhuma alteração registrada.</td></tr>}</tbody></table></div>
      <app-pagination [page]="page()" [last]="last()" [total]="total()" [busy]="loading()" (change)="load($event)" /></section>`,
})
export class Audit implements OnInit {
  private api = inject(Api); rows = signal<Entry[]>([]); page = signal(1); last = signal(1); total = signal(0); error = signal(''); loading = signal(false);
  ngOnInit(): void { void this.load(); }
  async load(page = 1): Promise<void> {
    this.loading.set(true); this.error.set('');
    try { const data = await this.api.list<Entry>('auditoria', {page}); this.rows.set(data.data); this.page.set(data.current_page!); this.last.set(data.last_page!); this.total.set(data.total!); }
    catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); }
  }
}
