
<?php

/*
 * ENTIDADE PERMISSAO
 *
 * Representa a autorização concedida a um usuário
 * para acessar determinado compartimento.
 */
class Permissao {

    private int $id;
    private int $usuarioId;
    private int $compartimentoId;
    private bool $ativa;

    public function __construct(
        int $id,
        int $usuarioId,
        int $compartimentoId,
        bool $ativa = true
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->compartimentoId = $compartimentoId;
        $this->ativa = $ativa;
    }

    // Verifica se a permissão está ativa.
    public function estaAtiva(): bool {
        return $this->ativa;
    }

    // Ativa ou desativa a permissão.
    public function alterarStatus(bool $ativa): void {
        $this->ativa = $ativa;
    }

    // Verifica se a permissão pertence ao usuário
    // e ao compartimento informados.
    public function corresponde(
        int $usuarioId,
        int $compartimentoId
    ): bool {
        return $this->usuarioId === $usuarioId
            && $this->compartimentoId === $compartimentoId;
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
}


/*
 * SERVIÇO DE GERENCIAMENTO DE PERMISSOES
 *
 * Responsável por verificar e administrar
 * as permissões de acesso dos usuários.
 */

class GerenciamentoPermissoes {

    // Mantém as permissões em memória, como na versão original.
    private array $permissoes = [];

    // Permite consultar as permissões persistidas no banco.
    private $verificadorBanco = null;

    // Cadastra uma permissão na lista em memória.
    public function adicionar(Permissao $permissao): void {
        $this->permissoes[] = $permissao;
    }

    // Configura uma função que consulta o repositório no MySQL.
    public function usarVerificadorBanco(callable $verificador): void {
        $this->verificadorBanco = $verificador;
    }

    // Verifica se o usuário possui autorização para o compartimento.
    public function possuiPermissao(
        int $usuarioId,
        int $compartimentoId
    ): bool {

        // Quando configurado, o banco é a fonte de verdade.
        if ($this->verificadorBanco !== null) {
            return (bool) call_user_func(
                $this->verificadorBanco,
                $usuarioId,
                $compartimentoId
            );
        }

        // Caso contrário, mantém a consulta em memória.
        foreach ($this->permissoes as $permissao) {

            if (
                $permissao->corresponde($usuarioId, $compartimentoId)
                && $permissao->estaAtiva()
            ) {
                return true;
            }
        }

        return false;
    }
}

