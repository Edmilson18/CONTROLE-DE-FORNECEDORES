<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\FornecedorRequest;
use App\Http\Resources\FornecedorResource;
use App\Models\Fornecedor;
use App\Services\AuditService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FornecedorController extends Controller
{
    public function index(FilterRequest $request): AnonymousResourceCollection
    {
        $query = Fornecedor::query();
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('nome', 'like', $term)->orWhere('cnpj', 'like', $term)->orWhere('cidade', 'like', $term));
        }

        return FornecedorResource::collection($query->orderBy('nome')->orderBy('id')->paginate($request->integer('per_page', 15)));
    }

    public function show(Fornecedor $fornecedor): FornecedorResource
    {
        return new FornecedorResource($fornecedor);
    }

    public function store(FornecedorRequest $request, AuditService $audit): FornecedorResource
    {
        return DB::transaction(function () use ($request, $audit): FornecedorResource {
            $model = Fornecedor::create([...$request->validated(), 'telefone' => $request->input('telefone') ?? '']);
            $audit->record('criar', $model);

            return new FornecedorResource($model);
        });
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedor, AuditService $audit): FornecedorResource
    {
        return DB::transaction(function () use ($request, $fornecedor, $audit): FornecedorResource {
            $before = $fornecedor->attributesToArray();
            $fornecedor->update([...$request->validated(), 'telefone' => $request->input('telefone') ?? '']);
            $audit->record('atualizar', $fornecedor, $before);

            return new FornecedorResource($fornecedor);
        });
    }

    public function destroy(Fornecedor $fornecedor, AuditService $audit): Response
    {
        Gate::authorize('delete', $fornecedor);
        DB::transaction(function () use ($fornecedor, $audit): void {
            $before = $fornecedor->attributesToArray();
            $fornecedor->delete();
            $audit->record('excluir', $fornecedor, $before);
        });

        return response()->noContent();
    }
}
