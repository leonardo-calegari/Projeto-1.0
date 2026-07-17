<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_upload.php");

$id                  = intval($_POST["id"]);
$empresa_id          = intval($_POST["empresa_id"]);
$cargo_id            = !empty($_POST["cargo_id"]) ? intval($_POST["cargo_id"]) : null;
$nome                = trim(strip_tags($_POST["nome"]));
$cpf                 = trim(strip_tags($_POST["cpf"]));
$documento           = trim(strip_tags($_POST["documento"]));
$telefone            = trim(strip_tags($_POST["telefone"]));
$ingresso_permanente = trim(strip_tags($_POST["ingresso_permanente"]));
$foto_atual          = trim($_POST["foto_atual"]);

if ($id <= 0) {
    die("ID inválido.");
}

if ($empresa_id <= 0 || $nome == "") {
    die("Preencha os campos obrigatórios.");
}

if (!in_array($ingresso_permanente, ["S", "N"])) {
    die("Valor inválido para ingresso permanente.");
}

// Se o usuário escolher uma foto nova, ela substitui a antiga.
// Caso contrário, mantém o nome de arquivo que já estava salvo.
$foto = processarUploadFoto("foto", $foto_atual);

$sql  = "UPDATE PESSOAS
         SET EMPRESA_ID = ?, CARGO_ID = ?, NOME = ?, CPF = ?, DOCUMENTO = ?, TELEFONE = ?, INGRESSO_PERMANENTE = ?, FOTO = ?
         WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissssssi", $empresa_id, $cargo_id, $nome, $cpf, $documento, $telefone, $ingresso_permanente, $foto, $id);

if ($stmt->execute()) {
    header("Location: pessoas.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>