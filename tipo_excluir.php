<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_log.php");

$id = intval($_GET["id"]);

if ($id <= 0) {
    die("ID inválido.");
}

$antigo = buscarRegistro($conn, "TIPOS", $id);

$sql  = "DELETE FROM TIPOS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    registrarLog("EXCLUSAO", "TIPOS", dadosLog($antigo));

    header("Location: tipos.php");
    exit;
} else {
    echo "Erro ao excluir: " . $conn->error;
}
?>