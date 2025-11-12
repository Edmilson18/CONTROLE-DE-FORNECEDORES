<?php
require_once '_includes/header.php';
?>
<div class="text-center mb-8">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-800 dark:to-indigo-900 py-8 px-4 mb-8 transition-colors duration-200">
        <h1 class="text-4xl font-bold text-white mb-2">Sistema de Controle de Fornecedores</h1>
        <p class="text-blue-100 dark:text-blue-200">Gerencie seus fornecedores, compras e pagamentos de forma eficiente</p>
    </div>
</div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="compras/" class="group transform hover:scale-105 transition-all duration-300">
                    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-full w-16 h-16 flex items-center justify-center mb-4 mx-auto shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div class="flex-grow flex flex-col justify-between">
                            <h2 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2 transition-colors duration-200">Compras e Pagamentos</h2>
                            <p class="text-gray-600 dark:text-gray-300 text-center transition-colors duration-200">Gerencie compras e pagamentos</p>
                        </div>
                    </div>
                </a>

                <a href="fornecedores/" class="group transform hover:scale-105 transition-all duration-300">
                    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="p-4 bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-full w-16 h-16 flex items-center justify-center mb-4 mx-auto shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="flex-grow flex flex-col justify-between">
                            <h2 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2 transition-colors duration-200">Fornecedores</h2>
                            <p class="text-gray-600 dark:text-gray-300 text-center transition-colors duration-200">Gerencie seus fornecedores</p>
                        </div>
                    </div>
                </a>

                <a href="relatorios/" class="group transform hover:scale-105 transition-all duration-300">
                    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="p-4 bg-gradient-to-br from-yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700 rounded-full w-16 h-16 flex items-center justify-center mb-4 mx-auto shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-grow flex flex-col justify-between">
                            <h2 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2 transition-colors duration-200">Relatórios</h2>
                            <p class="text-gray-600 dark:text-gray-300 text-center transition-colors duration-200">Acesse os relatórios</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>
<?php
require_once '_includes/footer.php';
?>