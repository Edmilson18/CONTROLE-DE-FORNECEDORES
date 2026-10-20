import { Routes } from '@angular/router';
import { authGuard, adminGuard, readyGuard } from './core/auth';
import { Shell } from './layout/shell';

export const routes: Routes = [
  {path: 'entrar', title: 'Entrar · Controle de fornecedores', loadComponent: () => import('./pages/login').then(m => m.Login)},
  {path: 'solicitar-acesso', title: 'Solicitar acesso · Controle de fornecedores', loadComponent: () => import('./pages/request-access').then(m => m.RequestAccess)},
  {path: 'ativar-conta', title: 'Ativar conta · Controle de fornecedores', loadComponent: () => import('./pages/activate-account').then(m => m.ActivateAccount)},
  {path: '', component: Shell, canActivate: [authGuard], children: [
    {path: '', title: 'Visão geral', canActivate: [readyGuard], loadComponent: () => import('./pages/dashboard').then(m => m.Dashboard)},
    {path: 'fornecedores', title: 'Fornecedores', canActivate: [readyGuard], loadComponent: () => import('./pages/suppliers').then(m => m.Suppliers)},
    {path: 'compras', title: 'Compras e pagamentos', canActivate: [readyGuard], loadComponent: () => import('./pages/purchases').then(m => m.Purchases)},
    {path: 'compras/:id', title: 'Detalhes da compra', canActivate: [readyGuard], loadComponent: () => import('./pages/purchase-detail').then(m => m.PurchaseDetail)},
    {path: 'relatorios', title: 'Relatórios', canActivate: [readyGuard], loadComponent: () => import('./pages/reports').then(m => m.Reports)},
    {path: 'usuarios', title: 'Usuários', canActivate: [readyGuard, adminGuard], loadComponent: () => import('./pages/users').then(m => m.Users)},
    {path: 'solicitacoes-acesso', title: 'Solicitações de acesso', canActivate: [readyGuard, adminGuard], loadComponent: () => import('./pages/access-requests').then(m => m.AccessRequests)},
    {path: 'auditoria', title: 'Histórico de alterações', canActivate: [readyGuard, adminGuard], loadComponent: () => import('./pages/audit').then(m => m.Audit)},
    {path: 'notificacoes', title: 'Notificações', canActivate: [readyGuard], loadComponent: () => import('./pages/notifications').then(m => m.Notifications)},
    {path: 'conta', title: 'Minha conta', loadComponent: () => import('./pages/account').then(m => m.Account)},
  ]},
  {path: '**', redirectTo: ''},
];
