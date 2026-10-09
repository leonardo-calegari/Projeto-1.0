<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"] ?? 0);
// Administrador (e funcionário sem empresa vinculada) escolhem a empresa.
// Expositor e funcionário vinculado usam sempre a empresa da sessão.
$empresa_id_sessao = intval($_SESSION["empresa_id"] ?? 0);
$is_admin = ($categoria_id == 1) || ($categoria_id == 2 && $empresa_id_sessao <= 0);

$nome = trim(strip_tags($_POST["nome"] ?? ""));

if ($nome == "") {
    die("Preencha o nome do cargo.");
}

if ($is_admin) {
   
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

    $empresa_id = intval($_SESSION["empresa_id"] ?? 0);

    if ($empresa_id <= 0) {
        die("Usuário sem empresa vinculada. Saia do sistema e entre novamente ou peça ao administrador para vincular uma empresa ao seu usuário.");
    }
}

$sql  = "INSERT INTO CARGOS (ID_EMPRESA, NOME) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $empresa_id, $nome);

if ($stmt->execute()) {
    header("Location: cargos.php");
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>