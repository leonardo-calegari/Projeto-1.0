<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$nome = trim(strip_tags($_POST["nome"]));

if ($nome == "") {
    die("Preencha o nome do cargo.");
}

if ($is_admin) {
    // Admin escolhe a empresa pelo select
    $empresa_id = intval($_POST["empresa_id"]);

    if ($empresa_id <= 0) {
        die("Selecione a empresa.");
    }
} else {
    // Funcionário/Expositor: empresa vem da sessão, não do POST
    $empresa_id = intval($_SESSION["empresa_id"]);

    if ($empresa_id <= 0) {
        die("Usuário sem empresa vinculada.");
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