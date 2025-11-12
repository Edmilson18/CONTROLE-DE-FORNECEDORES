<?php
require_once '../config/conexao.php';
$compra = null;

// Buscar lista de fornecedores
$lista_fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT c.*, f.nome as fornecedor_nome 
            FROM compras c 
            LEFT JOIN fornecedores f ON c.fornecedores_id = f.id 
            WHERE c.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $id = $_POST["id"];
    $valor = $_POST["valor"];
    $fornecedor_id = $_POST["fornecedor_id"];
    $status = isset($_POST["status"]) ? $_POST["status"] : 'pendente';
    $data = $_POST["data"]; // <-- nova variável para a data

    $sql = "UPDATE compras 
            SET valor = ?, fornecedores_id = ?, status = ?, data = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$valor, $fornecedor_id, $status, $data, $id])) {
        header("Location: index.php?sucesso=Compra atualizada com sucesso!");
        exit;
    } else {
        $erro = "Erro ao atualizar a compra";
    }
}

if (!$compra) {
    header("Location: index.php");
    exit;
}

require_once '../_includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-800 dark:to-indigo-800 py-6 mb-6">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-white mb-2">Editar Compra</h1>
        <p class="text-blue-100">Atualize as informações da compra</p>
    </div>
</div>

<?php if (isset($erro)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
        <p class="font-medium"><?= htmlspecialchars($erro) ?></p>
    </div>
<?php endif;?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-700 dark:to-blue-800 px-6 py-4">
        <h2 class="text-white text-xl font-semibold">Dados da Compra</h2>
    </div>
    <div class="p-6">
        <form action="editar.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= htmlspecialchars($compra['id'])?>">

            <div>
                <label for="valor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">R$</span>
                    <input type="number" step="0.01" id="valor" name="valor" 
                        value="<?= htmlspecialchars($compra['valor'])?>" required
                        class="w-full pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div>
                <label for="fornecedor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fornecedor</label>
                <select id="fornecedor_id" name="fornecedor_id" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">Selecione um Fornecedor</option>
                    <?php foreach ($lista_fornecedores as $fornecedor): ?>
                        <option value="<?= htmlspecialchars($fornecedor['id']) ?>" 
                            <?= ($fornecedor['id'] == $compra['fornecedores_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fornecedor['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="data" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data da Compra</label>
                <input type="date" id="data" name="data" required
                    value="<?= htmlspecialchars(date('Y-m-d', strtotime($compra['data']))) ?>"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select id="status" name="status" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="pendente" <?= ($compra['status'] === 'pendente') ? 'selected' : '' ?>>Pendente</option>
                    <option value="pago" <?= ($compra['status'] === 'pago') ? 'selected' : '' ?>>Pago</option>
                    <option value="cancelado" <?= ($compra['status'] === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="flex gap-2 pt-4">
                <button type="submit" 
                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 hover:from-blue-700 hover:to-blue-800 dark:hover:from-blue-800 dark:hover:to-blue-900 text-white font-semibold py-2 px-4 rounded-md transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Salvar Alterações
                </button>
                <a href="index.php" 
                    class="flex-1 text-center bg-gray-500 dark:bg-gray-600 hover:bg-gray-600 dark:hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-md transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../_includes/footer.php'; ?>
