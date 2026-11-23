
<?php

// Carrega as classes e os repositórios do projeto.
require_once __DIR__ . "/../src/Repositorios.php";

try {

    // 1. Conecta ao banco de dados.
    $conexao = Conexao::conectar();

    // 2. Cria o repositório de dispositivos.
    $repositorio = new DispositivoRepository($conexao);

    // 3. Define o compartimento que receberá o ESP32.
    $compartimentoId = 1;

    // 4. Confere se o compartimento existe.
    $consulta = $conexao->prepare(
        "SELECT id FROM compartimentos WHERE id = ?"
    );

    $consulta->execute([$compartimentoId]);

    if (!$consulta->fetch()) {
        throw new RuntimeException(
            "O compartimento informado não existe."
        );
    }

    // 5. Cria um token de identificação para o dispositivo.
    $token = new TokenDispositivo("ESP32-SALA-TESTE-001");

    // 6. Verifica se o compartimento já possui um dispositivo.
    $consulta = $conexao->prepare(
        "SELECT id FROM dispositivos WHERE compartimento_id = ?"
    );

    $consulta->execute([$compartimentoId]);

    if ($consulta->fetch()) {
        throw new RuntimeException(
            "Este compartimento já possui um dispositivo cadastrado."
        );
    }

    // 7. Verifica se o token já está sendo utilizado.
    $consulta = $conexao->prepare(
        "SELECT id FROM dispositivos WHERE token = ?"
    );

    $consulta->execute([$token->getValor()]);

    if ($consulta->fetch()) {
        throw new RuntimeException(
            "Este token já está cadastrado."
        );
    }

    // 8. Cria o objeto que representa o dispositivo ESP32.
    $dispositivo = new DispositivoESP32(
        0,
        $token,
        true
    );

    // 9. Salva o dispositivo e sua relação com o compartimento.
    $repositorio->salvar(
        $dispositivo,
        $token,
        $compartimentoId
    );

    // 10. Consulta o dispositivo cadastrado.
    $consulta = $conexao->prepare(
        "SELECT id FROM dispositivos WHERE token = ?"
    );

    $consulta->execute([$token->getValor()]);

    $registro = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        throw new RuntimeException(
            "Não foi possível localizar o dispositivo cadastrado."
        );
    }

    // 11. Recupera o objeto utilizando o repositório.
    $dispositivoEncontrado = $repositorio->buscar(
        (int)$registro["id"]
    );

    // 12. Exibe o resultado do teste.
    if ($dispositivoEncontrado !== null) {

        echo "<h2>Dispositivo cadastrado com sucesso!</h2>";

        echo "ID: " . (int)$registro["id"] . "<br>";

        echo "Token: "
            . htmlspecialchars($token->getValor()) . "<br>";

        echo "Compartimento: " . $compartimentoId . "<br>";

        echo "Status: Ativo";

    }

} catch (Throwable $erro) {

    echo "<h2>Erro durante o teste</h2>";

    echo htmlspecialchars($erro->getMessage());

}
