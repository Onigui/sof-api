<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropostaRequest;
use App\Http\Requests\UpdatePropostaRequest;
use App\Models\Proposta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json([
            'data' => $proposta,
        ], 201);
    }

    public function show(Proposta $proposta): JsonResponse
    {
        $this->authorize('view', $proposta);

        return response()->json([
            'data' => $proposta,
        ]);
    }

    public function update(UpdatePropostaRequest $request, Proposta $proposta): JsonResponse
    {
        $this->authorize('update', $proposta);

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

        return response()->json([
            'data' => $proposta,
        ]);
    }
}
