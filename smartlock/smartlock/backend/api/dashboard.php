<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function responder(int $status, array $dados): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, OPTIONS');
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

require_once __DIR__ . '/../src/Repositorios.php';

try {
    $db = Conexao::conectar();
    $dashboard = new DashboardRepository($db);

    $consultaHoje = $db->prepare('SELECT CURDATE()');
    $consultaHoje->execute();
    $hoje = new DateTimeImmutable((string)$consultaHoje->fetchColumn());

    $inicio = $hoje->modify('-6 days');
    $fim = $hoje->modify('+1 day');
    $registrosDiarios = $dashboard->buscarAcessosPorDia(
        $inicio->format('Y-m-d 00:00:00'),
        $fim->format('Y-m-d 00:00:00')
    );

    $diariosPorData = [];
    foreach ($registrosDiarios as $registro) {
        $diariosPorData[$registro['data']] = $registro;
    }

    $acessosPorDia = [];
    for ($dia = $inicio; $dia < $fim; $dia = $dia->modify('+1 day')) {
        $data = $dia->format('Y-m-d');
        $registro = $diariosPorData[$data] ?? [];
        $acessosPorDia[] = [
            'data' => $data,
            'total' => (int)($registro['total'] ?? 0),
            'autorizados' => (int)($registro['autorizados'] ?? 0),
            'negados' => (int)($registro['negados'] ?? 0)
        ];
    }

    $resumo = array_map('intval', $dashboard->obterResumo());
    $acessosRecentes = $dashboard->buscarAcessosRecentes(10);

    responder(200, [
        'sucesso' => true,
        'resumo' => $resumo,
        'acessos_recentes' => $acessosRecentes,
        'acessos_por_dia' => $acessosPorDia
    ]);
} catch (Throwable $erro) {
    error_log('SmartLock dashboard: ' . $erro->getMessage());
    responder(500, ['sucesso' => false, 'mensagem' => 'Erro interno ao carregar o dashboard.']);
}
