<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$id            = intval($_POST["id"]);
$nome_fantasia = trim(strip_tags($_POST["nome_fantasia"]));
$razao_social  = trim(strip_tags($_POST["razao_social"]));
$cnpj          = trim(strip_tags($_POST["cnpj"]));

if ($id <= 0 || $nome_fantasia == "" || $razao_social == "" || $cnpj == "") {
    die("Dados inválidos.");
}

$sql  = "UPDATE EMPRESAS SET NOME_FANTASIA = ?, RAZAO_SOCIAL = ?, CNPJ = ?, ATUALIZADO_EM = CURRENT_TIMESTAMP WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $nome_fantasia, $razao_social, $cnpj, $id);

if ($stmt->execute()) {
    header("Location: empresa.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>