<?php

require_once __DIR__ . "/../src/Agregados.php";

$resultado = new ResultadoAcesso(
    true,
    "Acesso autorizado"
);

$registro = new RegistroAcesso(
    1,
    1,
    1,
    $resultado
);

$agregado = new AgregadoRegistroAcesso($registro);

echo "ID: " . $agregado->getId() . "<br>";
echo "Usuário: " . $agregado->getUsuarioId() . "<br>";
echo "Compartimento: " . $agregado->getCompartimentoId() . "<br>";

echo "Data: " .
    $agregado->getDataHora()->format("d/m/Y H:i:s") .
    "<br>";

echo "Resultado: " .
    ($agregado->getResultado()->foiAutorizado()
        ? "Autorizado"
        : "Negado") .
    "<br>";

echo "Agregado de Registro de Acesso funcionando!";