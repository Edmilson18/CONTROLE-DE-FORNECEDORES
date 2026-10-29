<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $paymentTotals = DB::table('pagamentos')
            ->whereNull('estornado_em')
            ->select('compra_id')
            ->selectRaw('SUM(ROUND(valor * 100)) AS paid_cents')
            ->groupBy('compra_id');
        $pendingSummary = Compra::query()
            ->leftJoinSub($paymentTotals, 'payment_totals', fn ($join) => $join->on('compras.id', '=', 'payment_totals.compra_id'))
            ->where('compras.status', 'pendente')
            ->selectRaw('COALESCE(SUM(CASE WHEN ROUND(compras.valor * 100) > COALESCE(payment_totals.paid_cents, 0) THEN ROUND(compras.valor * 100) - COALESCE(payment_totals.paid_cents, 0) ELSE 0 END), 0) AS balance_cents')
            ->selectRaw('COALESCE(SUM(CASE WHEN compras.vencimento < ? AND ROUND(compras.valor * 100) > COALESCE(payment_totals.paid_cents, 0) THEN 1 ELSE 0 END), 0) AS overdue_count', [today()->toDateString()])
            ->first();
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $monthly[] = ['mes' => $month->format('Y-m'), 'valor' => number_format((float) Compra::where('status', '!=', 'cancelado')
                ->whereBetween('data', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->sum('valor'), 2, '.', '')];
        }

        return response()->json(['data' => [
            'fornecedores' => Fornecedor::count(), 'compras' => Compra::count(),
            'saldo_pendente' => Money::decimal((int) $pendingSummary->balance_cents),
            'vencidas' => (int) $pendingSummary->overdue_count,
            'mensal' => $monthly,
            'recentes' => CompraResource::collection(Compra::with(['fornecedor', 'pagamentos'])->latest('id')->limit(5)->get()),
        ]]);
    }
}
