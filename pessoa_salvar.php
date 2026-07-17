<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_upload.php");

$empresa_id          = intval($_POST["empresa_id"]);
$cargo_id            = !empty($_POST["cargo_id"]) ? intval($_POST["cargo_id"]) : null;
$nome                = trim(strip_tags($_POST["nome"]));
$cpf                 = trim(strip_tags($_POST["cpf"]));
$documento           = trim(strip_tags($_POST["documento"]));
$telefone            = trim(strip_tags($_POST["telefone"]));
$ingresso_permanente = trim(strip_tags($_POST["ingresso_permanente"]));

if ($empresa_id <= 0 || $nome == "") {
    die("Preencha os campos obrigatórios.");
}

if (!in_array($ingresso_permanente, ["S", "N"])) {
    die("Valor inválido para ingresso permanente.");
}

$foto = processarUploadFoto("foto");

$sql  = "INSERT INTO PESSOAS (EMPRESA_ID, CARGO_ID, NOME, CPF, DOCUMENTO, TELEFONE, INGRESSO_PERMANENTE, FOTO)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissssss", $empresa_id, $cargo_id, $nome, $cpf, $documento, $telefone, $ingresso_permanente, $foto);

if ($stmt->execute()) {
    header("Location: pessoas.php");
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}

$empresa_id = (int)$_POST["empresa_id"];
$categoria_id = 3;
?>