<?php
require_once '_includes/header.php';
?>
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h1 class="text-2xl font-semibold mb-4">Contato</h1>
    <p class="text-gray-600 dark:text-gray-300 mb-6">Use o formulário abaixo para entrar em contato conosco. Responderemos o mais breve possível.</p>

    <form action="#" method="post" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nome</label>
            <input type="text" name="nome" class="mt-1 block w-full rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 p-2" placeholder="Seu nome">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">E-mail</label>
            <input type="email" name="email" class="mt-1 block w-full rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 p-2" placeholder="seu@exemplo.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Mensagem</label>
            <textarea name="mensagem" rows="5" class="mt-1 block w-full rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 p-2" placeholder="Escreva sua mensagem aqui..."></textarea>
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="px-4 py-2 bg-primary-dark text-white rounded hover:bg-primary-light transition">Enviar</button>
            <a href="index.php" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition">Voltar</a>
        </div>
    </form>
</div>

<?php
require_once '_includes/footer.php';
?>
