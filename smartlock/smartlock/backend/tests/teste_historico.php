
<?php

require_once __DIR__ . "/../src/Repositorios.php";

try {

    // Conecta ao banco.
    $conexao = Conexao::conectar();

    // Instancia os repositórios.
    $usuarioRepo = new UsuarioRepository($conexao);
    $cartaoRepo = new CartaoRepository($conexao);
    $compartimentoRepo = new CompartimentoRepository($conexao);
    $dispositivoRepo = new DispositivoRepository($conexao);
    $permissaoRepo = new PermissaoRepository($conexao);
    $historicoRepo = new RegistroAcessoRepository($conexao);

    // Configura a consulta de permissões no MySQL.
    $gerenciamento = new GerenciamentoPermissoes();

    $gerenciamento->usarVerificadorBanco(
        function ($usuarioId, $compartimentoId) use ($permissaoRepo) {
            return $permissaoRepo->possuiPermissao(
                $usuarioId,
                $compartimentoId
            );
        }
    );

    // Cria o serviço de autorização.
    $servico = new ServicoAutorizacao($gerenciamento);

    // Recupera o compartimento e o dispositivo.
    $compartimento = $compartimentoRepo->buscar(1);
    $dispositivo = $dispositivoRepo->buscar(1);

    if ($compartimento === null || $dispositivo === null) {
        throw new RuntimeException(
            "Compartimento ou dispositivo não encontrado."
        );
    }

    // Token cadastrado para o ESP32.
    $token = new TokenDispositivo("ESP32-SALA-TESTE-001");

    // Usuários que serão testados.
    $usuariosParaTestar = [1, 4];

    echo "<h1>Teste de autorização e histórico</h1>";

    foreach ($usuariosParaTestar as $usuarioId) {

        // Busca o usuário e seus cartões.
        $usuario = $usuarioRepo->buscar($usuarioId);

        if ($usuario === null) {
            throw new RuntimeException(
                "Usuário ID $usuarioId não encontrado."
            );
        }

        // Localiza um cartão associado ao usuário.
        $consulta = $conexao->prepare(
            "SELECT id, uid FROM cartoes
             WHERE usuario_id = ?
             ORDER BY id LIMIT 1"
        );

        $consulta->execute([$usuarioId]);
        $dadosCartao = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$dadosCartao) {
            throw new RuntimeException(
                "Usuário sem cartão associado: " . $usuario->getNome()
            );
        }

        $cartao = $cartaoRepo->buscar($dadosCartao["uid"]);

        if ($cartao === null) {
            throw new RuntimeException("Cartão não encontrado.");
        }

        // Executa a decisão de autorização.
        $resultado = $servico->autorizar(
            $usuario,
            $cartao,
            $compartimento,
            $dispositivo,
            $token
        );

        // Cria o registro da tentativa.
        // Cria o registro da tentativa de acesso.
        // Cria o registro da tentativa de acesso.
        $registro = new RegistroAcesso(
            0,
            $usuario->getId(),
            $compartimento->getId(),
            $resultado
        );

        // Cria o agregado do registro de acesso.
        $agregadoRegistro = new AgregadoRegistroAcesso($registro);

        // Salva a tentativa no histórico do MySQL.
        $historicoRepo->salvar(
            $agregadoRegistro,
            (int)$dadosCartao["id"],
            $dadosCartao["uid"]
        );

        // Exibe o resultado.
        echo "<hr>";
        echo "<h3>" . htmlspecialchars($usuario->getNome()) . "</h3>";
        echo "Cartão: " . htmlspecialchars($dadosCartao["uid"]) . "<br>";

        echo $resultado->foiAutorizado()
            ? "<strong style='color:green'>ACESSO AUTORIZADO</strong><br>"
            : "<strong style='color:red'>ACESSO NEGADO</strong><br>";

        echo "Tentativa registrada no histórico.";
    }

    echo "<hr><h2>Testes finalizados!</h2>";

    // Mostra a quantidade total de registros.
    $registros = $historicoRepo->buscarTodos();

    echo "Total de registros no histórico: " . count($registros);

} catch (Throwable $erro) {

    echo "<h2>Erro durante o teste</h2>";
    echo htmlspecialchars($erro->getMessage());

}
