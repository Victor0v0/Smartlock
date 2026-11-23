
<?php

/*
 * OBJETO DE VALOR: UID
 *
 * Representa o identificador único de um cartão RFID.
 * Não possui identidade própria: seu valor identifica o cartão.
 */
class UID {

    private string $valor;

    public function __construct(string $valor) {

        // Impede a criação de um UID vazio.
        if (trim($valor) === "") {
            throw new InvalidArgumentException(
                "O UID do cartão não pode ser vazio."
            );
        }

        // Armazena o identificador do cartão.
        $this->valor = strtoupper(trim($valor));
    }

    public function getValor(): string {
        return $this->valor;
    }

    // Compara dois UIDs pelo seu conteúdo.
    public function equals(UID $outro): bool {
        return $this->valor === $outro->valor;
    }
}


/*
 * OBJETO DE VALOR: TOKEN DISPOSITIVO
 *
 * Representa a credencial utilizada para identificar
 * e validar um dispositivo ESP32.
 */
class TokenDispositivo {

    private string $valor;

    public function __construct(string $valor) {

        // Impede a utilização de um token vazio.
        if (trim($valor) === "") {
            throw new InvalidArgumentException(
                "O token do dispositivo não pode ser vazio."
            );
        }

        $this->valor = $valor;
    }

    public function getValor(): string {
        return $this->valor;
    }

    // Compara dois tokens pelo seu conteúdo.
    public function equals(TokenDispositivo $outro): bool {
        return hash_equals($this->valor, $outro->valor);
    }
}


/*
 * OBJETO DE VALOR: RESULTADO DE ACESSO
 *
 * Representa o resultado de uma tentativa de acesso,
 * indicando se ela foi autorizada ou negada.
 */
class ResultadoAcesso {

    private bool $autorizado;
    private string $motivo;

    public function __construct(
        bool $autorizado,
        string $motivo
    ) {
        $this->autorizado = $autorizado;
        $this->motivo = $motivo;
    }

    // Informa se o acesso foi autorizado.
    public function foiAutorizado(): bool {
        return $this->autorizado;
    }

    // Retorna o motivo do resultado.
    public function getMotivo(): string {
        return $this->motivo;
    }
}
