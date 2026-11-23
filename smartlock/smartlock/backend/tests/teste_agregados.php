
<?php

require_once __DIR__ . "/../src/Agregados.php";

try {

    // Cria um usuário para testar o agregado.
    $usuario = new Usuario(100, "Usuario de Teste", true);

    $agregadoUsuario = new AgregadoUsuario($usuario);

    // Cria um cartão RFID.
    $cartao = new CartaoRFID(
        100,
        new UID("TESTE123"),
        true
    );

    // Associa o cartão ao usuário.
    $agregadoUsuario->adicionarCartao($cartao);

    // Verifica se o cartão pertence ao usuário.
    if ($agregadoUsuario->possuiCartao($cartao)) {
        echo "Agregado de Usuario: OK<br>";
    }

    // Consulta os cartões associados.
    echo "UIDs cadastrados: ";

    echo implode(", ", $agregadoUsuario->listarUIDsCartoes());

    echo "<hr>";

    // Testa o agregado de compartimento.
    $compartimento = new Compartimento(
        100,
        "Compartimento de Teste",
        true
    );

    $agregadoCompartimento = new AgregadoCompartimento(
        $compartimento
    );

    echo $agregadoCompartimento->estaAtivo()
        ? "Compartimento ativo: OK<br>"
        : "Erro no status do compartimento<br>";

    $agregadoCompartimento->alterarStatus(false);

    echo !$agregadoCompartimento->estaAtivo()
        ? "Alteração de status: OK"
        : "Erro na alteração de status";

} catch (Throwable $erro) {

    echo "Erro: " . htmlspecialchars($erro->getMessage());

}
