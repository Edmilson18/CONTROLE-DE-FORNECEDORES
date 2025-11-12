<?php
require_once '../config/conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM fornecedores WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}
// Redireciona com mensagem padronizada
header('Location: index.php?sucesso=' . urlencode('Registro excluído com sucesso!'));
exit;