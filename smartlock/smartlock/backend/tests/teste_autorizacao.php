
<?php

// Carrega as classes e os repositórios do Smart Lock.
require_once __DIR__ . "/../src/Repositorios.php";

try {

    // 1. Conecta ao banco de dados.
    $conexao = Conexao::conectar();

    // 2. Instancia os repositórios necessários.
    $usuarioRepo = new UsuarioRepository($conexao);
    $cartaoRepo = new CartaoRepository($conexao);
    $compartimentoRepo = new CompartimentoRepository($conexao);
    $dispositivoRepo = new DispositivoRepository($conexao);
    $permissaoRepo = new PermissaoRepository($conexao);

    // 3. Cria o gerenciamento de permissões.
    $gerenciamento = new GerenciamentoPermissoes();

    // Faz o gerenciamento consultar as permissões no MySQL.
    $gerenciamento->usarVerificadorBanco(
        function ($usuarioId, $compartimentoId) use ($permissaoRepo) {

            return $permissaoRepo->possuiPermissao(
                $usuarioId,
                $compartimentoId
            );
        }
    );

    // 4. Cria o serviço responsável pela autorização.
    $servico = new ServicoAutorizacao($gerenciamento);

    // 5. Recupera o compartimento cadastrado.
    $compartimento = $compartimentoRepo->buscar(1);

    if ($compartimento === null) {
        throw new RuntimeException("Compartimento não encontrado.");
    }

    // 6. Recupera o dispositivo ESP32 cadastrado.
    $dispositivo = $dispositivoRepo->buscar(1);

    if ($dispositivo === null) {
        throw new RuntimeException("Dispositivo ESP32 não encontrado.");
    }

    // Token utilizado no cadastro do dispositivo de teste.
    $token = new TokenDispositivo("ESP32-SALA-TESTE-001");

    // Função que testa o acesso de um usuário.
    function testarAcesso(
        int $usuarioId,
        UsuarioRepository $usuarioRepo,
        CartaoRepository $cartaoRepo,
        Compartimento $compartimento,
        DispositivoESP32 $dispositivo,
        TokenDispositivo $token,
        ServicoAutorizacao $servico,
        PDO $conexao
    ): void {

        echo "<hr>";

        // Busca o usuário e carrega seus cartões.
        $usuario = $usuarioRepo->buscar($usuarioId);

        if ($usuario === null) {
            echo "Usuário ID $usuarioId não encontrado.<br>";
            return;
        }

        echo "<h3>Usuário: "
            . htmlspecialchars($usuario->getNome())
            . "</h3>";

        // Localiza um cartão associado ao usuário no banco.
        $consulta = $conexao->prepare(
            "SELECT uid FROM cartoes
             WHERE usuario_id = ?
             ORDER BY id LIMIT 1"
        );

        $consulta->execute([$usuarioId]);

        $registroCartao = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$registroCartao) {
            echo "Não existe cartão associado a este usuário.<br>";
            echo "Não foi possível executar a validação RFID.<br>";
            return;
        }

        // Recupera a entidade do cartão pelo UID.
        $cartao = $cartaoRepo->buscar($registroCartao["uid"]);

        if ($cartao === null) {
            echo "Cartão não encontrado no repositório.<br>";
            return;
        }

        echo "Cartão: "
            . htmlspecialchars($registroCartao["uid"])
            . "<br>";

        // Executa as regras reais de autorização.
        $resultado = $servico->autorizar(
            $usuario,
            $cartao,
            $compartimento,
            $dispositivo,
            $token
        );

        // Exibe o resultado da decisão.
        if ($resultado->foiAutorizado()) {
            echo "<strong style='color:green'>ACESSO AUTORIZADO</strong>";
        } else {
            echo "<strong style='color:red'>ACESSO NEGADO</strong>";
        }
    }

    echo "<h1>Teste de Autorização Smart Lock</h1>";

    // 7. Testa o Victor Teste.
    testarAcesso(
        1,
        $usuarioRepo,
        $cartaoRepo,
        $compartimento,
        $dispositivo,
        $token,
        $servico,
        $conexao
    );

    // 8. Testa o Victor Teste 2.
    testarAcesso(
        4,
        $usuarioRepo,
        $cartaoRepo,
        $compartimento,
        $dispositivo,
        $token,
        $servico,
        $conexao
    );

    echo "<hr>";
    echo "<h2>Teste de autorização finalizado!</h2>";

} catch (Throwable $erro) {

    echo "<h2>Erro durante o teste</h2>";
    echo htmlspecialchars($erro->getMessage());

}
