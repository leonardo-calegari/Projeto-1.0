<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$id = intval($_GET["id"]);

if ($id <= 0) {
    die("ID inválido.");
}

$sql  = "DELETE FROM PESSOAS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: pessoas.php");
    exit;
} else {
    echo "Erro ao excluir: " . $conn->error;
}
?>