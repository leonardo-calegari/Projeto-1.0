<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$id = intval($_GET["id"]);

if ($id <= 0) {
    die("ID inválido.");
}

$stmtAtual = $conn->prepare("SELECT EMPRESA_ID FROM PESSOAS WHERE ID = ?");
$stmtAtual->bind_param("i", $id);
$stmtAtual->execute();
$pessoaAtual = $stmtAtual->get_result()->fetch_assoc();

if (!$pessoaAtual) {
    die("Pessoa não encontrada.");
}

if (!$is_admin && $pessoaAtual["EMPRESA_ID"] != intval($_SESSION["empresa_id"])) {
    die("Você não tem permissão para excluir esta pessoa.");
}

// Exclusão lógica (mesmo padrão usado em EMPRESAS)
$sql  = "UPDATE PESSOAS SET EXCLUIDO_EM = NOW() WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $destino = $is_admin ? "pessoas.php" : "pessoas_empresa.php?empresa_id=" . intval($_SESSION["empresa_id"]);
    header("Location: " . $destino);
    exit;
} else {
    echo "Erro ao excluir: " . $conn->error;
}
?>