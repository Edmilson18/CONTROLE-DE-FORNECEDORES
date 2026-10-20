import { Component, inject, OnInit, signal } from '@angular/core';
import { CurrencyPipe, DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Api, errorMessage } from '../core/api';
import { Auth } from '../core/auth';
import { Dashboard as Data, Envelope } from '../core/models';
import { Feedback } from '../shared/feedback';
@Component({
  imports: [CurrencyPipe, DatePipe, RouterLink, Feedback],
  template: `<div class="page-heading"><div><p class="eyebrow">ACOMPANHAMENTO</p><h1>Visão geral</h1><p class="muted">Seus fornecedores e compromissos, em um só lugar.</p></div>
    @if (auth.canEdit()) {<a class="primary" routerLink="/compras">+ Registrar compra</a>}</div>
    <app-feedback [error]="error()" [loading]="loading()" />
    @if (data(); as d) {
      <section class="metrics" aria-label="Indicadores"><article><span>Saldo a pagar</span><strong>{{ d.saldo_pendente | currency:'BRL' }}</strong><small>Compras pendentes, descontando pagamentos</small></article>
        <article><span>Compras vencidas</span><strong [class.danger-text]="d.vencidas > 0">{{ d.vencidas }}</strong><small>Compromissos que precisam de atenção</small></article>
        <article><span>Fornecedores ativos</span><strong>{{ d.fornecedores }}</strong><a routerLink="/fornecedores">Consultar cadastro →</a></article>
        <article><span>Compras registradas</span><strong>{{ d.compras }}</strong><a routerLink="/compras">Acompanhar compras →</a></article></section>
      <section class="panel chart-panel"><div class="section-heading"><div><h2>Compras nos últimos meses</h2><p class="muted">Valor contratado por mês, sem compras canceladas.</p></div><span class="tag">Últimos 6 meses</span></div>
        <div class="bar-chart" role="img" aria-label="Gráfico de compras por mês. Valores disponíveis em cada coluna.">
          @for (month of d.mensal; track month.mes) {<div class="bar-column"><span class="chart-value">{{ month.valor | currency:'BRL':'symbol':'1.0-0' }}</span>
            <div class="bar-track"><div class="bar" [style.height.%]="height(month.valor)"></div></div><span>{{ month.mes + '-01' | date:'MMM/yy':'UTC' }}</span></div>}
        </div></section>
      <section class="panel"><div class="section-heading"><h2>Últimas compras</h2><a routerLink="/compras">Ver todas →</a></div>
        <div class="table-wrap"><table><thead><tr><th>Compra / fornecedor</th><th>Data</th><th>Status</th><th class="numeric">Valor</th><th class="numeric">Saldo</th></tr></thead>
        <tbody>@for (row of d.recentes; track row.id) {<tr><td><a [routerLink]="['/compras', row.id]">#{{ row.id }} · {{ row.fornecedor?.nome ?? 'Fornecedor removido' }}</a></td><td>{{ row.data | date:'dd/MM/yyyy':'UTC' }}</td>
          <td><span class="badge" [attr.data-status]="row.status">{{ row.status }}</span></td><td class="numeric">{{ row.valor | currency:'BRL' }}</td><td class="numeric">{{ row.saldo | currency:'BRL' }}</td></tr>}
          @empty {<tr><td colspan="5" class="empty">Nenhuma compra registrada. Comece pelo cadastro de fornecedores.</td></tr>}</tbody></table></div>
      </section>
    }`,
})
export class Dashboard implements OnInit {
  private api = inject(Api); auth = inject(Auth); data = signal<Data | null>(null); error = signal(''); loading = signal(true);
  async ngOnInit(): Promise<void> { try { this.data.set((await this.api.get<Envelope<Data>>('dashboard')).data); } catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); } }
  height(value: string): number { const max = Math.max(...(this.data()?.mensal.map(m => Number(m.valor)) ?? [1]), 1); return Number(value) / max * 100; }
}
