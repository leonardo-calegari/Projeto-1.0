<?php
// ============================================================
// CONFIGURAÇÕES DE OBRIGATORIEDADE DOS CAMPOS
// Altere os valores abaixo e salve. Vale para todo o sistema.
// ============================================================

// CNPJ da empresa é obrigatório?  true = sim | false = não
const CNPJ_OBRIGATORIO = true;

// Campos da PESSOA que são obrigatórios.
// Opções: "cpf", "documento".
//   []                      -> nenhum é obrigatório
//   ["cpf"]                 -> só o CPF
//   ["documento"]           -> só o documento
//   ["cpf", "documento"]    -> os dois
const PESSOA_CAMPOS_OBRIGATORIOS = [];

function campoPessoaObrigatorio(string $campo): bool
{
    return in_array($campo, PESSOA_CAMPOS_OBRIGATORIOS, true);
}