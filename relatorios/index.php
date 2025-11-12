<?php
require_once '../config/conexao.php';
require_once '../_includes/table-styles.php';
require_once '../_includes/header.php';

/* =====================================================
   🔹 Estatísticas gerais
===================================================== */
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM fornecedores) as total_fornecedores,
    COUNT(DISTINCT c.id) as total_compras,
    COALESCE(SUM(c.valor), 0) as total_valor_compras,
    COALESCE(AVG(c.valor), 0) as media_valor_compras
FROM compras c";
$stmt_stats = $pdo->query($sql_stats);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

$total_fornecedores   = $stats['total_fornecedores'];
$total_compras        = $stats['total_compras'];
$total_valor_compras  = $stats['total_valor_compras'];
$media_valor_compras  = $stats['media_valor_compras'];

/* =====================================================
   🔹 Tipo de relatório selecionado
===================================================== */
$tipo_relatorio = $_GET['tipo_relatorio'] ?? 'geral';

/* =====================================================
   🔹 Consultas SQL específicas por tipo de relatório
===================================================== */
$stmt_fornecedor = $stmt_mes = $stmt_pendentes = $stmt_pagas = $stmt_nao_pagas = $stmt_mes_detalhado = null;

if (in_array($tipo_relatorio, ['geral', 'fornecedores'])) {
    $sql_fornecedor = "SELECT f.nome, COUNT(c.id) as total_compras, COALESCE(SUM(c.valor), 0) as valor_total 
                       FROM fornecedores f 
                       LEFT JOIN compras c ON f.id = c.fornecedores_id 
                       GROUP BY f.id, f.nome 
                       ORDER BY valor_total DESC";
    $stmt_fornecedor = $pdo->query($sql_fornecedor);
}

if (in_array($tipo_relatorio, ['geral', 'mensal'])) {
    $sql_mes = "SELECT 
                DATE_FORMAT(c.data, '%Y-%m') as mes,
                COUNT(c.id) as total_compras,
                SUM(CASE WHEN c.status = 'pago' THEN c.valor ELSE 0 END) as total_valor
                FROM compras c
                GROUP BY DATE_FORMAT(c.data, '%Y-%m')
                ORDER BY mes DESC";
    $stmt_mes = $pdo->query($sql_mes);

    $sql_mes_detalhado = "SELECT 
                          DATE_FORMAT(c.data, '%Y-%m') as mes,
                          f.nome as fornecedor,
                          c.valor,
                          c.status
                          FROM compras c
                          JOIN fornecedores f ON c.fornecedores_id = f.id
                          ORDER BY f.nome ASC, c.data DESC";
    $stmt_mes_detalhado = $pdo->query($sql_mes_detalhado);
}

if (in_array($tipo_relatorio, ['geral', 'status'])) {
    $sql_pendentes = "SELECT c.*, f.nome as fornecedor 
                      FROM compras c 
                      JOIN fornecedores f ON c.fornecedores_id = f.id 
                      WHERE c.status = 'pendente' 
                      ORDER BY c.data DESC";
    $stmt_pendentes = $pdo->query($sql_pendentes);

    $sql_pagas = "SELECT c.*, f.nome as fornecedor 
                  FROM compras c 
                  JOIN fornecedores f ON c.fornecedores_id = f.id 
                  WHERE c.status = 'pago' 
                  ORDER BY c.data DESC";
    $stmt_pagas = $pdo->query($sql_pagas);

    $sql_nao_pagas = "SELECT c.*, f.nome as fornecedor, p.id as pagamento_id
                      FROM compras c
                      JOIN fornecedores f ON c.fornecedores_id = f.id
                      LEFT JOIN pagamentos p ON c.id = p.compra_id
                      WHERE c.status = 'cancelado' OR (p.id IS NULL AND c.status != 'pago')
                      ORDER BY c.data DESC";
    $stmt_nao_pagas = $pdo->query($sql_nao_pagas);
}
?>

<!-- =====================================================
     🔹 Cabeçalho da página
===================================================== -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-800 dark:to-indigo-800 py-6 mb-6">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-white mb-2">Relatórios</h1>
        <p class="text-blue-100 dark:text-blue-200">Visualize estatísticas e informações detalhadas do sistema</p>
    </div>
</div>

<!-- =====================================================
     🔹 Seleção do tipo de relatório
===================================================== -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
    <form method="get" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Tipo de Relatório</label>
            <select name="tipo_relatorio" 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500"
                    onchange="this.form.submit()">
                <option value="geral" <?= $tipo_relatorio == 'geral' ? 'selected' : '' ?>>Relatório Geral</option>
                <option value="fornecedores" <?= $tipo_relatorio == 'fornecedores' ? 'selected' : '' ?>>Compras por Fornecedor</option>
                <option value="mensal" <?= $tipo_relatorio == 'mensal' ? 'selected' : '' ?>>Relatório Mensal</option>
                <option value="status" <?= $tipo_relatorio == 'status' ? 'selected' : '' ?>>Relatório por Status</option>
            </select>
        </div>
    </form>
</div>

<!-- =====================================================
     🔹 Conteúdo Dinâmico Centralizado
===================================================== -->
<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col items-center justify-center space-y-10 w-full">

        <!-- Estatísticas Gerais -->
        <?php if ($tipo_relatorio == 'geral'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-4xl">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 text-center">Estatísticas Gerais</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-center">
                        <p class="text-sm text-blue-600 dark:text-blue-300">Fornecedores</p>
                        <p class="text-2xl font-bold"><?= $total_fornecedores ?></p>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg text-center">
                        <p class="text-sm text-green-600 dark:text-green-300">Compras</p>
                        <p class="text-2xl font-bold"><?= $total_compras ?></p>
                    </div>
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-center col-span-2">
                        <p class="text-sm text-purple-600 dark:text-purple-300">Total em Compras</p>
                        <p class="text-2xl font-bold">R$ <?= number_format($total_valor_compras, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Compras por Fornecedor -->
        <?php if ($stmt_fornecedor): ?>
        <div class="<?= $tableClasses['container'] ?> w-full max-w-5xl mx-auto">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 px-6 py-4 rounded-t-lg">
                <h2 class="text-white text-xl font-semibold text-center">Compras por Fornecedor</h2>
            </div>
            <div class="<?= $tableClasses['table']['wrapper'] ?> overflow-x-auto">
                <table class="<?= $tableClasses['table']['table'] ?> mx-auto">
                    <thead class="<?= $tableClasses['table']['thead'] ?>">
                        <tr class="text-center">
                            <th class="<?= $tableClasses['table']['th'] ?>">Fornecedor</th>
                            <th class="<?= $tableClasses['table']['th'] ?>">Total de Compras</th>
                            <th class="<?= $tableClasses['table']['th'] ?>">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody class="<?= $tableClasses['table']['tbody'] ?>">
                        <?php while ($row = $stmt_fornecedor->fetch()): ?>
                        <tr class="<?= $tableClasses['table']['tr'] ?> text-center">
                            <td class="<?= $tableClasses['table']['td'] ?>"><?= htmlspecialchars($row['nome']) ?></td>
                            <td class="<?= $tableClasses['table']['td'] ?>"><?= $row['total_compras'] ?></td>
                            <td class="<?= $tableClasses['table']['td'] ?>">R$ <?= number_format($row['valor_total'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Relatório Mensal -->
        <?php if ($stmt_mes): ?>
        <div class="<?= $tableClasses['container'] ?> w-full max-w-5xl mx-auto">
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 px-6 py-4 rounded-t-lg">
                <h2 class="text-white text-xl font-semibold text-center">Relatório Mensal</h2>
            </div>
            <div class="<?= $tableClasses['table']['wrapper'] ?> overflow-x-auto">
                <table class="<?= $tableClasses['table']['table'] ?> mx-auto">
                    <thead class="<?= $tableClasses['table']['thead'] ?>">
                        <tr class="text-center">
                            <th class="<?= $tableClasses['table']['th'] ?>">Mês</th>
                            <th class="<?= $tableClasses['table']['th'] ?>">Compras</th>
                            <th class="<?= $tableClasses['table']['th'] ?>">Total Pago</th>
                        </tr>
                    </thead>
                    <tbody class="<?= $tableClasses['table']['tbody'] ?>">
                        <?php while ($row = $stmt_mes->fetch()): ?>
                        <tr class="<?= $tableClasses['table']['tr'] ?> text-center">
                            <td class="<?= $tableClasses['table']['td'] ?>"><?= date('m/Y', strtotime($row['mes'] . '-01')) ?></td>
                            <td class="<?= $tableClasses['table']['td'] ?>"><?= $row['total_compras'] ?></td>
                            <td class="<?= $tableClasses['table']['td'] ?>">R$ <?= number_format($row['total_valor'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Relatório por Status -->
        <?php if ($stmt_pendentes): ?>
        <div class="w-full max-w-6xl mx-auto space-y-8">
            <?php include '..\_includes\table-styles.php'; ?>
        </div>
        <?php endif; ?>

        <!-- Botões de Exportação -->
        <div class="flex gap-4 mt-8 w-full max-w-md mx-auto">
            <a href="export.php?format=pdf&tipo=<?= htmlspecialchars($tipo_relatorio) ?>" 
               target="_blank" 
               class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-center font-semibold py-3 px-4 rounded-lg shadow hover:scale-[1.02] transition">
                Gerar PDF
            </a>
            <a href="export.php?format=csv&tipo=<?= htmlspecialchars($tipo_relatorio) ?>" 
               class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white text-center font-semibold py-3 px-4 rounded-lg shadow hover:scale-[1.02] transition">
                Exportar CSV
            </a>
        </div>
    </div>
</div>

<?php require_once '../_includes/footer.php'; ?>
