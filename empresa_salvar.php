<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$nome_fantasia = trim(strip_tags($_POST["nome_fantasia"]));
$razao_social  = trim(strip_tags($_POST["razao_social"]));
$cnpj          = trim(strip_tags($_POST["cnpj"]));
$tipo_id       = intval($_POST["tipo_id"]);
$ano           = 2026;

if ($nome_fantasia == "" || $razao_social == "" || $cnpj == "" || $tipo_id <= 0) {
    die("Preencha todos os campos.");
}

// verificar se o tipo controla espaços
$stmt_tipo = $conn->prepare("SELECT CONTROLA_ESPACOS FROM TIPOS WHERE ID = ?");
$stmt_tipo->bind_param("i", $tipo_id);
$stmt_tipo->execute();
$res_tipo = $stmt_tipo->get_result()->fetch_assoc();

if ($res_tipo["CONTROLA_ESPACOS"] === "S") {
    $quantidade_espacos = intval($_POST["quantidade_espacos"]);
} else {
    $quantidade_espacos = 0;
}

$sql  = "INSERT INTO EMPRESAS (ANO, NOME_FANTASIA, RAZAO_SOCIAL, CNPJ, TIPO_ID, QUANTIDADE_ESPACOS) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssii", $ano, $nome_fantasia, $razao_social, $cnpj, $tipo_id, $quantidade_espacos);

if ($stmt->execute()) {
    header("Location: empresa.php");
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>