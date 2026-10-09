<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("config.php");

$id            = intval($_POST["id"]);
$nome_fantasia = trim(strip_tags($_POST["nome_fantasia"]));
$razao_social  = trim(strip_tags($_POST["razao_social"]));
$cnpj          = trim(strip_tags($_POST["cnpj"]));
$tipo_id       = intval($_POST["tipo_id"] ?? 0);

if ($id <= 0 || $nome_fantasia == "" || $razao_social == "" || $tipo_id <= 0) {
    die("Dados inválidos.");
}

// O CNPJ só é obrigatório se estiver assim em config.php
if (CNPJ_OBRIGATORIO && $cnpj == "") {
    die("O CNPJ é obrigatório.");
}
$cnpj = ($cnpj === "") ? null : $cnpj;

// Tipo escolhido: define se a empresa controla espaços
$stmt_tipo = $conn->prepare("SELECT CONTROLA_ESPACOS FROM TIPOS WHERE ID = ?");
$stmt_tipo->bind_param("i", $tipo_id);
$stmt_tipo->execute();
$res_tipo = $stmt_tipo->get_result()->fetch_assoc();

if (!$res_tipo) {
    die("Tipo inválido.");
}

if ($res_tipo["CONTROLA_ESPACOS"] === "S") {
    $quantidade_espacos = intval($_POST["quantidade_espacos"] ?? 0);
} else {
    $quantidade_espacos = 0;
}

$sql  = "UPDATE EMPRESAS
         SET NOME_FANTASIA = ?, RAZAO_SOCIAL = ?, CNPJ = ?, TIPO_ID = ?, QUANTIDADE_ESPACOS = ?, ATUALIZADO_EM = CURRENT_TIMESTAMP
         WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiii", $nome_fantasia, $razao_social, $cnpj, $tipo_id, $quantidade_espacos, $id);

if ($stmt->execute()) {
    header("Location: empresa.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?><?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("config.php");

$id            = intval($_POST["id"]);
$nome_fantasia = trim(strip_tags($_POST["nome_fantasia"]));
$razao_social  = trim(strip_tags($_POST["razao_social"]));
$cnpj          = trim(strip_tags($_POST["cnpj"]));
$tipo_id       = intval($_POST["tipo_id"] ?? 0);

if ($id <= 0 || $nome_fantasia == "" || $razao_social == "" || $tipo_id <= 0) {
    die("Dados inválidos.");
}

// O CNPJ só é obrigatório se estiver assim em config.php
if (CNPJ_OBRIGATORIO && $cnpj == "") {
    die("O CNPJ é obrigatório.");
}
$cnpj = ($cnpj === "") ? null : $cnpj;

// Tipo escolhido: define se a empresa controla espaços
$stmt_tipo = $conn->prepare("SELECT CONTROLA_ESPACOS FROM TIPOS WHERE ID = ?");
$stmt_tipo->bind_param("i", $tipo_id);
$stmt_tipo->execute();
$res_tipo = $stmt_tipo->get_result()->fetch_assoc();

if (!$res_tipo) {
    die("Tipo inválido.");
}

if ($res_tipo["CONTROLA_ESPACOS"] === "S") {
    $quantidade_espacos = intval($_POST["quantidade_espacos"] ?? 0);
} else {
    $quantidade_espacos = 0;
}

$sql  = "UPDATE EMPRESAS
         SET NOME_FANTASIA = ?, RAZAO_SOCIAL = ?, CNPJ = ?, TIPO_ID = ?, QUANTIDADE_ESPACOS = ?, ATUALIZADO_EM = CURRENT_TIMESTAMP
         WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiii", $nome_fantasia, $razao_social, $cnpj, $tipo_id, $quantidade_espacos, $id);

if ($stmt->execute()) {
    header("Location: empresa.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>