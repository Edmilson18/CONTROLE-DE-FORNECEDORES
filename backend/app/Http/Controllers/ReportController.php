<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Resources\CompraResource;
use App\Services\PurchaseQuery;
use App\Support\Money;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(FilterRequest $request, PurchaseQuery $query): JsonResponse
    {
        $filtered = $query->filtered($request->validated());
        $quantity = (clone $filtered)->count();
        $totalCents = (int) (clone $filtered)->where('status', '!=', 'cancelado')
            ->selectRaw('COALESCE(SUM(ROUND(compras.valor * 100)), 0) AS total_cents')->value('total_cents');
        $paymentTotals = DB::table('pagamentos')
            ->select('compra_id')
            ->selectRaw('SUM(CASE WHEN estornado_em IS NULL THEN ROUND(valor * 100) ELSE 0 END) AS paid_cents')
            ->selectRaw('COUNT(*) AS payment_count')
            ->groupBy('compra_id');
        $balanceCents = (int) (clone $filtered)
            ->without(['fornecedor', 'pagamentos'])
            ->leftJoinSub($paymentTotals, 'payment_totals', fn ($join) => $join->on('compras.id', '=', 'payment_totals.compra_id'))
            ->selectRaw("COALESCE(SUM(CASE
                WHEN compras.status = 'cancelado' THEN 0
                WHEN compras.status = 'pago' AND COALESCE(payment_totals.payment_count, 0) = 0 THEN 0
                WHEN ROUND(compras.valor * 100) > COALESCE(payment_totals.paid_cents, 0)
                    THEN ROUND(compras.valor * 100) - COALESCE(payment_totals.paid_cents, 0)
                ELSE 0
            END), 0) AS balance_cents")
            ->value('balance_cents');

        return response()->json(['data' => [
            'quantidade' => $quantity,
            'total' => Money::decimal($totalCents),
            'saldo' => Money::decimal($balanceCents),
        ]]);
    }

    public function suppliers(FilterRequest $request, PurchaseQuery $query): JsonResponse
    {
        $paymentTotals = DB::table('pagamentos')
            ->select('compra_id')
            ->selectRaw('SUM(CASE WHEN estornado_em IS NULL THEN ROUND(valor * 100) ELSE 0 END) AS paid_cents')
            ->selectRaw('COUNT(*) AS payment_count')
            ->groupBy('compra_id');

        $rows = $query->filtered($request->validated())
            ->without(['fornecedor', 'pagamentos'])
            ->join('fornecedores', 'fornecedores.id', '=', 'compras.fornecedores_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', fn ($join) => $join->on('compras.id', '=', 'payment_totals.compra_id'))
            ->select('fornecedores.id', 'fornecedores.nome', 'fornecedores.cnpj')
            ->selectRaw('COUNT(compras.id) AS compras')
            ->selectRaw("COALESCE(SUM(CASE WHEN compras.status = 'cancelado' THEN 0 ELSE ROUND(compras.valor * 100) END), 0) AS contratado_cents")
            ->selectRaw("COALESCE(SUM(CASE WHEN compras.status = 'cancelado' THEN 0 WHEN compras.status = 'pago' AND COALESCE(payment_totals.payment_count, 0) = 0 THEN ROUND(compras.valor * 100) ELSE COALESCE(payment_totals.paid_cents, 0) END), 0) AS pago_cents")
            ->selectRaw("COALESCE(SUM(CASE WHEN compras.status = 'cancelado' OR (compras.status = 'pago' AND COALESCE(payment_totals.payment_count, 0) = 0) THEN 0 WHEN ROUND(compras.valor * 100) > COALESCE(payment_totals.paid_cents, 0) THEN ROUND(compras.valor * 100) - COALESCE(payment_totals.paid_cents, 0) ELSE 0 END), 0) AS saldo_cents")
            ->groupBy('fornecedores.id', 'fornecedores.nome', 'fornecedores.cnpj')
            ->orderByDesc('contratado_cents')
            ->limit(100)
            ->get()
            ->map(fn ($row): array => [
                'id' => $row->id,
                'nome' => $row->nome,
                'cnpj' => $row->cnpj,
                'compras' => (int) $row->compras,
                'contratado' => Money::decimal((int) $row->contratado_cents),
                'pago' => Money::decimal((int) $row->pago_cents),
                'saldo' => Money::decimal((int) $row->saldo_cents),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function export(FilterRequest $request, PurchaseQuery $query): Response
    {
        $builder = $query->filtered($request->validated())->orderByDesc('data')->orderByDesc('id');
        if ($request->input('formato', 'csv') === 'pdf') {
            abort_if((clone $builder)->count() > 2000, 422, 'Limite de 2.000 compras por PDF. Reduza o período.');
            $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
            $pdf = new Dompdf($options);
            $pdf->loadHtml(view('reports.compras', ['rows' => $builder->get(), 'filters' => $request->validated()])->render());
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="relatorio-compras.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response()->streamDownload(function () use ($builder): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Compra', 'Fornecedor', 'CNPJ', 'Data', 'Vencimento', 'Valor', 'Pago', 'Saldo', 'Status'], ';', '"', '');
            foreach ($builder->lazy(200) as $row) {
                $data = (new CompraResource($row))->resolve();
                $safe = static fn (string $value): string => preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
                fputcsv($stream, [$row->id, $safe($row->fornecedor?->nome ?? 'Fornecedor removido'),
                    $safe($row->fornecedor?->cnpj ?? ''), $data['data'], $data['vencimento'], $data['valor'], $data['pago'], $data['saldo'], $data['status']], ';', '"', '');
            }
            fclose($stream);
        }, 'relatorio-compras.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
