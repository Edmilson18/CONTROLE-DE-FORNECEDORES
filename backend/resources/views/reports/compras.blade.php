<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><style>
body{font-family:"DejaVu Sans",sans-serif;color:#173a4a;font-size:10px}
h1{font-size:22px}table{width:100%;border-collapse:collapse;margin-top:20px}
th{text-align:left;background:#e8f1f3}td,th{padding:8px;border-bottom:1px solid #dbe5e9}
.money{text-align:right}footer{margin-top:20px;color:#657888}
</style></head>
<body>
<h1>Controle de fornecedores · Compras</h1>
<p>Emitido em {{ now()->format('d/m/Y H:i') }} · Horário de Brasília</p>
<p>Período: {{ $filters['de'] ?? 'Início' }} a {{ $filters['ate'] ?? 'Hoje' }} · Status: {{ $filters['status'] ?? 'Todos' }}</p>
<table><thead><tr><th>#</th><th>Fornecedor</th><th>Data</th><th>Vencimento</th><th>Status</th><th class="money">Valor</th><th class="money">Saldo</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->id }}</td><td>{{ $row->fornecedor?->nome ?? 'Fornecedor removido' }}</td>
<td>{{ $row->data->format('d/m/Y') }}</td><td>{{ $row->vencimento?->format('d/m/Y') ?? '—' }}</td>
<td>{{ ucfirst($row->status) }}</td><td class="money">R$ {{ number_format((float) $row->valor, 2, ',', '.') }}</td>
<td class="money">R$ {{ number_format($row->balanceCents() / 100, 2, ',', '.') }}</td></tr>
@empty
<tr><td colspan="7">Nenhuma compra encontrada.</td></tr>
@endforelse
</tbody></table><footer>{{ $rows->count() }} registro(s). Compras canceladas não compõem o saldo a pagar.</footer>
</body></html>
