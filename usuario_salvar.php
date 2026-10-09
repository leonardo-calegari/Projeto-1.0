<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include_once("funcoes_log.php");

if (!ehAdmin()) {
    http_response_code(403);
    die("Acesso restrito ao administrador.");
}

$nome         = trim(strip_tags($_POST["nome"] ?? ""));
$email        = trim(strip_tags($_POST["email"] ?? ""));
$senha        = trim($_POST["senha"] ?? "");
$categoria_id = intval($_POST["categoria_id"] ?? 0);
$empresa_id   = intval($_POST["empresa_id"] ?? 0);
$voltar_emp   = intval($_POST["voltar_empresa"] ?? 0);

if ($nome === "" || $email === "" || $senha === "") {
    die("Preencha todos os campos.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("E-mail inválido.");
}

if (!in_array($categoria_id, [1, 2, 3], true)) {
    die("Categoria inválida.");
}

// Expositor (3) precisa de uma empresa; as demais categorias não têm empresa
if ($categoria_id === 3) {
    $st = $conn->prepare("SELECT ID FROM EMPRESAS WHERE ID = ?");
    $st->bind_param("i", $empresa_id);
    $st->execute();
    if (!$st->get_result()->fetch_assoc()) {
        die("Selecione a empresa do expositor.");
    }
} else {
    $empresa_id = null;
}

// E-mail único
$st = $conn->prepare("SELECT ID FROM USUARIOS WHERE EMAIL = ?");
$st->bind_param("s", $email);
$st->execute();
if ($st->get_result()->fetch_assoc()) {
    die("Já existe um usuário com este e-mail.");
}

$senhaHash = md5($senha);

$stmt = $conn->prepare("INSERT INTO USUARIOS (EMAIL, NOME, SENHA, CATEGORIA_ID, EMPRESA_ID) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssii", $email, $nome, $senhaHash, $categoria_id, $empresa_id);

if ($stmt->execute()) {
    if (function_exists("registrarLog")) {
        registrarLog("INCLUSAO", "USUARIOS", dadosLog([
            "ID"           => $conn->insert_id,
            "NOME"         => $nome,
            "EMAIL"        => $email,
            "CATEGORIA_ID" => $categoria_id,
            "EMPRESA_ID"   => $empresa_id,
        ]));
    }
    header("Location: " . ($voltar_emp > 0 ? "usuarios.php?empresa_id=" . $voltar_emp : "usuarios.php"));
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>
