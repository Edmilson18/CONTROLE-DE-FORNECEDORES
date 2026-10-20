<?php

/** Carrega as configurações locais sem sobrescrever variáveis do servidor. */
$caminhoEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
$configuracoesEnv = is_readable($caminhoEnv)
    ? parse_ini_file($caminhoEnv, false, INI_SCANNER_RAW)
    : [];
$configuracoesEnv = is_array($configuracoesEnv) ? $configuracoesEnv : [];

function obterConfiguracaoBanco(string $nome, string $padrao): string
{
    $valorAmbiente = getenv($nome);
    if ($valorAmbiente !== false && $valorAmbiente !== '') {
        return $valorAmbiente;
    }

    global $configuracoesEnv;

    return isset($configuracoesEnv[$nome]) && $configuracoesEnv[$nome] !== ''
        ? (string) $configuracoesEnv[$nome]
        : $padrao;
}

define('DB_HOST', obterConfiguracaoBanco('DB_HOST', '127.0.0.1'));
define('DB_PORT', obterConfiguracaoBanco('DB_PORT', '3307'));
define('DB_NAME', obterConfiguracaoBanco('DB_DATABASE', 'fornecedores'));
define('DB_USER', obterConfiguracaoBanco('DB_USERNAME', 'fornecedores'));
define('DB_PASS', obterConfiguracaoBanco('DB_PASSWORD', ''));

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Falha na conexão com o banco de dados: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Verifique a configuração do servidor.');
}
