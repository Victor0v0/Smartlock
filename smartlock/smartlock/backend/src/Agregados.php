
<?php

require_once __DIR__ . "/PessoasCartoes/PessoasCartoes.php";
require_once __DIR__ . "/ControleAcesso/ControleAcesso.php";
require_once __DIR__ . "/HistoricoConsulta/HistoricoConsulta.php";

/*
 * AGREGADO DE USUARIO
 *
 * A entidade Usuario é a raiz deste agregado.
 *
 * Sua responsabilidade é controlar as operações relacionadas
 * ao usuário e aos cartões RFID associados a ele.
 *
 * As outras partes do sistema devem utilizar os métodos
 * disponibilizados pelo agregado, sem acessar diretamente
 * sua entidade interna.
 */
class AgregadoUsuario {

    // Raiz do agregado: entidade responsável pelas regras do usuário.
    private Usuario $raiz;

    public function __construct(Usuario $usuario) {
        $this->raiz = $usuario;
    }

    /*
     * Associa um cartão ao usuário.
     *
     * A operação passa pela entidade raiz, que aplica
     * as regras de associação definidas no domínio.
     */
    public function adicionarCartao(CartaoRFID $cartao): void {
        $this->raiz->adicionarCartao($cartao);
    }

    /*
     * Verifica se determinado cartão pertence ao usuário.
     *
     * A consulta é feita internamente, sem entregar
     * a lista de cartões para alteração externa.
     */
    public function possuiCartao(CartaoRFID $cartao): bool {

        foreach ($this->raiz->getCartoes() as $cartaoCadastrado) {

            if (
                $cartaoCadastrado->getUID()->equals(
                    $cartao->getUID()
                )
            ) {
                return true;
            }
        }

        return false;
    }

    // Retorna os UIDs dos cartões associados, sem expor as entidades.
    public function listarUIDsCartoes(): array {

        $uids = [];

        foreach ($this->raiz->getCartoes() as $cartao) {
            $uids[] = $cartao->getUID()->getValor();
        }

        return $uids;
    }

    // Consulta o identificador do usuário.
    public function getId(): int {
        return $this->raiz->getId();
    }

    // Consulta o nome do usuário.
    public function getNome(): string {
        return $this->raiz->getNome();
    }

    // Consulta se o usuário está ativo.
    public function estaAtivo(): bool {
        return $this->raiz->estaAtivo();
    }

    // Altera a situação do usuário por meio da raiz.
    public function alterarStatus(bool $ativo): void {
        $this->raiz->alterarStatus($ativo);
    }
}


/*
 * AGREGADO DE COMPARTIMENTO
 *
 * A entidade Compartimento é a raiz deste agregado.
 *
 * Ela controla as informações e o estado do espaço
 * protegido pelo sistema Smart Lock.
 *
 * As alterações de estado são realizadas por meio
 * dos métodos disponibilizados pelo agregado.
 */
class AgregadoCompartimento {

    // Raiz do agregado: entidade Compartimento.
    private Compartimento $raiz;

    public function __construct(Compartimento $compartimento) {
        $this->raiz = $compartimento;
    }

    // Ativa ou desativa o compartimento.
    public function alterarStatus(bool $ativo): void {
        $this->raiz->alterarStatus($ativo);
    }

    // Consulta a situação atual do compartimento.
    public function estaAtivo(): bool {
        return $this->raiz->estaAtivo();
    }

    // Consulta o identificador do compartimento.
    public function getId(): int {
        return $this->raiz->getId();
    }

    // Consulta o nome do compartimento.
    public function getNome(): string {
        return $this->raiz->getNome();
    }
}

/*
 * AGREGADO DE REGISTRO DE ACESSO
 *
 * A entidade RegistroAcesso é a raiz deste agregado.
 *
 * Sua responsabilidade é controlar o acesso às informações
 * de uma tentativa de acesso, mantendo seus dados protegidos.
 */
class AgregadoRegistroAcesso {

    // Entidade raiz do agregado.
    private RegistroAcesso $raiz;

    public function __construct(RegistroAcesso $registro) {
        $this->raiz = $registro;
    }

    // Consulta o identificador do registro.
    public function getId(): int {
        return $this->raiz->getId();
    }

    // Consulta o identificador do usuário.
    public function getUsuarioId(): int {
        return $this->raiz->getUsuarioId();
    }

    // Consulta o identificador do compartimento.
    public function getCompartimentoId(): int {
        return $this->raiz->getCompartimentoId();
    }

    // Consulta a data e o horário do registro.
    public function getDataHora(): DateTimeImmutable {
        return $this->raiz->getDataHora();
    }

    // Consulta o resultado da tentativa de acesso.
    public function getResultado(): ResultadoAcesso {
        return $this->raiz->getResultado();
    }
}
