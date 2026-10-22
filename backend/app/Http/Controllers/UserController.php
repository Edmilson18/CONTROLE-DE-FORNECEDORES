<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterRequest;
use App\Http\Requests\ConsultationUserRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(FilterRequest $request): JsonResponse
    {
        $query = User::query()->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'));

        return response()->json($query->orderBy('name')->paginate($request->integer('per_page', 15)));
    }

    public function store(UserRequest $request, AuditService $audit): JsonResponse
    {
        return $this->createUser($request->validated(), $audit);
    }

    public function storeConsultation(ConsultationUserRequest $request, AuditService $audit): JsonResponse
    {
        return $this->createUser([...$request->validated(), 'role' => 'consulta', 'ativo' => true], $audit);
    }

    private function createUser(array $data, AuditService $audit): JsonResponse
    {
        return DB::transaction(function () use ($data, $audit): JsonResponse {
            $user = User::create($data);
            $audit->record('criar', $user);

            return response()->json(['data' => $user], 201);
        });
    }

    public function update(UserRequest $request, User $user, AuditService $audit): JsonResponse
    {
        return DB::transaction(function () use ($request, $user, $audit): JsonResponse {
            if ($user->password_setup_required) {
                throw ValidationException::withMessages(['email' => 'A pessoa precisa definir a senha antes de editar este usuário.']);
            }
            $admins = User::where('role', 'admin')->where('ativo', true)->lockForUpdate()->get();
            $data = $request->validated();
            if ($user->id === $request->user()->id && (! $data['ativo'] || $data['role'] !== 'admin')) {
                throw ValidationException::withMessages(['role' => 'Você não pode remover seu próprio acesso administrativo.']);
            }
            if ($user->role === 'admin' && $user->ativo && $admins->count() <= 1 && (! $data['ativo'] || $data['role'] !== 'admin')) {
                throw ValidationException::withMessages(['role' => 'Mantenha pelo menos um administrador ativo.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $before = $user->attributesToArray();
            $user->update($data);
            if (! $user->ativo || isset($data['password'])) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $audit->record('atualizar', $user, $before);

            return response()->json(['data' => $user]);
        });
    }
}
