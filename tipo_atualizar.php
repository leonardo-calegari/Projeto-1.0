<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$id               = intval($_POST["id"]);
$nome             = trim(strip_tags($_POST["nome"]));
$controla_espacos = trim(strip_tags($_POST["controla_espacos"]));

if ($id <= 0 || $nome == "") {
    die("Dados inválidos.");
}

if (!in_array($controla_espacos, ["S", "N"])) {
    die("Valor inválido para controla espaços.");
}

$sql  = "UPDATE TIPOS SET NOME = ?, CONTROLA_ESPACOS = ? WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nome, $controla_espacos, $id);

if ($stmt->execute()) {
    header("Location: tipos.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>