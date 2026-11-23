<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function responder(int $status, array $dados): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(400, ['sucesso' => false, 'mensagem' => 'Envie um JSON válido.']);
}

if (!isset($entrada['nome'], $entrada['uid']) || !is_string($entrada['nome']) || !is_string($entrada['uid'])) {
    responder(422, ['sucesso' => false, 'mensagem' => 'Informe o nome e o UID do cartão como texto.']);
}

$nome = trim($entrada['nome']);
$uidOriginal = trim($entrada['uid']);
$uid = strtoupper((string)preg_replace('/[\s:-]+/u', '', $uidOriginal));

if ($nome === '' || mb_strlen($nome) > 100) {
    responder(422, ['sucesso' => false, 'mensagem' => 'Informe um nome de até 100 caracteres.']);
}

if (!preg_match('/^(?:[0-9A-F]{8}|[0-9A-F]{14}|[0-9A-F]{20})$/', $uid)) {
    responder(422, ['sucesso' => false, 'mensagem' => 'UID inválido. Informe 4, 7 ou 10 bytes em hexadecimal.']);
}

require_once __DIR__ . '/../src/Compartilhado/Conexao.php';

try {
    $db = Conexao::conectar();
    $db->beginTransaction();

    $verificar = $db->prepare('SELECT id FROM cartoes WHERE uid = ? LIMIT 1');
    $verificar->execute([$uid]);
    if ($verificar->fetch(PDO::FETCH_ASSOC)) {
        $db->rollBack();
        responder(409, ['sucesso' => false, 'mensagem' => 'Este cartão já está cadastrado.']);
    }

    $criarUsuario = $db->prepare('INSERT INTO usuarios (nome) VALUES (?)');
    $criarUsuario->execute([$nome]);
    $usuarioId = (int)$db->lastInsertId();

    // O cartão começa ativo, mas sem autorização geral.
    // A permissão de acesso será configurada na etapa de permissões.
    $criarCartao = $db->prepare(
        'INSERT INTO cartoes (uid, usuario_id, pode_acessar, ativo) VALUES (?, ?, 0, 1)'
    );
    $criarCartao->execute([$uid, $usuarioId]);

    $db->commit();

    responder(201, [
        'sucesso' => true,
        'mensagem' => 'Pessoa e cartão cadastrados com sucesso.',
        'usuario_id' => $usuarioId,
        'uid' => $uid
    ]);
} catch (Throwable $erro) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    if (
        $erro instanceof PDOException
        && $erro->getCode() === '23000'
        && (int)($erro->errorInfo[1] ?? 0) === 1062
    ) {
        responder(409, ['sucesso' => false, 'mensagem' => 'Este cartão já está cadastrado.']);
    }

    error_log('SmartLock cadastro: ' . $erro->getMessage());
    responder(500, ['sucesso' => false, 'mensagem' => 'Erro interno ao cadastrar. Confira a conexão com o banco e os logs do PHP.']);
}
