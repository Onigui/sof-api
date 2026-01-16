<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\RelatorioRun;
use App\Models\User;
use App\Services\RelatorioFechamentoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class RelatorioFechamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_e_download_exigem_role(): void
    {
        $empresa = Empresa::factory()->create();
        $data = Carbon::parse('2025-01-10');
        $path = 'empresas/'.$empresa->id.'/relatorios/'.$data->toDateString().'/aprovadas.xlsx';

        $run = RelatorioRun::create([
            'empresa_id' => $empresa->id,
            'data_ref' => $data->toDateString(),
            'tipo' => RelatorioRun::TIPO_APROVADAS,
            'formato' => 'xlsx',
            'arquivo_path' => $path,
            'status' => RelatorioRun::STATUS_GERADO,
            'gerado_em' => now(),
        ]);

        Storage::fake('local');
        Storage::put($path, 'conteudo');

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'role' => User::ROLE_OPERADOR,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/relatorios/fechamento?data='.$data->format('d-m-Y'))
            ->assertForbidden();

        $this->get('/api/v1/relatorios/fechamento/'.$run->id.'/download')
            ->assertForbidden();
    }

    public function test_multi_tenant_nao_acessa_relatorio_de_outra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $data = Carbon::parse('2025-01-10');
        $path = 'empresas/'.$empresaB->id.'/relatorios/'.$data->toDateString().'/aprovadas.xlsx';

        $run = RelatorioRun::create([
            'empresa_id' => $empresaB->id,
            'data_ref' => $data->toDateString(),
            'tipo' => RelatorioRun::TIPO_APROVADAS,
            'formato' => 'xlsx',
            'arquivo_path' => $path,
            'status' => RelatorioRun::STATUS_GERADO,
            'gerado_em' => now(),
        ]);

        Storage::fake('local');
        Storage::put($path, 'conteudo');

        $user = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'role' => User::ROLE_ANALISTA,
        ]);

        Sanctum::actingAs($user);

        $this->get('/api/v1/relatorios/fechamento/'.$run->id.'/download')
            ->assertNotFound();
    }

    public function test_service_gera_relatorios_para_empresas(): void
    {
        Excel::fake();

        $empresa = Empresa::factory()->create();
        $data = '2025-01-10';

        $service = app(RelatorioFechamentoService::class);
        $service->gerar($data);

        $basePath = 'empresas/'.$empresa->id.'/relatorios/'.$data;

        Excel::assertStored($basePath.'/aprovadas.xlsx');
        Excel::assertStored($basePath.'/integradas.xlsx');

        $this->assertDatabaseHas('relatorio_runs', [
            'empresa_id' => $empresa->id,
            'data_ref' => $data,
            'tipo' => RelatorioRun::TIPO_APROVADAS,
            'status' => RelatorioRun::STATUS_GERADO,
        ]);

        $this->assertDatabaseHas('relatorio_runs', [
            'empresa_id' => $empresa->id,
            'data_ref' => $data,
            'tipo' => RelatorioRun::TIPO_INTEGRADAS,
            'status' => RelatorioRun::STATUS_GERADO,
        ]);
    }
}
