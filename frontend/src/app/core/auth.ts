import { computed, inject, Injectable, signal } from '@angular/core';
import { HttpClient, HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { CanActivateFn, Router } from '@angular/router';
import { firstValueFrom, catchError, throwError } from 'rxjs';
import { Envelope, User } from './models';

@Injectable({providedIn: 'root'})
export class Auth {
  private http = inject(HttpClient);
  user = signal<User | null>(null);
  canEdit = computed(() => ['admin', 'operador'].includes(this.user()?.role ?? ''));
  admin = computed(() => this.user()?.role === 'admin');
  async load(): Promise<boolean> {
    try { this.user.set((await firstValueFrom(this.http.get<Envelope<User>>('/api/auth/me'))).data); return true; }
    catch { this.user.set(null); return false; }
  }
  async login(email: string, password: string): Promise<void> {
    await firstValueFrom(this.http.get('/sanctum/csrf-cookie'));
    this.user.set((await firstValueFrom(this.http.post<Envelope<User>>('/api/auth/login', {email, password}))).data);
  }
  async logout(): Promise<void> { await firstValueFrom(this.http.post('/api/auth/logout', {})); this.user.set(null); }
  changePassword(data: unknown): Promise<unknown> { return firstValueFrom(this.http.put('/api/auth/password', data)); }
}
export const authGuard: CanActivateFn = async () => {
  const auth = inject(Auth); const router = inject(Router);
  return (await auth.load()) || router.parseUrl('/entrar');
};
export const adminGuard: CanActivateFn = () => inject(Auth).admin() || inject(Router).parseUrl('/');
export const apiInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router); const auth = inject(Auth);
  return next(req.clone({withCredentials: true, setHeaders: {Accept: 'application/json'}})).pipe(catchError((error: unknown) => {
    if (error instanceof HttpErrorResponse && [401, 419].includes(error.status) && !req.url.endsWith('/auth/login')) {
      auth.user.set(null); void router.navigateByUrl('/entrar');
    }
    return throwError(() => error);
  }));
};
