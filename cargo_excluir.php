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

if (!$is_admin) {
    // Funcionário/Expositor só pode excluir cargo da própria empresa
    $stmtAtual = $conn->prepare("SELECT ID_EMPRESA FROM CARGOS WHERE ID = ?");
    $stmtAtual->bind_param("i", $id);
    $stmtAtual->execute();
    $cargoAtual = $stmtAtual->get_result()->fetch_assoc();

    if (!$cargoAtual) {
        die("Cargo não encontrado.");
    }

    if ($cargoAtual["ID_EMPRESA"] != intval($_SESSION["empresa_id"])) {
        die("Você não tem permissão para excluir este cargo.");
    }
}

$sql  = "DELETE FROM CARGOS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: cargos.php");
    exit;
} else {
    echo "Erro ao excluir: " . $conn->error;
}
?>