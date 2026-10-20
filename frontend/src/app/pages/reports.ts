import { Component, inject, OnInit, signal } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Api, errorMessage } from '../core/api';
import { Envelope, Filters, Report, Supplier } from '../core/models';
import { Feedback } from '../shared/feedback';
@Component({
  imports: [CurrencyPipe, ReactiveFormsModule, Feedback],
  template: `<div class="page-heading"><div><p class="eyebrow">ANÁLISE E PRESTAÇÃO DE CONTAS</p><h1>Relatórios</h1><p class="muted">Defina um período e exporte os dados das compras.</p></div></div>
    <app-feedback [error]="error()" [loading]="loading()" />
    <section class="panel form-panel"><h2>Selecione os filtros</h2><form [formGroup]="form" (ngSubmit)="load()"><div class="form-grid">
      <label>Data inicial<input type="date" formControlName="de"></label><label>Data final<input type="date" formControlName="ate"></label>
      <label>Status<select formControlName="status"><option value="">Todos</option><option value="pendente">Pendente</option><option value="pago">Pago</option><option value="cancelado">Cancelado</option></select></label>
      <label>Fornecedor ou descrição<input formControlName="q" placeholder="Digite para filtrar"></label>
    </div><div class="form-actions"><button class="primary" [disabled]="loading()">Atualizar resumo</button></div></form></section>
    @if (report(); as r) {<section class="metrics three"><article><span>Compras no filtro aplicado</span><strong>{{ r.quantidade }}</strong></article>
      <article><span>Total contratado</span><strong>{{ r.total | currency:'BRL' }}</strong><small>Sem compras canceladas</small></article><article><span>Saldo a pagar</span><strong>{{ r.saldo | currency:'BRL' }}</strong></article></section>}
    <section class="panel export-panel"><div><h2>Leve seus dados com você</h2><p class="muted">As exportações usam os filtros atuais. O PDF aceita até 2.000 compras por arquivo.</p></div>
      <div class="flex flex-wrap gap-3"><button class="secondary" (click)="download('csv')" [disabled]="downloading()">Exportar CSV</button><button class="primary" (click)="download('pdf')" [disabled]="downloading()">{{ downloading() ? 'Gerando…' : 'Baixar PDF' }}</button></div></section>`,
})
export class Reports implements OnInit {
  private api = inject(Api); form = inject(FormBuilder).nonNullable.group({de: '', ate: '', status: '', q: ''});
  report = signal<Report | null>(null); error = signal(''); loading = signal(false); downloading = signal(false);
  filters(): Filters { return Object.fromEntries(Object.entries(this.form.getRawValue()).filter(([, v]) => v !== '')); }
  ngOnInit(): void { void this.load(); }
  async load(): Promise<void> { this.loading.set(true); this.error.set(''); try { this.report.set((await this.api.get<Envelope<Report>>('relatorios', this.filters())).data); } catch (e) { this.error.set(errorMessage(e)); } finally { this.loading.set(false); } }
  async download(format: 'pdf' | 'csv'): Promise<void> { this.downloading.set(true); this.error.set(''); try { await this.api.download(format, this.filters()); } catch (e) { this.error.set(errorMessage(e)); } finally { this.downloading.set(false); } }
}
