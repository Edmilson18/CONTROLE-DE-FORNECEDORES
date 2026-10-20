<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Services\PurchaseQuery;
use App\Support\Money;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(FilterRequest $request, PurchaseQuery $query): JsonResponse
    {
        $rows = $query->filtered($request->validated())->get();

        return response()->json(['data' => [
            'quantidade' => $rows->count(),
            'total' => Money::decimal($rows->where('status', '!=', 'cancelado')->sum(fn (Compra $c): int => Money::cents($c->valor))),
            'saldo' => Money::decimal($rows->sum(fn (Compra $c): int => $c->balanceCents())),
        ]]);
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
