
<?php

require_once __DIR__ . "/Compartilhado/Conexao.php";
require_once __DIR__ . "/PessoasCartoes/PessoasCartoes.php";
require_once __DIR__ . "/Compartilhado/ObjetosValor.php";
require_once __DIR__ . "/PermissoesAcesso/PermissoesAcesso.php";
require_once __DIR__ . "/ControleAcesso/ControleAcesso.php";
require_once __DIR__ . "/HistoricoConsulta/HistoricoConsulta.php";
require_once __DIR__ . "/Agregados.php";


/*
 * 1. REPOSITORIO DE USUARIOS
 *
 * Responsável por cadastrar, consultar e atualizar
 * os usuários que possuem cartões no Smart Lock.
 *
 * O repositório conecta a entidade Usuario ao MySQL,
 * separando a persistência das regras do domínio.
 */
class UsuarioRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Cadastra um usuário no banco de dados.
    public function salvar(Usuario $usuario, ?string $email = null): void {

        $sql = "INSERT INTO usuarios (nome, email, ativo)
                VALUES (?, ?, ?)";

        $this->db->prepare($sql)->execute([
            $usuario->getNome(),
            $email,
            $usuario->estaAtivo() ? 1 : 0
        ]);
    }

    // Recupera um usuário pelo seu identificador.
    
// Busca um usuário pelo ID e também carrega seus cartões RFID.
public function buscar(int $id): ?Usuario {

    // Primeiro, recupera os dados principais do usuário.
    $s = $this->db->prepare(
        "SELECT * FROM usuarios WHERE id = ?"
    );

    $s->execute([$id]);
    $d = $s->fetch(PDO::FETCH_ASSOC);

    // Se o usuário não existir, retorna null.
    if (!$d) {
        return null;
    }

    // Reconstrói a entidade Usuario.
    $usuario = new Usuario(
        (int)$d["id"],
        $d["nome"],
        (bool)$d["ativo"]
    );

    // Busca todos os cartões associados ao usuário.
    $consultaCartoes = $this->db->prepare(
        "SELECT * FROM cartoes WHERE usuario_id = ?"
    );

    $consultaCartoes->execute([$id]);

    $listaCartoes = $consultaCartoes->fetchAll(PDO::FETCH_ASSOC);

    // Adiciona cada cartão à entidade Usuario.
    foreach ($listaCartoes as $registro) {

        $cartao = new CartaoRFID(
            (int)$registro["id"],
            new UID($registro["uid"]),
            (bool)$registro["ativo"],
            (bool)$registro["pode_acessar"]
        );

        $usuario->adicionarCartao($cartao);
    }

    return $usuario;
}


    // Atualiza os dados cadastrais e a situação do usuário.
    public function atualizar(int $id, string $nome, ?string $email, bool $ativo): void {

        $s = $this->db->prepare(
            "UPDATE usuarios SET nome=?, email=?, ativo=? WHERE id=?"
        );

        $s->execute([$nome, $email, $ativo ? 1 : 0, $id]);
    }

    // Remove o usuário, respeitando os relacionamentos do banco.
    public function excluir(int $id): void {
        $this->db->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
    }
}


/*
 * 2. REPOSITORIO DE CARTOES
 *
 * Gerencia os cartões RFID e seus proprietários.
 * O campo usuario_id estabelece a associação entre
 * o cartão e a pessoa cadastrada no sistema.
 */
class CartaoRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function buscarParaAutorizacao(string $uid): ?array {

        $consulta = $this->db->prepare(
            "SELECT c.id, c.uid, c.usuario_id, c.ativo, c.pode_acessar,
                    u.ativo AS usuario_ativo
             FROM cartoes c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.uid = ?"
        );
        $consulta->execute([strtoupper(trim($uid))]);
        $cartao = $consulta->fetch(PDO::FETCH_ASSOC);

        return $cartao ?: null;
    }

    public function listarTodos(): array {

        $consulta = $this->db->prepare(
                "SELECT c.id, c.uid, c.usuario_id, c.ativo, c.pode_acessar,
                    u.nome AS proprietario
             FROM cartoes c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             ORDER BY c.id DESC"
        );

        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarDados(int $id, string $uid, string $nome): bool {

        $consulta = $this->db->prepare(
            "SELECT usuario_id FROM cartoes WHERE id = ? FOR UPDATE"
        );
        $consulta->execute([$id]);
        $cartao = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$cartao) {
            return false;
        }

        $atualizarCartao = $this->db->prepare(
            "UPDATE cartoes SET uid = ? WHERE id = ?"
        );
        $atualizarCartao->execute([$uid, $id]);

        $atualizarProprietario = $this->db->prepare(
            "UPDATE usuarios SET nome = ? WHERE id = ?"
        );
        $atualizarProprietario->execute([$nome, (int)$cartao['usuario_id']]);

        return true;
    }

    public function alterarStatus(int $id, bool $ativo): bool {

        $consulta = $this->db->prepare(
            "SELECT id FROM cartoes WHERE id = ?"
        );
        $consulta->execute([$id]);

        if (!$consulta->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $atualizar = $this->db->prepare(
            "UPDATE cartoes SET ativo = ? WHERE id = ?"
        );
        $atualizar->execute([$ativo ? 1 : 0, $id]);

        return true;
    }

    public function alterarPermissaoAcesso(int $id, bool $podeAcessar): bool {

        $consulta = $this->db->prepare(
            "SELECT id FROM cartoes WHERE id = ?"
        );
        $consulta->execute([$id]);

        if (!$consulta->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $atualizar = $this->db->prepare(
            "UPDATE cartoes SET pode_acessar = ? WHERE id = ?"
        );
        $atualizar->execute([$podeAcessar ? 1 : 0, $id]);

        return true;
    }

    // Armazena o UID e vincula o cartão ao seu proprietário.
    public function salvar(CartaoRFID $cartao, int $usuarioId): void {

        $sql = "INSERT INTO cartoes (uid, usuario_id, pode_acessar, ativo)
                VALUES (?, ?, 0, ?)";

        $this->db->prepare($sql)->execute([
            $cartao->getUID()->getValor(),
            $usuarioId,
            $cartao->estaAtivo() ? 1 : 0
        ]);
    }

    // Localiza um cartão pelo UID recebido pelo leitor RFID.
    public function buscar(string $uid): ?CartaoRFID {

        $s = $this->db->prepare("SELECT * FROM cartoes WHERE uid=?");
        $s->execute([strtoupper(trim($uid))]);
        $d = $s->fetch(PDO::FETCH_ASSOC);

        return $d ? new CartaoRFID(
            (int)$d["id"],
            new UID($d["uid"]),
            (bool)$d["ativo"],
            (bool)$d["pode_acessar"]
        ) : null;
    }

    // Atualiza o proprietário e a situação do cartão.
    public function atualizar(int $id, int $usuarioId, bool $ativo): void {

        $s = $this->db->prepare(
            "UPDATE cartoes SET usuario_id=?, ativo=? WHERE id=?"
        );

        $s->execute([$usuarioId, $ativo ? 1 : 0, $id]);
    }

    // Exclui um cartão cadastrado.
    public function excluir(int $id): bool {

        $consulta = $this->db->prepare(
            "SELECT id FROM cartoes WHERE id = ? FOR UPDATE"
        );
        $consulta->execute([$id]);

        if (!$consulta->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $excluir = $this->db->prepare(
            "DELETE FROM cartoes WHERE id = ?"
        );
        $excluir->execute([$id]);

        return $excluir->rowCount() > 0;
    }
}


/*
 * 3. REPOSITORIO DE COMPARTIMENTOS
 *
 * Gerencia os espaços protegidos pelo Smart Lock,
 * como salas, armários e áreas restritas.
 *
 * Cada compartimento possui identificação e situação
 * próprias, permitindo controlar seu funcionamento.
 */
class CompartimentoRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Cadastra um novo compartimento.
    public function salvar(Compartimento $c): void {

        $s = $this->db->prepare(
            "INSERT INTO compartimentos (nome, ativo) VALUES (?, ?)"
        );

        $s->execute([$c->getNome(), $c->estaAtivo() ? 1 : 0]);
    }

    // Consulta um compartimento pelo identificador.
    public function buscar(int $id): ?Compartimento {

        $s = $this->db->prepare(
            "SELECT * FROM compartimentos WHERE id=?"
        );

        $s->execute([$id]);
        $d = $s->fetch(PDO::FETCH_ASSOC);

        return $d ? new Compartimento(
            $d["id"], $d["nome"], (bool)$d["ativo"]
        ) : null;
    }

    // Atualiza o nome e a situação do compartimento.
    public function atualizar(int $id, string $nome, bool $ativo): void {

        $s = $this->db->prepare(
            "UPDATE compartimentos SET nome=?, ativo=? WHERE id=?"
        );

        $s->execute([$nome, $ativo ? 1 : 0, $id]);
    }

    // Exclui um compartimento sem ignorar suas dependências.
    public function excluir(int $id): void {
        $this->db->prepare("DELETE FROM compartimentos WHERE id=?")->execute([$id]);
    }
}


/*
 * 4. REPOSITORIO DE DISPOSITIVOS
 *
 * Gerencia os dispositivos ESP32 utilizados no sistema.
 * Cada dispositivo fica vinculado a um compartimento,
 * conforme a organização do domínio Smart Lock.
 */
class DispositivoRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Cadastra um dispositivo e sua vinculação ao compartimento.
    public function salvar(
        DispositivoESP32 $d,
        TokenDispositivo $token,
        int $compartimentoId
    ): void {

        $s = $this->db->prepare(
            "INSERT INTO dispositivos (token, compartimento_id, ativo)
             VALUES (?, ?, ?)"
        );

        $s->execute([
            $token->getValor(),
            $compartimentoId,
            $d->estaAtivo() ? 1 : 0
        ]);
    }

    // Consulta um dispositivo pelo identificador.
    public function buscar(int $id): ?DispositivoESP32 {

        $s = $this->db->prepare(
            "SELECT * FROM dispositivos WHERE id=?"
        );

        $s->execute([$id]);
        $d = $s->fetch(PDO::FETCH_ASSOC);

        return $d ? new DispositivoESP32(
            $d["id"],
            new TokenDispositivo($d["token"]),
            (bool)$d["ativo"],
            (int)$d["compartimento_id"]
        ) : null;
    }

    public function buscarPorToken(TokenDispositivo $token): ?array {

        $consulta = $this->db->prepare(
            "SELECT d.id, d.token, d.ativo AS dispositivo_ativo,
                    d.compartimento_id AS dispositivo_compartimento_id,
                    c.id AS compartimento_id, c.nome AS compartimento_nome,
                    c.ativo AS compartimento_ativo
             FROM dispositivos d
             INNER JOIN compartimentos c ON c.id = d.compartimento_id
             WHERE d.token = ?"
        );
        $consulta->execute([$token->getValor()]);
        $dados = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            return null;
        }

        return [
            'dispositivo' => new DispositivoESP32(
                (int)$dados['id'],
                new TokenDispositivo($dados['token']),
                (bool)$dados['dispositivo_ativo'],
                (int)$dados['dispositivo_compartimento_id']
            ),
            'compartimento' => new Compartimento(
                (int)$dados['compartimento_id'],
                $dados['compartimento_nome'],
                (bool)$dados['compartimento_ativo']
            )
        ];
    }

    // Atualiza o token, o compartimento e a situação do dispositivo.
    public function atualizar(
        int $id,
        TokenDispositivo $token,
        int $compartimentoId,
        bool $ativo
    ): void {

        $s = $this->db->prepare(
            "UPDATE dispositivos
             SET token=?, compartimento_id=?, ativo=? WHERE id=?"
        );

        $s->execute([
            $token->getValor(),
            $compartimentoId,
            $ativo ? 1 : 0,
            $id
        ]);
    }

    // Remove o dispositivo cadastrado.
    public function excluir(int $id): void {
        $this->db->prepare("DELETE FROM dispositivos WHERE id=?")->execute([$id]);
    }
}


/*
 * 5. REPOSITORIO DE PERMISSOES
 *
 * Administra as autorizações dos usuários para
 * acessar determinados compartimentos.
 *
 * A consulta deste repositório é utilizada pelo
 * Serviço de Autorização durante a tentativa de acesso.
 */
class PermissaoRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Cadastra uma permissão para usuário e compartimento.
    public function salvar(Permissao $p): void {

        $s = $this->db->prepare(
            "INSERT INTO permissoes
             (usuario_id, compartimento_id, ativa) VALUES (?, ?, ?)"
        );

        $s->execute([
            $p->getUsuarioId(),
            $p->getCompartimentoId(),
            $p->estaAtiva() ? 1 : 0
        ]);
    }

    // Verifica se existe autorização ativa para o compartimento.
    public function possuiPermissao(int $usuarioId, int $compartimentoId): bool {

        $s = $this->db->prepare(
            "SELECT COUNT(*) FROM permissoes
             WHERE usuario_id=? AND compartimento_id=? AND ativa=1"
        );

        $s->execute([$usuarioId, $compartimentoId]);

        return (int)$s->fetchColumn() > 0;
    }

    // Ativa ou revoga uma permissão existente.
    public function atualizar(int $id, bool $ativa): void {

        $s = $this->db->prepare(
            "UPDATE permissoes SET ativa=? WHERE id=?"
        );

        $s->execute([$ativa ? 1 : 0, $id]);
    }

    // Exclui uma permissão cadastrada.
    public function excluir(int $id): void {
        $this->db->prepare("DELETE FROM permissoes WHERE id=?")->execute([$id]);
    }
}


/*
 * 6. REPOSITORIO DE REGISTROS DE ACESSO
 *
 * Armazena e consulta as tentativas realizadas no sistema.
 * Os registros permitem acompanhar o cartão utilizado,
 * o compartimento solicitado, a data, o horário e o resultado.
 */
class RegistroAcessoRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Persiste uma tentativa, autorizada ou negada.
    public function salvar(
        AgregadoRegistroAcesso $r,
        ?int $cartaoId,
        string $uid
    ): void {

        $resultado = $r->getResultado()->foiAutorizado()
            ? "AUTORIZADO" : "NEGADO";

        $s = $this->db->prepare(
            "INSERT INTO log_acessos
             (cartao_id, uid_lido, compartimento_id, data_hora, resultado)
             VALUES (?, ?, ?, ?, ?)"
        );

        $s->execute([
            $cartaoId,
            strtoupper(trim($uid)),
            $r->getCompartimentoId(),
            $r->getDataHora()
                ->setTimezone(new DateTimeZone("America/Sao_Paulo"))
                ->format("Y-m-d H:i:s"),
            $resultado
        ]);
    }

    public function registrarTentativa(
        ?int $cartaoId,
        ?int $compartimentoId,
        string $uid,
        ResultadoAcesso $resultado
    ): void {

        $status = $resultado->foiAutorizado() ? "AUTORIZADO" : "NEGADO";
        $consulta = $this->db->prepare(
            "INSERT INTO log_acessos
             (cartao_id, compartimento_id, uid_lido, data_hora, resultado)
             VALUES (?, ?, ?, ?, ?)"
        );
        $consulta->execute([
            $cartaoId,
            $compartimentoId,
            strtoupper(trim($uid)),
            (new DateTimeImmutable(
                "now",
                new DateTimeZone("America/Sao_Paulo")
            ))->format("Y-m-d H:i:s"),
            $status
        ]);
    }

    // Consulta o histórico completo em ordem cronológica inversa.
    public function buscarTodos(): array {

        return $this->db->query(
            "SELECT * FROM log_acessos ORDER BY data_hora DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    // Consulta as tentativas relacionadas a determinado cartão.
    public function buscarPorCartao(int $cartaoId): array {

        $s = $this->db->prepare(
            "SELECT * FROM log_acessos
             WHERE cartao_id=? ORDER BY data_hora DESC"
        );

        $s->execute([$cartaoId]);

        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}

class DashboardRepository {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function obterResumo(): array {

        $contagens = $this->db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM usuarios) AS usuarios_total,
                (SELECT COUNT(*) FROM cartoes) AS cartoes_total,
                (SELECT COALESCE(SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END), 0) FROM cartoes) AS cartoes_ativos,
                (SELECT COALESCE(SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END), 0) FROM cartoes) AS cartoes_inativos"
        );
        $contagens->execute();
        $resumo = $contagens->fetch(PDO::FETCH_ASSOC);

        $acessos = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN resultado = 'AUTORIZADO' THEN 1 ELSE 0 END), 0) AS acessos_autorizados,
                COALESCE(SUM(CASE WHEN resultado = 'NEGADO' THEN 1 ELSE 0 END), 0) AS acessos_negados
             FROM log_acessos"
        );
        $acessos->execute();

        return array_merge($resumo, $acessos->fetch(PDO::FETCH_ASSOC));
    }

    public function buscarAcessosRecentes(int $limite = 10): array {

        if ($limite < 1 || $limite > 10) {
            throw new InvalidArgumentException('O limite de acessos deve estar entre 1 e 10.');
        }

        $consulta = $this->db->prepare(
            "SELECT l.id, l.uid_lido, l.data_hora, l.resultado, l.compartimento_id,
                    c.uid, u.nome AS proprietario
             FROM log_acessos l
             LEFT JOIN cartoes c ON c.id = l.cartao_id
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             ORDER BY l.data_hora DESC, l.id DESC
             LIMIT ?"
        );
        $consulta->bindValue(1, $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarAcessosPorDia(string $inicio, string $fim): array {

        $consulta = $this->db->prepare(
            "SELECT DATE(data_hora) AS data, COUNT(*) AS total,
                    SUM(CASE WHEN resultado = 'AUTORIZADO' THEN 1 ELSE 0 END) AS autorizados,
                    SUM(CASE WHEN resultado = 'NEGADO' THEN 1 ELSE 0 END) AS negados
             FROM log_acessos
             WHERE data_hora >= ? AND data_hora < ?
             GROUP BY DATE(data_hora)
             ORDER BY DATE(data_hora)"
        );
        $consulta->execute([$inicio, $fim]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
