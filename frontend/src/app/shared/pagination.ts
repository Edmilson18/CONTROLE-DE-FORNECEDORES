import { Component, input, output } from '@angular/core';
@Component({
  selector: 'app-pagination',
  template: `<nav class="pagination" aria-label="Paginação"><span>{{ total() }} registro(s) · Página {{ page() }} de {{ last() }}</span>
    <div class="flex gap-2"><button class="secondary" [disabled]="page() <= 1 || busy()" (click)="change.emit(page()-1)">Anterior</button>
    <button class="secondary" [disabled]="page() >= last() || busy()" (click)="change.emit(page()+1)">Próxima</button></div></nav>`,
})
export class Pagination { page = input(1); last = input(1); total = input(0); busy = input(false); change = output<number>(); }
