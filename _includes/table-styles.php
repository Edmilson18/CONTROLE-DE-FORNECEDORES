<?php
// Classes comuns para tabelas no modo escuro
$tableClasses = [
    'container' => 'bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 transition-colors duration-200',
    'header' => [
        'wrapper' => 'flex justify-between items-center mb-6',
        'title' => 'text-xl font-semibold text-gray-800 dark:text-white',
        'button' => 'bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200'
    ],
    'table' => [
        'wrapper' => 'overflow-x-auto',
        'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
        'thead' => 'bg-gray-50 dark:bg-gray-700',
        'th' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider',
        'tbody' => 'bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700',
        'tr' => 'hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150',
        'td' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100',
        'td_actions' => 'px-6 py-4 whitespace-nowrap text-sm font-medium',
        'link_edit' => 'text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3 transition-colors duration-200',
        'link_delete' => 'text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200'
    ],
    'form' => [
        'group' => 'mb-4',
        'label' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1',
        'input' => 'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm transition-colors duration-200',
        'select' => 'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm transition-colors duration-200',
        'button' => 'bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200'
    ],
    'alert' => [
        'success' => 'bg-green-100 dark:bg-green-800 border-l-4 border-green-500 text-green-700 dark:text-green-100 p-4 mb-4',
        'error' => 'bg-red-100 dark:bg-red-800 border-l-4 border-red-500 text-red-700 dark:text-red-100 p-4 mb-4'
    ]
];
?>