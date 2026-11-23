
<?php

// Carrega as classes do projeto e os repositórios.
require_once __DIR__ . "/../src/Repositorios.php";

echo "<h1>Testes do Sistema Smart Lock</h1>";

try {

    // Abre a conexão com o banco de dados.
    $conexao = Conexao::conectar();

    echo "<p>1. Conexão com MySQL: OK</p>";

    // Cria os seis repositórios.
    $usuarios = new UsuarioRepository($conexao);
    $cartoes = new CartaoRepository($conexao);
    $compartimentos = new CompartimentoRepository($conexao);
    $dispositivos = new DispositivoRepository($conexao);
    $permissoes = new PermissaoRepository($conexao);
    $historico = new RegistroAcessoRepository($conexao);

    echo "<p>2. Criação dos repositórios: OK</p>";

    // Testa a consulta de usuários.
    $consulta = $conexao->query(
        "SELECT id FROM usuarios ORDER BY id"
    );

    $listaUsuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>3. Usuários</h3>";

    foreach ($listaUsuarios as $registro) {

        $usuario = $usuarios->buscar((int)$registro["id"]);

        if ($usuario !== null) {
            echo "Usuário encontrado: "
                . htmlspecialchars($usuario->getNome())
                . "<br>";
        }
    }

    // Testa a consulta de cartões.
    $consulta = $conexao->query(
        "SELECT uid FROM cartoes ORDER BY id"
    );

    $listaCartoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>4. Cartões RFID</h3>";

    foreach ($listaCartoes as $registro) {

        $cartao = $cartoes->buscar($registro["uid"]);

        if ($cartao !== null) {
            echo "Cartão encontrado: "
                . htmlspecialchars($registro["uid"])
                . "<br>";
        }
    }

    // Testa a consulta de compartimentos.
    $consulta = $conexao->query(
        "SELECT id FROM compartimentos ORDER BY id"
    );

    $listaCompartimentos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>5. Compartimentos</h3>";

    foreach ($listaCompartimentos as $registro) {

        $compartimento = $compartimentos->buscar(
            (int)$registro["id"]
        );

        if ($compartimento !== null) {
            echo "Compartimento encontrado: "
                . htmlspecialchars($compartimento->getNome())
                . "<br>";
        }
    }

    // Testa a consulta de dispositivos.
    $consulta = $conexao->query(
        "SELECT id FROM dispositivos ORDER BY id"
    );

    $listaDispositivos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>6. Dispositivos ESP32</h3>";

    foreach ($listaDispositivos as $registro) {

        $dispositivo = $conexao->prepare(
            "SELECT id FROM dispositivos WHERE id = ?"
        );

        $dispositivo->execute([(int)$registro["id"]]);

        if ($dispositivo->fetch()) {
            echo "Dispositivo encontrado: ID "
                . (int)$registro["id"] . "<br>";
        }
    }

    // Testa a consulta das permissões.
    $consulta = $conexao->query(
        "SELECT usuario_id, compartimento_id FROM permissoes"
    );

    $listaPermissoes = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>7. Permissões</h3>";

    foreach ($listaPermissoes as $registro) {

        $possui = $permissoes->possuiPermissao(
            (int)$registro["usuario_id"],
            (int)$registro["compartimento_id"]
        );

        echo "Usuário " . (int)$registro["usuario_id"]
            . " / Compartimento "
            . (int)$registro["compartimento_id"]
            . ": "
            . ($possui ? "Permissão ativa" : "Sem permissão ativa")
            . "<br>";
    }

    // Testa a consulta do histórico de acessos.
    $registros = $historico->buscarTodos();

    echo "<h3>8. Histórico de acessos</h3>";

    echo "Quantidade de registros encontrados: "
        . count($registros) . "<br>";

    echo "<hr>";
    echo "<h2>Testes de consulta finalizados!</h2>";

} catch (Throwable $erro) {

    echo "<h2>Erro durante os testes</h2>";
    echo "<p>" . htmlspecialchars($erro->getMessage()) . "</p>";

}
