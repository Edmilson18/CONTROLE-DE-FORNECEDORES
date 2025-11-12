<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Fornecedores</title>
    <link rel="shortcut icon" href="/img/favicon.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#3b82f6',
                            DEFAULT: '#2563eb',
                            dark: '#1d4ed8'
                        },
                        secondary: {
                            light: '#60a5fa',
                            DEFAULT: '#1e40af',
                            dark: '#1e3a8a'
                        },
                        accent: {
                            light: '#93c5fd',
                            DEFAULT: '#60a5fa',
                            dark: '#3b82f6'
                        }
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif']
                    },
                    boxShadow: {
                        'inner-light': 'inset 0 2px 4px 0 rgba(255, 255, 255, 0.06)',
                        'inner-dark': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.06)'
                    }
                }
            }
        }

        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');

            function updateIcons() {
                const isDark = document.documentElement.classList.contains('dark');
                sunIcon.classList.toggle('hidden', !isDark);
                moonIcon.classList.toggle('hidden', isDark);
            }

            updateIcons();

            darkModeToggle.addEventListener('click', function() {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
                updateIcons();
            });
        });
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-poppins text-gray-800 dark:text-gray-200 transition-colors duration-200 flex flex-col min-h-screen">

    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <span class="text-xl font-semibold text-primary-dark dark:text-primary-light transition-colors duration-200">
                        Sistema de Fornecedores
                    </span>
                </div>

                <!-- Todos os botões e links juntos -->
                <div class="flex items-center space-x-3">
                    <a href="/Controle%20de%20Fornecedores/contato.php" 
                       class="group relative px-3 py-2 text-primary-dark dark:text-primary-light hover:text-primary-light transition-colors">
                        Contato
                        <span class="absolute left-0 -bottom-0.5 h-0.5 w-0 bg-primary-dark dark:bg-primary-light group-hover:w-full transition-all"></span>
                    </a>

                    <a href="/Controle%20de%20Fornecedores/sobre.php" 
                       class="group relative px-3 py-2 text-primary-dark dark:text-primary-light hover:text-primary-light transition-colors">
                        Sobre
                        <span class="absolute left-0 -bottom-0.5 h-0.5 w-0 bg-primary-dark dark:bg-primary-light group-hover:w-full transition-all"></span>
                    </a>

                    <a href="\Controle de Fornecedores\" id="homeButton"
                       class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 
                       transition-all duration-200 shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-light dark:focus:ring-primary-dark"
                       title="Início">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-200 transform transition-transform duration-200" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75zM9 22V12h6v10" />
                        </svg>
                    </a>

                    <button id="darkModeToggle"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 
                        dark:hover:bg-gray-600 transition-all duration-200 shadow hover:shadow-md
                        focus:outline-none focus:ring-2 focus:ring-primary-light dark:focus:ring-primary-dark">
                        <svg id="sunIcon"
                            class="w-5 h-5 text-yellow-500 dark:text-yellow-300 hidden transform hover:rotate-45 transition-transform duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707
                                     m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg id="moonIcon"
                            class="w-5 h-5 text-gray-700 dark:text-gray-200 transform hover:-rotate-12 transition-transform duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646
                                     9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

<main class="flex-grow transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 py-6 dark:text-gray-100">
</body>
</html>
