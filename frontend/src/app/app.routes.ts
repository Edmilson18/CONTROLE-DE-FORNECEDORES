import { Routes } from '@angular/router';
import { authGuard, adminGuard } from './core/auth';
import { Shell } from './layout/shell';

export const routes: Routes = [
  {path: 'entrar', title: 'Entrar · Controle de fornecedores', loadComponent: () => import('./pages/login').then(m => m.Login)},
  {path: '', component: Shell, canActivate: [authGuard], children: [
    {path: '', title: 'Visão geral', loadComponent: () => import('./pages/dashboard').then(m => m.Dashboard)},
    {path: 'fornecedores', title: 'Fornecedores', loadComponent: () => import('./pages/suppliers').then(m => m.Suppliers)},
    {path: 'compras', title: 'Compras e pagamentos', loadComponent: () => import('./pages/purchases').then(m => m.Purchases)},
    {path: 'compras/:id', title: 'Detalhes da compra', loadComponent: () => import('./pages/purchase-detail').then(m => m.PurchaseDetail)},
    {path: 'relatorios', title: 'Relatórios', loadComponent: () => import('./pages/reports').then(m => m.Reports)},
    {path: 'usuarios', title: 'Usuários', canActivate: [adminGuard], loadComponent: () => import('./pages/users').then(m => m.Users)},
    {path: 'auditoria', title: 'Histórico de alterações', canActivate: [adminGuard], loadComponent: () => import('./pages/audit').then(m => m.Audit)},
    {path: 'notificacoes', title: 'Notificações', loadComponent: () => import('./pages/notifications').then(m => m.Notifications)},
    {path: 'conta', title: 'Minha conta', loadComponent: () => import('./pages/account').then(m => m.Account)},
  ]},
  {path: '**', redirectTo: ''},
];
