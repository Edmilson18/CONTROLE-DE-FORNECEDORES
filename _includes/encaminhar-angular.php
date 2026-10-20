<?php

$script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$section = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$destination = match ($section) {
    'fornecedores' => '/fornecedores',
    'compras' => '/compras',
    'relatorios' => '/relatorios',
    default => '/',
};

if ($section === 'compras' && $script === 'editar.php') {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false && $id !== null) {
        $destination = '/compras/'.$id;
    }
}

header('Location: http://127.0.0.1:4200'.$destination, true, 302);
exit;
