<?php
require_once '../config/conexao.php';
require_once '../_includes/table-styles.php';

// ==========================
// INSERIR NOVA COMPRA
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'compra') {
    try {
        $pdo->beginTransaction();

        $valor = floatval(str_replace(',', '.', $_POST['valor']));
        $fornecedor_id = intval($_POST['fornecedor_id']);
        $status = $_POST['status'];
        $data = $_POST['data'] ?? date('Y-m-d');

        $sql = "INSERT INTO compras (valor, fornecedores_id, status, data) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$valor, $fornecedor_id, $status, $data]);

        $pdo->commit();
        $sucesso = "Compra cadastrada com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao cadastrar compra: " . $e->getMessage();
    }
}

// ==========================
// EXCLUIR COMPRA
// ==========================
if (isset($_GET['delete_id'])) {
    try {
        $id = intval($_GET['delete_id']);
        $stmt = $pdo->prepare("DELETE FROM compras WHERE id = ?");
        $stmt->execute([$id]);
        $sucesso = "Compra excluída com sucesso!";
    } catch (Exception $e) {
        $erro = "Erro ao excluir compra: " . $e->getMessage();
    }
}

// ==========================
// CONSULTAS
// ==========================
$lista_fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$sql_compras = "
    SELECT c.id, c.valor, c.data, c.status, f.nome AS fornecedor_nome 
    FROM compras c
    LEFT JOIN fornecedores f ON c.fornecedores_id = f.id
    ORDER BY c.id
";
$compras = $pdo->query($sql_compras)->fetchAll(PDO::FETCH_ASSOC);

require_once "../_includes/header.php";
?>

<div class="bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800 py-6 mb-6 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-white mb-2">Gerenciar Compras</h1>
        <p class="text-green-100 dark:text-green-200">Registre, edite e exclua compras</p>
    </div>
</div>

<?php if (!empty($sucesso)): ?>
    <div class="bg-green-50 dark:bg-green-900/50 border-l-4 border-green-500 text-green-800 dark:text-green-100 p-4 mb-6 rounded shadow-sm">
        <?= htmlspecialchars($sucesso) ?>
    </div>
<?php elseif (!empty($erro)): ?>
    <div class="bg-red-50 dark:bg-red-900/50 border-l-4 border-red-500 text-red-800 dark:text-red-100 p-4 mb-6 rounded shadow-sm">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <!-- FORMULÁRIO DE COMPRA -->
    <div class="md:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-colors duration-300">
            <div class="bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">Cadastrar Nova Compra</h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="tipo" value="compra">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Valor</label>
                        <input type="number" step="0.01" name="valor" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Fornecedor</label>
                        <select name="fornecedor_id" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            <option value="">Selecione</option>
                            <?php foreach ($lista_fornecedores as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Data</label>
                        <input type="date" name="data" value="<?= date('Y-m-d') ?>"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select name="status"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800 hover:from-green-700 hover:to-green-800 text-white font-semibold py-2 px-4 rounded-md transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Salvar Compra
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LISTA DE COMPRAS -->
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-colors duration-300">
            <div class="bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">Lista de Compras</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">ID</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Valor</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Data</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Fornecedor</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Status</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-200">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($compras)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">Nenhuma compra cadastrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $c): ?>
                                <?php
                                // Define as cores do status (iguais ao módulo de fornecedores)
                                switch ($c['status']) {
                                    case 'pago':
                                        $statusClass = 'bg-green-100 text-green-800 dark:bg-green-600 dark:text-white';
                                        break;
                                    case 'pendente':
                                        $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500 dark:text-gray-900';
                                        break;
                                    case 'cancelado':
                                        $statusClass = 'bg-red-100 text-red-800 dark:bg-red-600 dark:text-white';
                                        break;
                                    default:
                                        $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-white';
                                }
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100"><?= $c['id'] ?></td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100"><?= date('d/m/Y', strtotime($c['data'])) ?></td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100"><?= htmlspecialchars($c['fornecedor_nome'] ?? '-') ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            <a href="editar.php?id=<?= $c['id'] ?>"
                                                class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md transition">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414
                                                        a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Editar
                                            </a>
                                            <a href="excluir.php?id=<?= $c['id'] ?>"
                                                onclick="return confirm('Tem certeza que deseja excluir esta compra?')"
                                                class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                                        4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../_includes/footer.php'; ?>
