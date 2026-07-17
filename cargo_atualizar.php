<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$id   = intval($_POST["id"]);
$nome = trim(strip_tags($_POST["nome"]));

if ($id <= 0) {
    die("ID inválido.");
}

if ($nome == "") {
    die("Preencha o nome do cargo.");
}

$sql  = "UPDATE CARGOS SET NOME = ? WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nome, $id);

if ($stmt->execute()) {
    header("Location: cargos.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>