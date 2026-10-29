export type Role = 'admin' | 'operador' | 'consulta';
export interface User { id: number; name: string; email: string; role: Role; ativo: boolean; password_setup_required: boolean; }
export type AccessRequestStatus = 'pending' | 'approved' | 'rejected' | 'completed';
export interface AccountAccessRequest {
  id: number; name: string; email: string; message: string; status: AccessRequestStatus;
  response_message: string | null; reviewed_at: string | null; completed_at: string | null;
  created_at: string; reviewer: {id: number; name: string} | null;
}
export interface Supplier { id: number; nome: string; cnpj: string; telefone: string; cidade: string; arquivado: boolean; }
export interface Payment { id: number; compra_id: number; valor: string; data: string; estornado_em: string | null; motivo_estorno: string | null; }
export interface Purchase {
  id: number; fornecedores_id: number | null; fornecedor: Supplier | null; data: string;
  valor: string; status: 'pendente' | 'pago' | 'cancelado'; vencimento: string | null;
  descricao: string | null; pago: string; saldo: string; quitada_no_legado: boolean; pagamentos: Payment[];
}
export interface Envelope<T> { data: T; }
export interface Page<T> {
  data: T[]; meta?: {current_page: number; last_page: number; total: number};
  current_page?: number; last_page?: number; total?: number;
}
export interface Dashboard { fornecedores: number; compras: number; saldo_pendente: string; vencidas: number; mensal: {mes: string; valor: string}[]; recentes: Purchase[]; }
export interface Notice { id: string; data: {compra_id: number; titulo: string; mensagem: string}; read_at: string | null; created_at: string; }
export interface Audit { id: number; acao: string; entidade: string; entidade_id: number; antes: Record<string, unknown>; depois: Record<string, unknown>; user: {name: string} | null; created_at: string; }
export interface Report { quantidade: number; total: string; saldo: string; }
export interface SupplierReport { id: number; nome: string; cnpj: string; compras: number; contratado: string; pago: string; saldo: string; }
export type Filters = Record<string, string | number>;
