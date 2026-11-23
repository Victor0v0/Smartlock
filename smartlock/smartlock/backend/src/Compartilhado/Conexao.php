
<?php

// Classe responsável por estabelecer a conexão
// entre o sistema Smart Lock e o banco de dados MySQL.
class Conexao {

    // Método responsável por criar e retornar
    // a conexão com o banco de dados.
    public static function conectar(): PDO {

        // Configurações de acesso ao banco de dados.
        $host = "127.0.0.1";
        $porta = "3306";
        $banco = "controle_rfid";
        $usuario = "root";
        $senha = "";

        try {

            // Estabelece a conexão utilizando PDO.
            $conexao = new PDO(
                "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
                $usuario,
                $senha
            );

            // Define que os erros do banco serão tratados
            // por meio de exceções.
            $conexao->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            // Retorna a conexão para ser utilizada
            // pelos repositórios do sistema.
            return $conexao;

        } catch (PDOException $erro) {

            // Informa que ocorreu uma falha na conexão.
            throw new RuntimeException(
                "Erro ao conectar ao banco de dados.",
                0,
                $erro
            );
        }
    }
}
