<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropostaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cliente_nome' => ['sometimes', 'string', 'max:255'],
            'cliente_cpf' => ['sometimes', 'string', 'max:20'],
            'cliente_celular' => ['sometimes', 'string', 'max:20'],
            'cliente_email' => ['nullable', 'email', 'max:255'],
            'produto_id' => ['sometimes', 'integer', 'exists:produtos,id'],
            'loja_id' => ['nullable', 'integer', 'exists:lojas,id'],
            'regiao_raw' => ['sometimes', 'string', 'max:255'],
            'regiao_id' => ['nullable', 'integer', 'exists:regioes,id'],
            'banco_id' => ['nullable', 'integer', 'exists:bancos,id'],
            'pv' => ['nullable', 'string', 'max:255'],
            'veiculo_placa' => ['nullable', 'string', 'max:20'],
            'veiculo_renavam' => ['nullable', 'string', 'max:30'],
            'veiculo_descricao' => ['nullable', 'string', 'max:255'],
            'valor_veiculo' => ['nullable', 'numeric'],
            'valor_financiado' => ['nullable', 'numeric'],
        ];
    }
}
