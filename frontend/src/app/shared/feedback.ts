import { Component, input } from '@angular/core';
@Component({
  selector: 'app-feedback',
  template: `@if (error()) { <div class="alert error" role="alert">{{ error() }}</div> }
    @if (success()) { <div class="alert success" role="status">{{ success() }}</div> }
    @if (loading()) { <p class="muted py-4" role="status">Carregando…</p> }`,
})
export class Feedback { error = input(''); success = input(''); loading = input(false); }
