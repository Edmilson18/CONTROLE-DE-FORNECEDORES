<?php
require_once '../config/conexao.php';

$format = strtolower($_GET['format'] ?? 'pdf');

// Função auxiliar para formatar valores para Excel/Calc
function formatExcelValue($value, $type = 'text') {
    if ($type === 'date') {
        return date('d/m/Y', strtotime($value));
    } elseif ($type === 'number') {
        return str_replace('.', ',', number_format($value, 2, '.', ''));
    }
    return $value;
}

// Reutilizar queries principais
$sql_fornecedor = "SELECT f.nome, COUNT(c.id) as total_compras, SUM(c.valor) as valor_total 
                   FROM fornecedores f 
                   LEFT JOIN compras c ON f.id = c.fornecedores_id 
                   GROUP BY f.id, f.nome ";

$sql_pendentes = "SELECT c.*, f.nome as fornecedor 
                  FROM compras c 
                  JOIN fornecedores f ON c.fornecedores_id = f.id 
                  WHERE c.status = 'pendente' ";

$sql_pagas = "SELECT c.*, f.nome as fornecedor 
              FROM compras c 
              JOIN fornecedores f ON c.fornecedores_id = f.id 
              WHERE c.status = 'pago' ";

$sql_mes = "SELECT 
            DATE_FORMAT(c.data, '%Y-%m') as mes,
            COUNT(c.id) as total_compras,
            SUM(CASE WHEN c.status = 'pago' THEN c.valor ELSE 0 END) as total_valor
            FROM compras c
            GROUP BY DATE_FORMAT(c.data, '%Y-%m')
            ORDER BY mes DESC";

$sql_nao_pagas = "SELECT c.*, f.nome as fornecedor, p.id as pagamento_id
                  FROM compras c
                  JOIN fornecedores f ON c.fornecedores_id = f.id
                  LEFT JOIN pagamentos p ON c.id = p.compra_id
                  WHERE c.status = 'cancelado' OR (p.id IS NULL AND c.status != 'pago')
                  ORDER BY c.data DESC";

$sql_mes_detalhado = "SELECT 
                      DATE_FORMAT(c.data, '%Y-%m') as mes,
                      f.nome as fornecedor,
                      c.valor,
                      c.status,
                      c.data
                      FROM compras c
                      JOIN fornecedores f ON c.fornecedores_id = f.id
                      ORDER BY f.nome ASC, c.data DESC";

$data = [];
$data['fornecedor'] = $pdo->query($sql_fornecedor)->fetchAll(PDO::FETCH_ASSOC);
$data['pendentes'] = $pdo->query($sql_pendentes)->fetchAll(PDO::FETCH_ASSOC);
$data['pagas'] = $pdo->query($sql_pagas)->fetchAll(PDO::FETCH_ASSOC);
$data['mes'] = $pdo->query($sql_mes)->fetchAll(PDO::FETCH_ASSOC);
$data['nao_pagas'] = $pdo->query($sql_nao_pagas)->fetchAll(PDO::FETCH_ASSOC);
$data['mes_detalhado'] = $pdo->query($sql_mes_detalhado)->fetchAll(PDO::FETCH_ASSOC);

// Tipo de relatório (geral|fornecedores|mensal|status)
$tipo = $_GET['tipo'] ?? 'geral';

// Função para decidir se mostramos uma seção
$should_show = function($section) use ($tipo) {
    if ($tipo === 'geral') return true;
    if ($tipo === 'fornecedores') return $section === 'fornecedor';
    if ($tipo === 'mensal') return in_array($section, ['mes','mes_detalhado']);
    if ($tipo === 'status') return in_array($section, ['pendentes','pagas','nao_pagas']);
    return true;
};

// Gradientes por seção (usado no PDF)
$headerGradients = [
    'fornecedor' => "background: linear-gradient(to right, #3B82F6, #4F46E5); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
    'mes' => "background: linear-gradient(to right, #6366F1, #4F46E5); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
    'mes_detalhado' => "background: linear-gradient(to right, #7C3AED, #6D28D9); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
    'pendentes' => "background: linear-gradient(to right, #F59E0B, #D97706); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
    'pagas' => "background: linear-gradient(to right, #10B981, #059669); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
    'nao_pagas' => "background: linear-gradient(to right, #EF4444, #DC2626); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; margin:0;",
];

// Título da página conforme tipo
$pageTitles = [
    'geral' => 'Relatório Completo',
    'fornecedores' => 'Relatório - Compras por Fornecedor',
    'mensal' => 'Relatório Mensal',
    'status' => 'Relatório por Status'
];
$pageTitle = $pageTitles[$tipo] ?? $pageTitles['geral'];

if ($format === 'csv') {
    // Gerar CSV com várias seções - configurado para Excel/Calc
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="relatorios.xlsx"');
    // BOM para Excel reconhecer UTF-8
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    // Função auxiliar para imprimir seção (aceita columns como ['Header'=>'key'] ou ['Header', ...])
    $print_section = function($title, $columns, $rows) use ($out) {
        // normalize columns into headers and keys
        $headers = [];
        $keys = [];
        foreach ($columns as $k => $v) {
            if (is_int($k)) {
                // numeric index: header and infer key
                $headers[] = $v;
                $keys[] = strtolower(str_replace(' ', '_', $v));
            } else {
                // associative: header => key
                $headers[] = $k;
                $keys[] = $v;
            }
        }

        fputcsv($out, [$title], ';');
        fputcsv($out, $headers, ';');
        foreach ($rows as $r) {
            $line = [];
            foreach ($keys as $key) {
                $line[] = $r[$key] ?? '';
            }
            fputcsv($out, $line, ';');
        }
        fputcsv($out, [], ';');
    };

    // Fornecedor
    if (($should_show)('fornecedor')) {
        $print_section('Compras por Fornecedor', ['Fornecedor'=>'nome','Total de Compras'=>'total_compras','Valor Total'=>'valor_total'], array_map(function($r){
            return [
                'nome' => $r['nome'],
                'total_compras' => $r['total_compras'],
                'valor_total' => formatExcelValue($r['valor_total'], 'number')
            ];
        }, $data['fornecedor']));
    }

    // Pendentes
    if (($should_show)('pendentes')) {
        $print_section('Compras Pendentes', ['Data'=>'data','Fornecedor'=>'fornecedor','Valor'=>'valor'], array_map(function($r){
            return [
                'data' => formatExcelValue($r['data'], 'date'),
                'fornecedor' => $r['fornecedor'],
                'valor' => formatExcelValue($r['valor'], 'number')
            ];
        }, $data['pendentes']));
    }

    // Pagas
    if (($should_show)('pagas')) {
        $print_section('Compras Pagas', ['Data'=>'data','Fornecedor'=>'fornecedor','Valor'=>'valor'], array_map(function($r){
            return [
                'data' => formatExcelValue($r['data'], 'date'),
                'fornecedor' => $r['fornecedor'],
                'valor' => formatExcelValue($r['valor'], 'number')
            ];
        }, $data['pagas']));
    }

    // Total por mês
    if (($should_show)('mes')) {
        $print_section('Total de Compras (por mês)', ['Mês'=>'mes','Total de Compras'=>'total_compras','Valor Total'=>'total_valor'], array_map(function($r){
            return [
                'mes' => date('m/Y', strtotime($r['mes'].'-01')),
                'total_compras' => $r['total_compras'],
                'total_valor' => formatExcelValue($r['total_valor'], 'number')
            ];
        }, $data['mes']));
    }

    // Não pagas
    if (($should_show)('nao_pagas')) {
        $print_section('Compras Não Pagas (inclui canceladas)', ['Data'=>'data','Fornecedor'=>'fornecedor','Valor'=>'valor','Status'=>'status'], array_map(function($r){
            return [
                'data' => formatExcelValue($r['data'], 'date'),
                'fornecedor' => $r['fornecedor'],
                'valor' => formatExcelValue($r['valor'], 'number'),
                'status' => ucfirst($r['status'])
            ];
        }, $data['nao_pagas']));
    }

    // Detalhado
    if (($should_show)('mes_detalhado')) {
        $print_section('Compras por Mês (Detalhado)', ['Mês'=>'mes','Fornecedor'=>'fornecedor','Valor'=>'valor','Status'=>'status'], array_map(function($r){
            return [
                'mes' => date('m/Y', strtotime($r['mes'] . '-01')),
                'fornecedor' => $r['fornecedor'],
                'valor' => formatExcelValue($r['valor'], 'number'),
                'status' => ucfirst($r['status'])
            ];
        }, $data['mes_detalhado']));
    }

    fclose($out);
    exit;
}

// PDF -> gerar página HTML pronta para impressão (o usuário salva como PDF pelo navegador)
?><!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Relatórios - PDF</title>
    <link rel="shortcut icon" href="/CrudF/favicon.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos Globais */
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Estilos para Impressão */
        @media print {
            body {
                font-family: 'Poppins', sans-serif;
                color: #111827;
                background: white;
            }
            .section { 
                margin-bottom: 40px;
                break-inside: avoid;
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .page-break { 
                page-break-after: always; 
            }
            .section h2 {
                background: linear-gradient(to right, #3B82F6, #4F46E5);
                color: white;
                padding: 1rem 1.5rem;
                font-size: 1.25rem;
                font-weight: 600;
                margin: 0;
            }
            table { 
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin: 0;
            }
            th, td { 
                border: 1px solid #E5E7EB;
                padding: 0.75rem 1rem;
                text-align: left;
                font-size: 0.875rem;
            }
            th { 
                background: #F8FAFC;
                font-weight: 600;
                color: #374151;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                border-bottom: 2px solid #E5E7EB;
            }
            tbody tr:hover {
                background-color: #F8FAFC;
            }
            td {
                background: white;
            }
            .overflow-x-auto {
                padding: 1rem;
                background: white;
            }
            tr:nth-child(even) {
                background-color: #F9FAFB;
            }
            h1 {
                font-size: 2rem;
                font-weight: bold;
                color: #1F2937;
                margin-bottom: 1.5rem;
            }
            h2 {
                font-size: 1.5rem;
                font-weight: 600;
                color: #374151;
                margin: 1.5rem 0 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 2px solid #E5E7EB;
            }
            .status-badge {
                padding: 0.375rem 0.875rem;
                border-radius: 9999px;
                font-weight: 600;
                font-size: 0.75rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .status-pendente {
                background-color: #FEF3C7;
                color: #92400E;
                border: 1px solid #F59E0B;
            }
            .status-pago {
                background-color: #D1FAE5;
                color: #065F46;
                border: 1px solid #10B981;
            }
            .status-cancelado {
                background-color: #FEE2E2;
                color: #991B1B;
                border: 1px solid #EF4444;
            }
            .valor {
                font-weight: 600;
                color: #374151;
            }
            .data {
                color: #6B7280;
            }
            @page {
                margin: 2cm;
            }
        }
        @media screen {
            body {
                font-family: 'Poppins', sans-serif;
                max-width: 1200px;
                margin: 2rem auto;
                padding: 0 1rem;
                background: #F3F4F6;
            }
            .content-wrapper {
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            .section {
                transition: all 0.3s ease;
            }
            .section:hover {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                transform: translateY(-2px);
            }
            table {
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
                border-radius: 0.5rem;
                overflow: hidden;
            }
            th {
                background: linear-gradient(to right, #F8FAFC, #F1F5F9);
            }
            tr:hover td {
                background-color: #F8FAFC;
            }
            .status-badge {
                transition: all 0.3s ease;
            }
            .status-badge:hover {
                transform: scale(1.05);
            }
        }
    </style>
    <script>
        function triggerPrint(){
            window.print();
        }
        window.onload = function(){
            setTimeout(triggerPrint, 300);
        }
    </script>
</head>
<body>
    <div class="content-wrapper">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-gray-600">Gerado em <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="flex gap-4">
                <a href="export.php?format=pdf&tipo=<?= urlencode($tipo) ?>" target="_blank" 
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-300 transform hover:scale-[1.02]">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Gerar PDF
                </a>
                <a href="export.php?format=csv&tipo=<?= urlencode($tipo) ?>" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-300 transform hover:scale-[1.02]">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar Planilha
                </a>
            </div>
        </div>

        <?php if (($should_show)('fornecedor')): ?>
        <div class="section bg-white rounded-lg shadow-lg overflow-hidden p-6">
            <h2 style="<?= $headerGradients['fornecedor'] ?>">Compras por Fornecedor</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Total de Compras</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['fornecedor'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nome']) ?></td>
                            <td><?= $r['total_compras'] ?></td>
                            <td>R$ <?= number_format($r['valor_total'],2,',','.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-break"></div>
        <?php endif; ?>

        <?php if (($should_show)('pendentes')): ?>
        <div class="section">
            <h2 style="<?= $headerGradients['pendentes'] ?>">Compras Pendentes</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['pendentes'] as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                            <td><?= htmlspecialchars($r['fornecedor']) ?></td>
                            <td>R$ <?= number_format($r['valor'],2,',','.') ?></td>
                            <td><span class="status-badge status-pendente">Pendente</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-break"></div>
        <?php endif; ?>

        <?php if (($should_show)('pagas')): ?>
        <div class="section">
            <h2 style="<?= $headerGradients['pagas'] ?>">Compras Pagas</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['pagas'] as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                            <td><?= htmlspecialchars($r['fornecedor']) ?></td>
                            <td>R$ <?= number_format($r['valor'],2,',','.') ?></td>
                            <td><span class="status-badge status-pago">Pago</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-break"></div>
        <?php endif; ?>

        <?php if (($should_show)('nao_pagas')): ?>
        <div class="section">
            <h2 style="<?= $headerGradients['nao_pagas'] ?>">Compras Não Pagas</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['nao_pagas'] as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
                            <td><?= htmlspecialchars($r['fornecedor']) ?></td>
                            <td>R$ <?= number_format($r['valor'],2,',','.') ?></td>
                            <td>
                                <span class="status-badge <?= $r['status'] === 'cancelado' ? 'status-cancelado' : 'status-pendente' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-break"></div>
        <?php endif; ?>

        <?php if (($should_show)('mes')): ?>
        <div class="section">
            <h2 style="<?= $headerGradients['mes'] ?>">Total de Compras por Mês</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Total de Compras</th>
                            <th>Valor Total Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['mes'] as $r): ?>
                        <tr>
                            <td><?= date('F/Y', strtotime($r['mes'].'-01')) ?></td>
                            <td><?= $r['total_compras'] ?></td>
                            <td>R$ <?= number_format($r['total_valor'],2,',','.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-break"></div>
        <?php endif; ?>

        <?php if (($should_show)('mes_detalhado')): ?>
        <div class="section">
            <h2 style="<?= $headerGradients['mes_detalhado'] ?>">Compras por Mês (Detalhado)</h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Fornecedor</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data['mes_detalhado'] as $r): ?>
                        <tr>
                            <td><?= date('F/Y', strtotime($r['mes'] . '-01')) ?></td>
                            <td><?= htmlspecialchars($r['fornecedor']) ?></td>
                            <td>R$ <?= number_format($r['valor'],2,',','.') ?></td>
                            <td>
                                <span class="status-badge <?= 
                                    $r['status'] === 'pago' ? 'status-pago' : 
                                    ($r['status'] === 'cancelado' ? 'status-cancelado' : 'status-pendente') 
                                ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
