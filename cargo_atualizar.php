<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$id   = intval($_POST["id"]);
$nome = trim(strip_tags($_POST["nome"]));

if ($id <= 0) {
    die("ID inválido.");
}

if ($nome == "") {
    die("Preencha o nome do cargo.");
}

// Busca o cargo atual para validar a que empresa ele pertence
$stmtAtual = $conn->prepare("SELECT ID_EMPRESA FROM CARGOS WHERE ID = ?");
$stmtAtual->bind_param("i", $id);
$stmtAtual->execute();
$cargoAtual = $stmtAtual->get_result()->fetch_assoc();

if (!$cargoAtual) {
    die("Cargo não encontrado.");
}

if ($is_admin) {
    // Admin pode inclusive mudar o cargo de empresa
    $empresa_id = intval($_POST["empresa_id"]);

    if ($empresa_id <= 0) {
        die("Selecione a empresa.");
    }
} else {
    // Funcionário/Expositor só pode atualizar cargo da própria empresa,
    // e a empresa do cargo não muda (vem sempre da sessão)
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