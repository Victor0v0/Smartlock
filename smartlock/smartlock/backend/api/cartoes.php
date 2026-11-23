<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, PUT, PATCH, DELETE, OPTIONS');
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

$metodo = $_SERVER['REQUEST_METHOD'];
if (!in_array($metodo, ['GET', 'PUT', 'PATCH', 'DELETE'], true)) {
    header('Allow: GET, PUT, PATCH, DELETE, OPTIONS');
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

$id = null;
$nome = null;
$uid = null;
$campoStatus = null;
$valorStatus = null;

if ($metodo !== 'GET') {
    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responder(400, ['sucesso' => false, 'mensagem' => 'Envie um JSON válido.']);
    }

    $id = filter_var(
        $entrada['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id === false) {
        responder(422, ['sucesso' => false, 'mensagem' => 'ID do cartão inválido.']);
    }

    if ($metodo === 'PUT') {
        if (!isset($entrada['nome'], $entrada['uid']) || !is_string($entrada['nome']) || !is_string($entrada['uid'])) {
            responder(422, ['sucesso' => false, 'mensagem' => 'Informe o nome e o UID como texto.']);
        }

        $nome = trim($entrada['nome']);
        $uid = strtoupper((string)preg_replace('/[\s:-]+/u', '', trim($entrada['uid'])));

        if ($nome === '' || mb_strlen($nome) > 100) {
            responder(422, ['sucesso' => false, 'mensagem' => 'Informe um nome de até 100 caracteres.']);
        }

        if (!preg_match('/^(?:[0-9A-F]{8}|[0-9A-F]{14}|[0-9A-F]{20})$/', $uid)) {
            responder(422, ['sucesso' => false, 'mensagem' => 'UID inválido. Informe 4, 7 ou 10 bytes em hexadecimal.']);
        }
    }

    if ($metodo === 'PATCH') {
        $camposStatus = array_values(array_intersect(
            ['ativo', 'pode_acessar'],
            array_keys($entrada)
        ));

        if (count($camposStatus) !== 1) {
            responder(422, ['sucesso' => false, 'mensagem' => 'Informe somente um campo: ativo ou pode_acessar.']);
        }

        $campoStatus = $camposStatus[0];
        $valorStatus = $entrada[$campoStatus];
        if (!is_int($valorStatus) || !in_array($valorStatus, [0, 1], true)) {
            responder(422, ['sucesso' => false, 'mensagem' => 'O valor do campo deve ser 0 ou 1.']);
        }
    }
}

require_once __DIR__ . '/../src/Repositorios.php';

$db = null;
try {
    $db = Conexao::conectar();
    $cartoes = new CartaoRepository($db);

    if ($metodo === 'GET') {
        responder(200, ['sucesso' => true, 'cartoes' => $cartoes->listarTodos()]);
    }

    $db->beginTransaction();

    if ($metodo === 'PUT') {
        if (!$cartoes->atualizarDados((int)$id, $uid, $nome)) {
            $db->rollBack();
            responder(404, ['sucesso' => false, 'mensagem' => 'Cartão não encontrado.']);
        }

        $db->commit();
        responder(200, ['sucesso' => true, 'mensagem' => 'Cartão atualizado com sucesso.']);
    }

    if ($metodo === 'PATCH') {
        $alterado = $campoStatus === 'ativo'
            ? $cartoes->alterarStatus((int)$id, $valorStatus === 1)
            : $cartoes->alterarPermissaoAcesso((int)$id, $valorStatus === 1);

        if (!$alterado) {
            $db->rollBack();
            responder(404, ['sucesso' => false, 'mensagem' => 'Cartão não encontrado.']);
        }

        $db->commit();
        responder(200, ['sucesso' => true, 'mensagem' => 'Estado do cartão atualizado com sucesso.']);
    }

    if (!$cartoes->excluir((int)$id)) {
        $db->rollBack();
        responder(404, ['sucesso' => false, 'mensagem' => 'Cartão não encontrado.']);
    }

    $db->commit();
    responder(200, ['sucesso' => true, 'mensagem' => 'Cartão excluído com sucesso.']);
} catch (Throwable $erro) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    if (
        $erro instanceof PDOException
        && $erro->getCode() === '23000'
        && (int)($erro->errorInfo[1] ?? 0) === 1062
    ) {
        responder(409, ['sucesso' => false, 'mensagem' => 'Este UID já está cadastrado.']);
    }

    error_log('SmartLock cartões: ' . $erro->getMessage());
    responder(500, ['sucesso' => false, 'mensagem' => 'Erro interno ao processar cartões.']);
}
