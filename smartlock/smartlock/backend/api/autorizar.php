<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function responder(int $status, array $dados): void {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function verificarLimite(string $ip, string $categoria, int $limite, int $janela): ?bool {
    $caminho = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smartlock-auth-limit.json';
    $arquivo = @fopen($caminho, 'c+');

    if ($arquivo === false || !flock($arquivo, LOCK_EX)) {
        if (is_resource($arquivo)) {
            fclose($arquivo);
        }
        return null;
    }

    rewind($arquivo);
    $conteudo = stream_get_contents($arquivo);
    $registros = $conteudo === '' ? [] : json_decode($conteudo, true);

    if (!is_array($registros)) {
        flock($arquivo, LOCK_UN);
        fclose($arquivo);
        return null;
    }

    $agora = time();
    foreach ($registros as $chave => $tentativas) {
        if (!is_array($tentativas)) {
            unset($registros[$chave]);
            continue;
        }

        $registros[$chave] = array_values(array_filter(
            $tentativas,
            static fn($instante): bool => is_int($instante) && $instante > $agora - $janela
        ));

        if ($registros[$chave] === []) {
            unset($registros[$chave]);
        }
    }

    $chave = hash('sha256', $categoria . '|' . $ip);
    $tentativas = $registros[$chave] ?? [];
    $limiteAtingido = count($tentativas) >= $limite;

    if (!$limiteAtingido) {
        $tentativas[] = $agora;
        $registros[$chave] = $tentativas;
    }

    if (count($registros) > 2000) {
        uasort($registros, static fn(array $a, array $b): int => max($a) <=> max($b));
        $registros = array_slice($registros, -2000, null, true);
    }

    $novoConteudo = json_encode($registros);
    $gravado = is_string($novoConteudo)
        && rewind($arquivo)
        && ftruncate($arquivo, 0)
        && fwrite($arquivo, $novoConteudo) === strlen($novoConteudo)
        && fflush($arquivo);

    flock($arquivo, LOCK_UN);
    fclose($arquivo);

    return $gravado ? $limiteAtingido : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    responder(405, ['resultado' => 'NEGADO', 'mensagem' => 'Método não permitido.']);
}

$ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
$limiteGeral = verificarLimite($ip, 'requisicoes', 120, 60);
if ($limiteGeral === null) {
    responder(503, ['resultado' => 'NEGADO', 'mensagem' => 'Serviço temporariamente indisponível.']);
}
if ($limiteGeral) {
    header('Retry-After: 60');
    responder(429, ['resultado' => 'NEGADO', 'mensagem' => 'Limite de requisições excedido.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada) || !isset($entrada['uid']) || !is_string($entrada['uid'])) {
    responder(422, ['resultado' => 'NEGADO', 'mensagem' => 'Informe um UID válido.']);
}

$uid = strtoupper((string)preg_replace('/[\s:-]+/u', '', trim($entrada['uid'])));
if (!preg_match('/^(?:[0-9A-F]{8}|[0-9A-F]{14}|[0-9A-F]{20})$/', $uid)) {
    responder(422, ['resultado' => 'NEGADO', 'mensagem' => 'Informe um UID válido.']);
}

$autorizacao = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!is_string($autorizacao)) {
    $autorizacao = '';
}
$autorizacao = trim($autorizacao);

if ($autorizacao === '' && function_exists('getallheaders')) {
    $cabecalhos = getallheaders();
    if (is_array($cabecalhos)) {
        foreach ($cabecalhos as $nome => $valor) {
            if (is_string($nome) && strcasecmp($nome, 'Authorization') === 0 && is_string($valor)) {
                $autorizacao = trim($valor);
                break;
            }
        }
    }
}

$tokenValor = null;
if (preg_match('/^Bearer\s+([^\s]+)$/i', $autorizacao, $correspondencia) === 1
    && strlen($correspondencia[1]) <= 255) {
    $tokenValor = $correspondencia[1];
}

require_once __DIR__ . '/../src/Repositorios.php';

$db = null;
try {
    $db = Conexao::conectar();
    $db->beginTransaction();

    $historico = new RegistroAcessoRepository($db);
    $dispositivoContexto = null;

    if ($tokenValor !== null) {
        $dispositivoRepo = new DispositivoRepository($db);
        $dispositivoContexto = $dispositivoRepo->buscarPorToken(
            new TokenDispositivo($tokenValor)
        );
    }

    if ($dispositivoContexto === null) {
        $limiteTokenInvalido = verificarLimite($ip, 'token-invalido', 10, 60);
        if ($limiteTokenInvalido === null) {
            $db->rollBack();
            responder(503, ['resultado' => 'NEGADO', 'mensagem' => 'Serviço temporariamente indisponível.']);
        }
        if ($limiteTokenInvalido) {
            $db->rollBack();
            header('Retry-After: 60');
            responder(429, ['resultado' => 'NEGADO', 'mensagem' => 'Limite de autenticações excedido.']);
        }

        $resultado = new ResultadoAcesso(false, 'Dispositivo não autenticado.');
        $historico->registrarTentativa(null, null, $uid, $resultado);
        $db->commit();
        responder(401, ['resultado' => 'NEGADO', 'mensagem' => 'Dispositivo não autenticado.']);
    }

    $dispositivo = $dispositivoContexto['dispositivo'];
    $compartimento = $dispositivoContexto['compartimento'];
    $cartaoRepo = new CartaoRepository($db);
    $dadosCartao = $cartaoRepo->buscarParaAutorizacao($uid);

    if ($dadosCartao === null) {
        $resultado = new ResultadoAcesso(false, 'Cartão não cadastrado.');
        $historico->registrarTentativa(null, $compartimento->getId(), $uid, $resultado);
        $db->commit();
        responder(200, ['resultado' => 'NEGADO', 'mensagem' => 'Cartão não cadastrado.']);
    }

    $usuarioRepo = new UsuarioRepository($db);
    $usuario = $usuarioRepo->buscar((int)$dadosCartao['usuario_id']);
    $cartao = new CartaoRFID(
        (int)$dadosCartao['id'],
        new UID($dadosCartao['uid']),
        (bool)$dadosCartao['ativo'],
        (bool)$dadosCartao['pode_acessar']
    );

    if ($usuario === null) {
        $resultado = new ResultadoAcesso(false, 'Usuário não encontrado.');
    } else {
        $permissaoRepo = new PermissaoRepository($db);
        $gerenciamento = new GerenciamentoPermissoes();
        $gerenciamento->usarVerificadorBanco(
            static function (int $usuarioId, int $compartimentoId) use ($permissaoRepo): bool {
                return $permissaoRepo->possuiPermissao($usuarioId, $compartimentoId);
            }
        );

        $servico = new ServicoAutorizacao($gerenciamento);
        $resultado = $servico->autorizar(
            $usuario,
            $cartao,
            $compartimento,
            $dispositivo,
            new TokenDispositivo($tokenValor)
        );
    }

    $historico->registrarTentativa(
        (int)$dadosCartao['id'],
        $compartimento->getId(),
        $uid,
        $resultado
    );
    $db->commit();

    responder(200, [
        'resultado' => $resultado->foiAutorizado() ? 'AUTORIZADO' : 'NEGADO',
        'mensagem' => $resultado->foiAutorizado() ? 'Acesso autorizado.' : 'Acesso negado.'
    ]);
} catch (Throwable $erro) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('SmartLock autorização: ' . get_class($erro));
    responder(503, ['resultado' => 'NEGADO', 'mensagem' => 'Serviço temporariamente indisponível.']);
}
