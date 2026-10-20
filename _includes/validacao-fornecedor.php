<?php

function validarCNPJ(string $cnpj): bool
{
    $cnpj = strtoupper(trim($cnpj));

    $formatoNumerico = '/^(?:[0-9]{14}|[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2})$/';
    $formatoAlfanumerico = '/^(?:[A-Z0-9]{12}[0-9]{2}|[A-Z0-9]{2}\.[A-Z0-9]{3}\.[A-Z0-9]{3}\/[A-Z0-9]{4}-[0-9]{2})$/';

    if (!preg_match($formatoNumerico, $cnpj) && !preg_match($formatoAlfanumerico, $cnpj)) {
        return false;
    }

    $cnpj = preg_replace('/[.\/-]/', '', $cnpj) ?? '';

    if (preg_match('/^([A-Z0-9])\1{13}$/', $cnpj)) {
        return false;
    }

    $pesos = [2, 3, 4, 5, 6, 7, 8, 9];
    $calcularDigito = static function (string $base) use ($pesos): int {
        $soma = 0;
        $posicaoPeso = 0;

        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $valor = ord($base[$i]) - 48;
            $soma += $valor * $pesos[$posicaoPeso];
            $posicaoPeso = ($posicaoPeso + 1) % count($pesos);
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    };

    $base = substr($cnpj, 0, 12);
    $primeiroDigito = $calcularDigito($base);
    $segundoDigito = $calcularDigito($base . $primeiroDigito);

    return (int) $cnpj[12] === $primeiroDigito
        && (int) $cnpj[13] === $segundoDigito;
}

function formatarCNPJ(string $cnpj): string
{
    $cnpjOriginal = strtoupper(trim($cnpj));
    $cnpjLimpo = preg_replace('/[.\/-]/', '', $cnpjOriginal) ?? '';

    if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $cnpjLimpo)) {
        return $cnpjOriginal;
    }

    return substr($cnpjLimpo, 0, 2) . '.'
        . substr($cnpjLimpo, 2, 3) . '.'
        . substr($cnpjLimpo, 5, 3) . '/'
        . substr($cnpjLimpo, 8, 4) . '-'
        . substr($cnpjLimpo, 12, 2);
}

function formatarTelefone(string $telefone): string
{
    $telefoneOriginal = trim($telefone);
    $digitos = preg_replace('/\D/', '', $telefoneOriginal) ?? '';

    if (!in_array(strlen($digitos), [10, 11], true)) {
        return $telefoneOriginal;
    }

    $quantidadeNumeroLocal = strlen($digitos) === 11 ? 5 : 4;

    return '(' . substr($digitos, 0, 2) . ') '
        . substr($digitos, 2, $quantidadeNumeroLocal) . '-'
        . substr($digitos, 2 + $quantidadeNumeroLocal);
}

function validarTelefone(string $telefone): bool
{
    $telefone = trim($telefone);

    if ($telefone === '') {
        return true;
    }

    if (!preg_match('/^[0-9()+\s.-]+$/', $telefone)) {
        return false;
    }

    $digitos = preg_replace('/\D/', '', $telefone) ?? '';

    return in_array(strlen($digitos), [10, 11], true);
}
