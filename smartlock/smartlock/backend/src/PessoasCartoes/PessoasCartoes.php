
<?php

require_once __DIR__ . "/../Compartilhado/ObjetosValor.php";

/*
 * ENTIDADE USUARIO
 *
 * Representa uma pessoa cadastrada no sistema Smart Lock.
 * Possui identidade própria e pode estar ativa ou inativa.
 */
class Usuario {

    private int $id;
    private string $nome;
    private bool $ativo;

    // Cartões RFID associados ao usuário.
    private array $cartoes = [];

    public function __construct(
        int $id,
        string $nome,
        bool $ativo = true
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->ativo = $ativo;
    }

    // Verifica se o usuário está ativo.
    public function estaAtivo(): bool {
        return $this->ativo;
    }

    // Permite ativar ou desativar o usuário.
    public function alterarStatus(bool $ativo): void {
        $this->ativo = $ativo;
    }

    // Associa um cartão RFID ao usuário.
    public function adicionarCartao(CartaoRFID $cartao): void {

        // Impede a associação duplicada do mesmo cartão
        // ao mesmo usuário.
        foreach ($this->cartoes as $cartaoExistente) {
            if (
                $cartaoExistente->getUID()->getValor()
                === $cartao->getUID()->getValor()
            ) {
                throw new InvalidArgumentException(
                    "Este cartão já está associado ao usuário."
                );
            }
        }

        $this->cartoes[] = $cartao;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function getCartoes(): array {
        return $this->cartoes;
    }
}


/*
 * ENTIDADE CARTAORFID
 *
 * Representa um cartão utilizado para identificar
 * um usuário durante uma tentativa de acesso.
 */
class CartaoRFID {

    private int $id;
    private UID $uid;
    private bool $ativo;
    private bool $podeAcessar;

    public function __construct(
        int $id,
        UID $uid,
        bool $ativo = true,
        bool $podeAcessar = false
    ) {
        $this->id = $id;
        $this->uid = $uid;
        $this->ativo = $ativo;
        $this->podeAcessar = $podeAcessar;
    }

    // Verifica se o cartão está ativo.
    public function estaAtivo(): bool {
        return $this->ativo;
    }

    // Permite bloquear ou desbloquear o cartão.
    public function alterarStatus(bool $ativo): void {
        $this->ativo = $ativo;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getUID(): UID {
        return $this->uid;
    }

    public function podeAcessar(): bool {
        return $this->podeAcessar;
    }
}
