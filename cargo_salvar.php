<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$nome = trim(strip_tags($_POST["nome"]));

if ($nome == "") {
    die("Preencha o nome do cargo.");
}

$sql  = "INSERT INTO CARGOS (NOME) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome);

if ($stmt->execute()) {
    header("Location: cargos.php");
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>