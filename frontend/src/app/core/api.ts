import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { Envelope, Filters, Page } from './models';

@Injectable({providedIn: 'root'})
export class Api {
  private http = inject(HttpClient);
  get<T>(path: string, params: Filters = {}): Promise<T> {
    return firstValueFrom(this.http.get<T>('/api/' + path, {params}));
  }
  csrfCookie(): Promise<unknown> { return firstValueFrom(this.http.get('/sanctum/csrf-cookie')); }
  list<T>(path: string, params: Filters = {}): Promise<Page<T>> { return this.get<Page<T>>(path, params); }
  save<T>(path: string, data: unknown, id?: number): Promise<Envelope<T>> {
    return firstValueFrom(id ? this.http.put<Envelope<T>>(`/api/${path}/${id}`, data) : this.http.post<Envelope<T>>(`/api/${path}`, data));
  }
  post<T>(path: string, data: unknown = {}): Promise<T> { return firstValueFrom(this.http.post<T>('/api/' + path, data)); }
  patch(path: string, data: unknown = {}): Promise<unknown> { return firstValueFrom(this.http.patch('/api/' + path, data)); }
  remove(path: string): Promise<unknown> { return firstValueFrom(this.http.delete('/api/' + path)); }
  async download(format: 'pdf' | 'csv', params: Filters): Promise<void> {
    const blob = await firstValueFrom(this.http.get('/api/relatorios/exportar', {params: {...params, formato: format}, responseType: 'blob'}));
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url; link.download = `relatorio-compras.${format}`; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
}
export function errorMessage(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    if (error.status === 0) { return 'Não foi possível conectar ao servidor. Confira se o backend está iniciado.'; }
    if (error.status === 401 || error.status === 419) { return 'Sua sessão expirou. Entre novamente.'; }
    if (error.status === 403) { return 'Seu perfil não tem permissão para esta ação.'; }
    if (error.status === 429) { return 'Muitas tentativas. Aguarde um minuto e tente novamente.'; }
    const body = error.error as {errors?: Record<string, string[]>; message?: string} | null;
    if (body?.errors) { return Object.values(body.errors).flat().join(' '); }
    if (body?.message && error.status < 500) { return body.message; }
  }
  return 'Não foi possível concluir a operação. Tente novamente.';
}
