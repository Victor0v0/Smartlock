
<?php

require_once __DIR__ . "/../src/Compartilhado/Conexao.php";

try {

    $conexao = Conexao::conectar();

    // Consulta todos os usuários cadastrados.
    $consulta = $conexao->query(
        "SELECT id, nome, ativo FROM usuarios ORDER BY id"
    );

    $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Usuários cadastrados</h2>";

    foreach ($usuarios as $usuario) {

        echo "ID: " . $usuario["id"] . "<br>";

        echo "Nome: "
            . htmlspecialchars($usuario["nome"]) . "<br>";

        echo "Status: "
            . ($usuario["ativo"] ? "Ativo" : "Inativo")
            . "<br><hr>";
    }

} catch (Throwable $erro) {

    echo "Erro: " . htmlspecialchars($erro->getMessage());

}
