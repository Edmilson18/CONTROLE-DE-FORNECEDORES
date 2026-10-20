<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompraRequest;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\Pagamento;
use App\Services\PurchaseQuery;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CompraController extends Controller
{
    public function index(FilterRequest $request, PurchaseQuery $query): AnonymousResourceCollection
    {
        return CompraResource::collection($query->filtered($request->validated())->orderByDesc('data')->orderByDesc('id')->paginate($request->integer('per_page', 15)));
    }

    public function show(Compra $compra): CompraResource
    {
        return new CompraResource($compra->load(['fornecedor', 'pagamentos']));
    }

    public function store(CompraRequest $request, PurchaseService $service): CompraResource
    {
        return new CompraResource($service->save($request->validated()));
    }

    public function update(CompraRequest $request, Compra $compra, PurchaseService $service): CompraResource
    {
        return new CompraResource($service->save($request->validated(), $compra));
    }

    public function destroy(Compra $compra, PurchaseService $service): Response
    {
        Gate::authorize('delete', $compra);
        $service->delete($compra);

        return response()->noContent();
    }

    public function pay(PaymentRequest $request, Compra $compra, PurchaseService $service): JsonResponse
    {
        return response()->json(['data' => $service->pay($compra, $request->validated())], 201);
    }

    public function reverse(Request $request, Pagamento $pagamento, PurchaseService $service): Response
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:500']]);
        $service->reverse($pagamento, $data['motivo']);

        return response()->noContent();
    }
}
