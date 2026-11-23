
<?php

require_once __DIR__ . "/../src/Repositorios.php";

try {

    // Conecta ao banco de dados.
    $conexao = Conexao::conectar();

    // Cria o repositório de compartimentos.
    $repositorio = new CompartimentoRepository($conexao);

    // Cria um compartimento de teste.
    $compartimento = new Compartimento(
        0,
        "Sala de Teste",
        true
    );

    // Salva o compartimento no banco.
    $repositorio->salvar($compartimento);

    echo "<h2>Compartimento cadastrado!</h2>";

    // Consulta o compartimento recém-cadastrado.
    $consulta = $conexao->query(
        "SELECT id FROM compartimentos ORDER BY id DESC LIMIT 1"
    );

    $registro = $consulta->fetch(PDO::FETCH_ASSOC);

    $encontrado = $repositorio->buscar((int)$registro["id"]);

    if ($encontrado !== null) {
        echo "ID: " . $registro["id"] . "<br>";
        echo "Nome: " . htmlspecialchars($encontrado->getNome()) . "<br>";
        echo "Status: Ativo";
    }

} catch (Throwable $erro) {

    echo "<h2>Erro no teste</h2>";
    echo htmlspecialchars($erro->getMessage());

}
