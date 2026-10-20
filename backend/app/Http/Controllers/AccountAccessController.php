<?php

namespace App\Http\Controllers;

use App\Mail\AccountAccessDecision;
use App\Models\AccountAccessRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountAccessController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:100', 'max:2000'],
        ]);
        $email = Str::lower(trim($data['email']));

        $hasOpenRequest = AccountAccessRequest::query()
            ->where('email', $email)
            ->where(function ($query): void {
                $query->where('status', 'pending')
                    ->orWhere(fn ($approved) => $approved->where('status', 'approved')->where('setup_token_expires_at', '>', now()));
            })->exists();

        if (! User::where('email', $email)->exists() && ! $hasOpenRequest) {
            AccountAccessRequest::create([...$data, 'email' => $email, 'status' => 'pending']);
        }

        return response()->json([
            'message' => 'Se a solicitação puder ser registrada, o administrador a verá na área de solicitações. Você receberá uma resposta no e-mail informado.',
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,completed'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = AccountAccessRequest::query()
            ->with('reviewer:id,name')
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($requests);
    }

    public function decide(Request $request, AccountAccessRequest $accountAccessRequest): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'response_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $setupUrl = null;
        DB::transaction(function () use ($request, $accountAccessRequest, $data, &$setupUrl): void {
            $record = AccountAccessRequest::query()->lockForUpdate()->findOrFail($accountAccessRequest->id);
            if ($record->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Esta solicitação já foi analisada.']);
            }

            $plainToken = $data['decision'] === 'approved' ? Str::random(64) : null;
            $record->update([
                'status' => $data['decision'],
                'response_message' => $data['response_message'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'setup_token_hash' => $plainToken ? hash('sha256', $plainToken) : null,
                'setup_token_expires_at' => $plainToken ? now()->addHours(48) : null,
            ]);

            if ($plainToken) {
                $setupUrl = rtrim(config('app.frontend_url'), '/').'/ativar-conta?token='.urlencode($plainToken);
            }

            $this->sendDecision($record, $setupUrl);
        });

        return response()->json(['message' => 'Decisão registrada e e-mail enviado.']);
    }

    public function resend(AccountAccessRequest $accountAccessRequest): JsonResponse
    {
        $setupUrl = null;
        DB::transaction(function () use ($accountAccessRequest, &$setupUrl): void {
            $record = AccountAccessRequest::query()->lockForUpdate()->findOrFail($accountAccessRequest->id);
            if ($record->status !== 'approved' || User::where('email', $record->email)->where('password_setup_required', false)->exists()) {
                throw ValidationException::withMessages(['email' => 'Não há um acesso aprovado aguardando definição de senha.']);
            }

            $plainToken = Str::random(64);
            $record->update([
                'setup_token_hash' => hash('sha256', $plainToken),
                'setup_token_expires_at' => now()->addHours(48),
            ]);
            $setupUrl = rtrim(config('app.frontend_url'), '/').'/ativar-conta?token='.urlencode($plainToken);
            $this->sendDecision($record, $setupUrl);
        });

        return response()->json(['message' => 'Novo link enviado por e-mail.']);
    }

    private function sendDecision(AccountAccessRequest $record, ?string $setupUrl): void
    {
        if (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['email' => 'O envio de e-mail real ainda não está configurado. Configure SMTP em backend/.env e tente novamente.']);
        }

        try {
            Mail::to($record->email)->send(new AccountAccessDecision($record, $setupUrl));
        } catch (Throwable) {
            throw ValidationException::withMessages(['email' => 'Não foi possível enviar o e-mail. Confira as configurações SMTP em backend/.env e tente novamente.']);
        }
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);

        return DB::transaction(function () use ($request, $data): JsonResponse {
            $record = AccountAccessRequest::query()
                ->where('setup_token_hash', hash('sha256', $data['token']))
                ->lockForUpdate()
                ->first();

            if (! $record || $record->status !== 'approved' || ! $record->setup_token_expires_at?->isFuture()) {
                throw ValidationException::withMessages(['token' => 'Este link é inválido ou expirou. Solicite uma nova análise ao administrador.']);
            }
            $user = User::where('email', $record->email)->first();
            if ($user && ! $user->password_setup_required) {
                throw ValidationException::withMessages(['token' => 'Já existe uma conta ativa para este e-mail. Entre em contato com o administrador.']);
            }

            $user ??= User::create([
                    'name' => $record->name,
                    'email' => $record->email,
                    'password' => Str::random(64),
                    'role' => 'operador',
                    'ativo' => true,
                    'password_setup_required' => true,
                ]);

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json(['data' => $user]);
        });
    }
}
