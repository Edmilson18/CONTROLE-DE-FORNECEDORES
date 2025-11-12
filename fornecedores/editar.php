<?php
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $cnpj = $_POST['cnpj'];
    $telefone = $_POST['telefone'];
    $cidade = $_POST['cidade'];

    $sql = "UPDATE fornecedores SET nome = ?, cnpj = ?, telefone = ?, cidade = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nome, $cnpj, $telefone, $cidade, $id])) {
        header("Location: index.php?sucesso=Fornecedor atualizado com sucesso!");
        exit;
    } else {
        $erro = "Erro ao atualizar o fornecedor";
    }
}

$fornecedor = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql_fornecedor = "SELECT * FROM fornecedores WHERE id = ?";
    $stmt_fornecedor = $pdo->prepare($sql_fornecedor);
    $stmt_fornecedor->execute([$id]);    
    $fornecedor = $stmt_fornecedor->fetch();
}

require_once "../_includes/header.php";

if(!$fornecedor) {
    header("Location: index.php");
    exit;
}
?>

<div class="bg-gradient-to-r from-yellow-600 to-yellow-700 dark:from-yellow-800 dark:to-yellow-900 py-6 mb-6">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-white mb-2">Editar Fornecedor</h1>
        <p class="text-yellow-100">Atualize as informações do fornecedor</p>
    </div>
</div>

<?php if (isset($erro)): ?>
    <div class="bg-red-100 dark:bg-red-800 border-l-4 border-red-500 text-red-700 dark:text-red-100 p-4 mb-6 rounded shadow-sm">
        <p class="font-medium"><?= htmlspecialchars($erro) ?></p>
    </div>
<?php endif; ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <form action="editar.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" value="<?= htmlspecialchars($fornecedor['id']) ?>">
                
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($fornecedor['nome']) ?>" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CNPJ</label>
                    <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($fornecedor['cnpj']) ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                
                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($fornecedor['telefone']) ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                
                <div>
                    <label for="cidade" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($fornecedor['cidade']) ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>

                <div class="flex items-center space-x-4 pt-4">
                    <button type="submit"
                        class="flex-1 bg-gradient-to-r from-yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700 hover:from-yellow-600 hover:to-yellow-700 dark:hover:from-yellow-700 dark:hover:to-yellow-800 text-white font-semibold py-2 px-4 rounded-md transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Salvar Alterações
                    </button>
                    <a href="index.php"
                        class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 font-semibold py-2 px-4 rounded-md text-center transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once '../_includes/footer.php'; ?>

