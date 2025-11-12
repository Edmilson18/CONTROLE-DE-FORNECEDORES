<?php
require_once '../config/conexao.php';

if(isset($_GET['id'])) {
    $id = $_GET['id'];

    // Primeiro exclui os pagamentos relacionados à compra
    $sql_pagamentos = "DELETE FROM pagamentos WHERE compra_id = ?";
    $stmt_pagamentos = $pdo->prepare($sql_pagamentos);
    $stmt_pagamentos->execute([$id]);

    // Depois exclui a compra
    $sql = "DELETE FROM compras WHERE id = ?";
    $stmt = $pdo->prepare($sql);   
    $stmt->execute([$id]);
}
// Redireciona com mensagem padronizada
header('Location: index.php?sucesso=' . urlencode('Registro excluído com sucesso!'));
exit;