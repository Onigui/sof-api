<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropostaRequest;
use App\Http\Requests\UpdatePropostaRequest;
use App\Models\Proposta;
use App\Models\User;
use App\Services\Audit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropostaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Proposta::query();

        $query->when($request->string('status')->toString(), function ($builder, $status) {
            $builder->where('status', $status);
        });

        $query->when($request->integer('operador_id'), function ($builder, $operadorId) {
            $builder->where('operador_id', $operadorId);
        });

        $query->when($request->integer('loja_id'), function ($builder, $lojaId) {
            $builder->where('loja_id', $lojaId);
        });

        $query->when($request->integer('regiao_id'), function ($builder, $regiaoId) {
            $builder->where('regiao_id', $regiaoId);
        });

        $query->when($request->boolean('pendencia'), function ($builder) {
            $builder->whereHas('pendencias', function ($pendencias) {
                $pendencias->where('status', \App\Models\Pendencia::STATUS_ABERTA);
            });
        });

        if ($request->filled('data_de')) {
            $dataDe = Carbon::parse($request->input('data_de'))->startOfDay();
            $query->where('created_at', '>=', $dataDe);
        }

        if ($request->filled('data_ate')) {
            $dataAte = Carbon::parse($request->input('data_ate'))->endOfDay();
            $query->where('created_at', '<=', $dataAte);
        }

        return response()->json([
            'data' => $query->latest()->paginate(),
        ]);
    }

    public function store(StorePropostaRequest $request): JsonResponse
    {
        $user = $request->user();

        $proposta = Proposta::create(array_merge($request->validated(), [
            'empresa_id' => $user->empresa_id,
            'operador_id' => $user->id,
            'status' => Proposta::STATUS_RASCUNHO,
            'prioridade' => Proposta::PRIORIDADE_NORMAL,
        ]));

        Audit::log(
            'PROPOSTA_CRIADA',
            Proposta::class,
            (string) $proposta->id,
            [],
            $user,
            $request
        );

        return response()->json([
            'data' => $proposta,
        ], 201);
    }

    public function show(Proposta $proposta): JsonResponse
    {
        $this->authorize('view', $proposta);

        return response()->json([
            'data' => $proposta->load(['documentos', 'pendencias']),
            'data' => $proposta,
        ]);
    }

    public function update(UpdatePropostaRequest $request, Proposta $proposta): JsonResponse
    {
        $this->authorize('update', $proposta);

        $validated = $request->validated();

        $proposta->update($validated);

        Audit::log(
            'PROPOSTA_ATUALIZADA',
            Proposta::class,
            (string) $proposta->id,
            [
                'campos' => array_keys($validated),
            ],
            $request->user(),
            $request
        );
        $proposta->update($request->validated());

        return response()->json([
            'data' => $proposta,
        ]);
    }

    public function enviar(Request $request, Proposta $proposta): JsonResponse
    {
        $this->authorize('enviar', $proposta);

        $proposta->update([
            'status' => Proposta::STATUS_ANALISE_PROMOTORA,
            'enviada_em' => now(),
        ]);

        Audit::log(
            'PROPOSTA_ENVIADA',
            Proposta::class,
            (string) $proposta->id,
            [],
            $request->user(),
            $request
        );

        return response()->json([
            'data' => $proposta,
        ]);
    }

    public function transferir(Request $request, Proposta $proposta): JsonResponse
    {
        $this->authorize('transferir', $proposta);

        $user = $request->user();

        $validated = $request->validate([
            'novo_operador_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('empresa_id', $user->empresa_id)
                    ->where('role', User::ROLE_OPERADOR),
            ],
            'motivo' => ['required', 'string'],
        ]);

        $operadorAnterior = $proposta->operador_id;
        $proposta->update([
            'operador_id' => $validated['novo_operador_id'],
        ]);

        Audit::log(
            'PROPOSTA_TRANSFERIDA',
            'proposta',
            (string) $proposta->id,
            [
                'from' => $operadorAnterior,
                'to' => $validated['novo_operador_id'],
                'motivo' => $validated['motivo'],
            ],
            $user,
            $request
        );

        return response()->json([
            'data' => $proposta,
        ]);
    }

    public function ajustarStatus(Request $request, Proposta $proposta): JsonResponse
    {
        $this->authorize('ajustarStatus', $proposta);

        $validated = $request->validate([
            'status_novo' => ['required', 'string'],
            'motivo' => ['required', 'string'],
        ]);

        $novoStatus = $validated['status_novo'];
        $statusAtual = $proposta->status;

        if ($novoStatus === Proposta::STATUS_INTEGRADA) {
            return response()->json([
                'message' => 'Status INTEGRADA só pode ser definido pela integração.',
            ], 422);
        }

        $allowUnintegrate = filter_var(env('ALLOW_ADMIN_UNINTEGRATE', false), FILTER_VALIDATE_BOOL);
        if ($statusAtual === Proposta::STATUS_INTEGRADA && !$allowUnintegrate) {
            return response()->json([
                'message' => 'Propostas integradas não podem ter o status ajustado.',
            ], 422);
        }

        $allowed = $this->allowedStatusTransitions($statusAtual, $allowUnintegrate);
        if (!in_array($novoStatus, $allowed, true)) {
            return response()->json([
                'message' => 'Transição de status não permitida.',
            ], 422);
        }

        $proposta->update([
            'status' => $novoStatus,
        ]);

        $metadata = [
            'from' => $statusAtual,
            'to' => $novoStatus,
            'motivo' => $validated['motivo'],
        ];

        if ($statusAtual === Proposta::STATUS_INTEGRADA && $allowUnintegrate) {
            $metadata['override'] = true;
            $metadata['warning'] = 'ADMIN_UNINTEGRATE';
        }

        Audit::log(
            'PROPOSTA_STATUS_AJUSTADO',
            'proposta',
            (string) $proposta->id,
            $metadata,
            $request->user(),
            $request
        );

        return response()->json([
            'data' => $proposta,
        ]);
    }

    private function allowedStatusTransitions(string $statusAtual, bool $allowUnintegrate): array
    {
        $transitions = [
            Proposta::STATUS_RASCUNHO => [
                Proposta::STATUS_ANALISE_PROMOTORA,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_ANALISE_PROMOTORA => [
                Proposta::STATUS_ANALISE_BANCO,
                Proposta::STATUS_APROVADA,
                Proposta::STATUS_RECUSADA,
                Proposta::STATUS_FORMALIZACAO,
                Proposta::STATUS_ANALISE_PAGAMENTO,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_ANALISE_BANCO => [
                Proposta::STATUS_APROVADA,
                Proposta::STATUS_RECUSADA,
                Proposta::STATUS_FORMALIZACAO,
                Proposta::STATUS_ANALISE_PAGAMENTO,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_APROVADA => [
                Proposta::STATUS_FORMALIZACAO,
                Proposta::STATUS_ANALISE_PAGAMENTO,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_FORMALIZACAO => [
                Proposta::STATUS_ANALISE_PAGAMENTO,
                Proposta::STATUS_APROVADA,
                Proposta::STATUS_RECUSADA,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_ANALISE_PAGAMENTO => [
                Proposta::STATUS_APROVADA,
                Proposta::STATUS_RECUSADA,
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_RECUSADA => [
                Proposta::STATUS_CANCELADA,
            ],
            Proposta::STATUS_CANCELADA => [],
            Proposta::STATUS_INTEGRADA => $allowUnintegrate ? [
                Proposta::STATUS_RASCUNHO,
                Proposta::STATUS_ANALISE_PROMOTORA,
                Proposta::STATUS_ANALISE_BANCO,
                Proposta::STATUS_APROVADA,
                Proposta::STATUS_RECUSADA,
                Proposta::STATUS_FORMALIZACAO,
                Proposta::STATUS_ANALISE_PAGAMENTO,
                Proposta::STATUS_CANCELADA,
            ] : [],
        ];

        return $transitions[$statusAtual] ?? [];
    }
}
