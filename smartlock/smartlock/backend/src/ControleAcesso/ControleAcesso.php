
<?php

require_once __DIR__ . "/../PessoasCartoes/PessoasCartoes.php";
require_once __DIR__ . "/../Compartilhado/ObjetosValor.php";
require_once __DIR__ . "/../PermissoesAcesso/PermissoesAcesso.php";

/*
 * ENTIDADE COMPARTIMENTO
 *
 * Representa um espaço protegido pelo Smart Lock.
 * Possui identidade própria e pode estar ativo ou inativo.
 */
class Compartimento {

    private int $id;
    private string $nome;
    private bool $ativo;

    public function __construct(
        int $id,
        string $nome,
        bool $ativo = true
    ) {
        if (trim($nome) === "") {
            throw new InvalidArgumentException(
                "O nome do compartimento não pode ser vazio."
            );
        }

        $this->id = $id;
        $this->nome = $nome;
        $this->ativo = $ativo;
    }

    public function estaAtivo(): bool {
        return $this->ativo;
    }

    public function alterarStatus(bool $ativo): void {
        $this->ativo = $ativo;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getNome(): string {
        return $this->nome;
    }
}


/*
 * ENTIDADE DISPOSITIVO ESP32
 *
 * Representa o equipamento responsável por
 * realizar a comunicação com o sistema Smart Lock.
 */
class DispositivoESP32 {

    private int $id;
    private TokenDispositivo $token;
    private bool $ativo;
    private ?int $compartimentoId;

    public function __construct(
        int $id,
        TokenDispositivo $token,
        bool $ativo = true,
        ?int $compartimentoId = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->ativo = $ativo;
        $this->compartimentoId = $compartimentoId;
    }

    public function estaAtivo(): bool {
        return $this->ativo;
    }

    public function validarToken(TokenDispositivo $token): bool {
        return $this->token->equals($token);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCompartimentoId(): ?int {
        return $this->compartimentoId;
    }
}


/*
 * SERVIÇO DE AUTORIZACAO
 *
 * Reúne as regras necessárias para decidir
 * se uma tentativa de acesso pode ser liberada.
 */
class ServicoAutorizacao {

    private GerenciamentoPermissoes $gerenciamentoPermissoes;

    public function __construct(
        GerenciamentoPermissoes $gerenciamentoPermissoes
    ) {
        $this->gerenciamentoPermissoes = $gerenciamentoPermissoes;
    }

    public function autorizar(
        Usuario $usuario,
        CartaoRFID $cartao,
        Compartimento $compartimento,
        DispositivoESP32 $dispositivo,
        TokenDispositivo $token
    ): ResultadoAcesso {

        // O usuário precisa estar ativo.
        if (!$usuario->estaAtivo()) {
            return new ResultadoAcesso(false, "Usuário inativo.");
        }

        // O cartão precisa estar ativo.
        if (!$cartao->estaAtivo()) {
            return new ResultadoAcesso(false, "Cartão inativo.");
        }

        if (!$cartao->podeAcessar()) {
            return new ResultadoAcesso(false, "Cartão sem liberação de acesso.");
        }

        // O compartimento precisa estar ativo.
        if (!$compartimento->estaAtivo()) {
            return new ResultadoAcesso(false, "Compartimento inativo.");
        }

        // O dispositivo precisa estar ativo.
        if (!$dispositivo->estaAtivo()) {
            return new ResultadoAcesso(false, "Dispositivo inativo.");
        }

        if ($dispositivo->getCompartimentoId() !== $compartimento->getId()) {
            return new ResultadoAcesso(false, "Dispositivo não vinculado ao compartimento.");
        }

        // O token recebido precisa corresponder ao cadastrado.
        if (!$dispositivo->validarToken($token)) {
            return new ResultadoAcesso(false, "Token inválido.");
        }

        // Verifica se o cartão pertence ao usuário informado.
        $cartaoPertenceAoUsuario = false;

        foreach ($usuario->getCartoes() as $cartaoCadastrado) {
            if (
                $cartaoCadastrado->getUID()->equals(
                    $cartao->getUID()
                )
            ) {
                $cartaoPertenceAoUsuario = true;
                break;
            }
        }

        if (!$cartaoPertenceAoUsuario) {
            return new ResultadoAcesso(
                false,
                "Cartão não associado ao usuário."
            );
        }

        // Consulta se existe permissão ativa para o compartimento.
        $temPermissao = $this->gerenciamentoPermissoes
            ->possuiPermissao(
                $usuario->getId(),
                $compartimento->getId()
            );

        if (!$temPermissao) {
            return new ResultadoAcesso(
                false,
                "Usuário sem permissão para este compartimento."
            );
        }

        // Todas as verificações foram aprovadas.
        return new ResultadoAcesso(true, "Acesso autorizado.");
    }
}
