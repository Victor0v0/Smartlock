
<?php

require_once __DIR__ . "/../Compartilhado/ObjetosValor.php";

/*
 * ENTIDADE REGISTROACESSO
 *
 * Representa uma tentativa de acesso realizada no Smart Lock.
 * Guarda quem tentou acessar, qual compartimento foi solicitado,
 * quando ocorreu a tentativa e qual foi o resultado.
 */
class RegistroAcesso {

    private int $id;
    private int $usuarioId;
    private int $compartimentoId;
    private DateTimeImmutable $dataHora;
    private ResultadoAcesso $resultado;

    public function __construct(
        int $id,
        int $usuarioId,
        int $compartimentoId,
        ResultadoAcesso $resultado
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->compartimentoId = $compartimentoId;
        $this->dataHora = new DateTimeImmutable(
            "now",
            new DateTimeZone("America/Sao_Paulo")
        );
        $this->resultado = $resultado;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getUsuarioId(): int {
        return $this->usuarioId;
    }

    public function getCompartimentoId(): int {
        return $this->compartimentoId;
    }

    public function getDataHora(): DateTimeImmutable {
        return $this->dataHora;
    }

    public function getResultado(): ResultadoAcesso {
        return $this->resultado;
    }
}


/*
 * SERVICO DE HISTORICO
 *
 * Responsável por armazenar e consultar os registros
 * das tentativas de acesso ao sistema.
 */
class ServicoHistorico {

    private array $registros = [];

    // Registra uma tentativa de acesso, autorizada ou negada.
    public function registrar(RegistroAcesso $registro): void {
        $this->registros[] = $registro;
    }

    // Retorna todos os registros armazenados.
    public function consultarTodos(): array {
        return $this->registros;
    }

    // Retorna somente os registros de determinado usuário.
    public function consultarPorUsuario(int $usuarioId): array {

        $resultado = [];

        foreach ($this->registros as $registro) {
            if ($registro->getUsuarioId() === $usuarioId) {
                $resultado[] = $registro;
            }
        }

        return $resultado;
    }

    // Retorna somente as tentativas que foram autorizadas.
    public function consultarAutorizados(): array {

        $resultado = [];

        foreach ($this->registros as $registro) {
            if ($registro->getResultado()->foiAutorizado()) {
                $resultado[] = $registro;
            }
        }

        return $resultado;
    }

    // Retorna somente as tentativas que foram negadas.
    public function consultarNegados(): array {

        $resultado = [];

        foreach ($this->registros as $registro) {
            if (!$registro->getResultado()->foiAutorizado()) {
                $resultado[] = $registro;
            }
        }

        return $resultado;
    }
}
