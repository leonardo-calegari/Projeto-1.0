<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
// Administrador (e funcionário sem empresa vinculada) escolhem a empresa.
// Expositor e funcionário vinculado usam sempre a empresa da sessão.
$empresa_id_sessao = intval($_SESSION["empresa_id"] ?? 0);
$is_admin = ($categoria_id == 1) || ($categoria_id == 2 && $empresa_id_sessao <= 0);

$id   = intval($_POST["id"] ?? 0);
$nome = trim(strip_tags($_POST["nome"] ?? ""));

if ($id <= 0) {
    die("ID inválido.");
}

if ($nome == "") {
    die("Preencha o nome do cargo.");
}


$stmtAtual = $conn->prepare("SELECT ID_EMPRESA FROM CARGOS WHERE ID = ?");
$stmtAtual->bind_param("i", $id);
$stmtAtual->execute();
$cargoAtual = $stmtAtual->get_result()->fetch_assoc();

if (!$cargoAtual) {
    die("Cargo não encontrado.");
}

if ($is_admin) {
    // Admin pode inclusive mudar o cargo de empresa
    $empresa_id = intval($_POST["empresa_id"] ?? 0);

    if ($empresa_id <= 0) {
        die("Selecione a empresa.");
    }

    $chk = $conn->prepare("SELECT ID FROM EMPRESAS WHERE ID = ? AND EXCLUIDO_EM IS NULL");
    $chk->bind_param("i", $empresa_id);
    $chk->execute();
    if ($chk->get_result()->num_rows == 0) {
        die("Empresa inválida.");
    }
} else {

    if ($cargoAtual["ID_EMPRESA"] != intval($_SESSION["empresa_id"])) {
        die("Você não tem permissão para atualizar este cargo.");
    }

    $empresa_id = intval($_SESSION["empresa_id"]);
}

$sql  = "UPDATE CARGOS SET ID_EMPRESA = ?, NOME = ? WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $empresa_id, $nome, $id);

if ($stmt->execute()) {
    header("Location: cargos.php");
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>