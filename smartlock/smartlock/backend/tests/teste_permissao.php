
<?php

// Carrega as classes e os repositórios do projeto.
require_once __DIR__ . "/../src/Repositorios.php";

try {

    // 1. Conecta ao banco de dados.
    $conexao = Conexao::conectar();

    // 2. Cria o repositório de permissões.
    $repositorio = new PermissaoRepository($conexao);

    // 3. Localiza o usuário Victor Teste.
    $consulta = $conexao->prepare(
        "SELECT id, nome FROM usuarios WHERE nome = ?"
    );

    $consulta->execute(["Victor Teste"]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new RuntimeException(
            "O usuário Victor Teste não foi encontrado."
        );
    }

    $usuarioId = (int)$usuario["id"];

    // 4. Define o compartimento Sala de Teste.
    $compartimentoId = 1;

    // 5. Confirma se o compartimento existe.
    $consulta = $conexao->prepare(
        "SELECT id FROM compartimentos WHERE id = ?"
    );

    $consulta->execute([$compartimentoId]);

    if (!$consulta->fetch()) {
        throw new RuntimeException(
            "O compartimento não foi encontrado."
        );
    }

    // 6. Verifica se já existe uma permissão para essa combinação.
    $consulta = $conexao->prepare(
        "SELECT id FROM permissoes
         WHERE usuario_id = ? AND compartimento_id = ?"
    );

    $consulta->execute([$usuarioId, $compartimentoId]);

    if ($consulta->fetch()) {
        throw new RuntimeException(
            "Este usuário já possui uma permissão cadastrada para esse compartimento."
        );
    }

    // 7. Cria a permissão de acesso.
    $permissao = new Permissao(
        0,
        $usuarioId,
        $compartimentoId,
        true
    );

    // 8. Salva a permissão no banco.
    $repositorio->salvar($permissao);

    // 9. Confirma se a permissão está ativa.
    $autorizado = $repositorio->possuiPermissao(
        $usuarioId,
        $compartimentoId
    );

    if (!$autorizado) {
        throw new RuntimeException(
            "A permissão foi cadastrada, mas não foi confirmada como ativa."
        );
    }

    // 10. Exibe o resultado.
    echo "<h2>Permissão cadastrada com sucesso!</h2>";
    echo "Usuário: " . htmlspecialchars($usuario["nome"]) . "<br>";
    echo "ID do usuário: " . $usuarioId . "<br>";
    echo "Compartimento: Sala de Teste<br>";
    echo "ID do compartimento: " . $compartimentoId . "<br>";
    echo "Permissão: Ativa";

} catch (Throwable $erro) {

    echo "<h2>Erro durante o teste</h2>";
    echo htmlspecialchars($erro->getMessage());

}
