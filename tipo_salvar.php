<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$nome             = trim(strip_tags($_POST["nome"]));
$controla_espacos = trim(strip_tags($_POST["controla_espacos"]));

if ($nome == "") {
    die("Preencha o nome do tipo.");
}

if (!in_array($controla_espacos, ["S", "N"])) {
    die("Valor inválido para controla espaços.");
}

$sql  = "INSERT INTO TIPOS (NOME, CONTROLA_ESPACOS) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $nome, $controla_espacos);

if ($stmt->execute()) {
    header("Location: tipos.php");
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>