
<?php

// Carrega a classe responsável pela conexão com o MySQL.
require_once __DIR__ . "/../src/Compartilhado/Conexao.php";

try {

    // Tenta estabelecer a conexão com o banco controle_rfid.
    $conexao = Conexao::conectar();

    echo "Conexão com o banco de dados realizada com sucesso!";

} catch (Throwable $erro) {

    // Exibe o erro caso a conexão não seja estabelecida.
    echo "Falha na conexão: " . $erro->getMessage();
}
