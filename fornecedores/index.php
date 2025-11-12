<?php
require_once '../config/conexao.php';
require_once '../_includes/table-styles.php';

/**
 * Função para validar CNPJ
 */
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

    $peso1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    $peso2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    $soma1 = $soma2 = 0;

    for ($i = 0; $i < 12; $i++) $soma1 += $cnpj[$i] * $peso1[$i];
    $resto = $soma1 % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;

    for ($i = 0; $i < 13; $i++) $soma2 += $cnpj[$i] * $peso2[$i];
    $resto = $soma2 % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    return ($cnpj[12] == $digito1 && $cnpj[13] == $digito2);
}

/**
 * Processa o formulário
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cnpj = trim($_POST['cnpj']);
    $telefone = trim($_POST['telefone']);
    $cidade = trim($_POST['cidade']);

    if (empty($nome) || empty($cnpj)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!validarCNPJ($cnpj)) {
        $erro = "CNPJ inválido! Verifique e tente novamente.";
    } else {
        try {
            $sql = "INSERT INTO fornecedores (nome, cnpj, telefone, cidade) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $cnpj, $telefone, $cidade]);
            header("Location: index.php?sucesso=Fornecedor cadastrado com sucesso!");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro ao cadastrar fornecedor: " . $e->getMessage();
        }
    }
}

$sql = "SELECT id, nome, cnpj, telefone, cidade FROM fornecedores ORDER BY id ASC";
$stmt = $pdo->query($sql);
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../_includes/header.php";
?>

<!-- Mensagem de sucesso -->
<?php
$sucesso_msg = $sucesso ?? ($_GET['sucesso'] ?? null);
if ($sucesso_msg): ?>
    <div class="bg-green-50 dark:bg-green-900/50 border-l-4 border-green-500 text-green-800 dark:text-green-100 p-4 mb-6 rounded shadow-sm flex items-center">
        <svg class="h-5 w-5 mr-3 text-green-500 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <p class="font-medium"><?= htmlspecialchars($sucesso_msg) ?></p>
    </div>
<?php endif; ?>

<!-- Mensagem de erro -->
<?php if (isset($erro)): ?>
    <div class="bg-red-50 dark:bg-red-900/50 border-l-4 border-red-500 text-red-800 dark:text-red-100 p-4 mb-6 rounded shadow-sm flex items-center">
        <svg class="h-5 w-5 mr-3 text-red-500 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="font-medium"><?= htmlspecialchars($erro) ?></p>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <!-- Formulário -->
    <div class="md:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-700 dark:to-blue-800 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">Cadastrar Novo Fornecedor</h2>
            </div>
            <div class="p-6">
                <form action="index.php" method="POST" class="space-y-4">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nome</label>
                        <input type="text" id="nome" name="nome" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="cnpj" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" required placeholder="00.000.000/0000-00"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Telefone</label>
                        <input type="text" id="telefone" name="telefone"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="cidade" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Cidade</label>
                        <input type="text" id="cidade" name="cidade"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2 px-4 rounded-md transition-all duration-300 transform hover:scale-[1.02]">
                        Cadastrar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-x-auto w-full">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-700 dark:to-purple-800 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">Lista de Fornecedores</h2>
            </div>
            <div class="p-6">
                <table class="w-full table-auto border-collapse text-sm text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-4 py-2 w-[40px]">ID</th>
                            <th class="px-4 py-2 w-[180px]">Nome</th>
                            <th class="px-4 py-2 w-[180px] whitespace-nowrap">CNPJ</th>
                            <th class="px-4 py-2 w-[140px] whitespace-nowrap">Telefone</th>
                            <th class="px-4 py-2 w-[160px]">Cidade</th>
                            <th class="px-4 py-2 text-right w-[180px]">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-2"><?= htmlspecialchars($fornecedor['id']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($fornecedor['nome']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap"><?= htmlspecialchars($fornecedor['cnpj']) ?></td>
                                <!-- Aqui: evita quebra de linha e aplica truncate se for maior que o espaço -->
                                <td class="px-4 py-2 max-w-[140px] truncate whitespace-nowrap">
                                    <?= htmlspecialchars($fornecedor['telefone']) ?>
                                </td>
                                <td class="px-4 py-2"><?= htmlspecialchars($fornecedor['cidade']) ?></td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="editar.php?id=<?= $fornecedor['id'] ?>"
                                           class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md transition">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414
                                                      a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Editar
                                        </a>
                                        <a href="excluir.php?id=<?= $fornecedor['id'] ?>"
                                           onclick="return confirm('Tem certeza que deseja excluir este fornecedor?')"
                                           class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                                      4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../_includes/footer.php'; ?>
