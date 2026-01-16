<?php

namespace App\Services;

use App\Exports\AprovadasExport;
use App\Exports\IntegradasExport;
use App\Models\Empresa;
use App\Models\RelatorioRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class RelatorioFechamentoService
{
    public function gerar(string $dataRef, ?int $createdBy = null): void
    {
        $data = Carbon::parse($dataRef)->toDateString();

        Empresa::query()->chunkById(100, function ($empresas) use ($data, $createdBy) {
            foreach ($empresas as $empresa) {
                $this->gerarParaEmpresa($empresa->id, $data, $createdBy);
            }
        });
    }

    private function gerarParaEmpresa(int $empresaId, string $data, ?int $createdBy): void
    {
        $basePath = "empresas/{$empresaId}/relatorios/{$data}";

        $this->gerarTipo(
            $empresaId,
            $data,
            RelatorioRun::TIPO_APROVADAS,
            new AprovadasExport($data, $empresaId),
            "{$basePath}/aprovadas.xlsx",
            $createdBy
        );

        $this->gerarTipo(
            $empresaId,
            $data,
            RelatorioRun::TIPO_INTEGRADAS,
            new IntegradasExport($data, $empresaId),
            "{$basePath}/integradas.xlsx",
            $createdBy
        );
    }

    private function gerarTipo(
        int $empresaId,
        string $data,
        string $tipo,
        object $export,
        string $path,
        ?int $createdBy
    ): void {
        try {
            Excel::store($export, $path);

            $this->registrarResultado($empresaId, $data, $tipo, $path, RelatorioRun::STATUS_GERADO, null, $createdBy);
        } catch (\Throwable $exception) {
            Log::error('Falha ao gerar relatorio', [
                'empresa_id' => $empresaId,
                'data_ref' => $data,
                'tipo' => $tipo,
                'erro' => $exception->getMessage(),
            ]);

            $this->registrarResultado(
                $empresaId,
                $data,
                $tipo,
                $path,
                RelatorioRun::STATUS_FALHOU,
                $exception->getMessage(),
                $createdBy
            );
        }
    }

    private function registrarResultado(
        int $empresaId,
        string $data,
        string $tipo,
        string $path,
        string $status,
        ?string $erro,
        ?int $createdBy
    ): void {
        RelatorioRun::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'data_ref' => $data,
                'tipo' => $tipo,
            ],
            [
                'formato' => 'xlsx',
                'arquivo_path' => $path,
                'status' => $status,
                'erro' => $erro,
                'gerado_em' => now(),
                'created_by' => $createdBy,
            ]
        );
    }
}
