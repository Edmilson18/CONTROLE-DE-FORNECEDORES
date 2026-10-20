<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\Fornecedor;
use App\Support\Money;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pending = Compra::with('pagamentos')->where('status', 'pendente')->get();
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $monthly[] = ['mes' => $month->format('Y-m'), 'valor' => number_format((float) Compra::where('status', '!=', 'cancelado')
                ->whereBetween('data', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->sum('valor'), 2, '.', '')];
        }

        return response()->json(['data' => [
            'fornecedores' => Fornecedor::count(), 'compras' => Compra::count(),
            'saldo_pendente' => Money::decimal($pending->sum(fn (Compra $c): int => $c->balanceCents())),
            'vencidas' => $pending->filter(fn (Compra $c): bool => $c->vencimento !== null && $c->vencimento->lt(today()) && $c->balanceCents() > 0)->count(),
            'mensal' => $monthly,
            'recentes' => CompraResource::collection(Compra::with(['fornecedor', 'pagamentos'])->latest('id')->limit(5)->get()),
        ]]);
    }
}
