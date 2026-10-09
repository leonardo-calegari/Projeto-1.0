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

$id           = intval($_POST["id"] ?? 0);
$nome         = trim(strip_tags($_POST["nome"] ?? ""));
$email        = trim(strip_tags($_POST["email"] ?? ""));
$senha        = trim($_POST["senha"] ?? "");
$categoria_id = intval($_POST["categoria_id"] ?? 0);
$empresa_id   = intval($_POST["empresa_id"] ?? 0);

if ($id <= 0 || $nome === "" || $email === "") {
    die("Dados inválidos.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("E-mail inválido.");
}

if (!in_array($categoria_id, [1, 2, 3], true)) {
    die("Categoria inválida.");
}

// Dados atuais (para validar e para o log)
$st = $conn->prepare("SELECT ID AS ID, NOME AS NOME, EMAIL AS EMAIL, CATEGORIA_ID AS CATEGORIA_ID, EMPRESA_ID AS EMPRESA_ID FROM USUARIOS WHERE ID = ?");
$st->bind_param("i", $id);
$st->execute();
$antigo = $st->get_result()->fetch_assoc();
if (!$antigo) {
    die("Usuário não encontrado.");
}

// O administrador não pode tirar o próprio acesso
if ($id === $usuarioLogadoId && $categoria_id !== 1) {
    die("Você não pode remover o seu próprio acesso de administrador.");
}

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

// E-mail único (ignorando o próprio usuário)
$st = $conn->prepare("SELECT ID FROM USUARIOS WHERE EMAIL = ? AND ID <> ?");
$st->bind_param("si", $email, $id);
$st->execute();
if ($st->get_result()->fetch_assoc()) {
    die("Já existe outro usuário com este e-mail.");
}

if ($senha !== "") {
    $senhaHash = md5($senha);
    $stmt = $conn->prepare("UPDATE USUARIOS SET NOME = ?, EMAIL = ?, SENHA = ?, CATEGORIA_ID = ?, EMPRESA_ID = ? WHERE ID = ?");
    $stmt->bind_param("sssiii", $nome, $email, $senhaHash, $categoria_id, $empresa_id, $id);
} else {
    $stmt = $conn->prepare("UPDATE USUARIOS SET NOME = ?, EMAIL = ?, CATEGORIA_ID = ?, EMPRESA_ID = ? WHERE ID = ?");
    $stmt->bind_param("ssiii", $nome, $email, $categoria_id, $empresa_id, $id);
}

if ($stmt->execute()) {
    if (function_exists("registrarLog")) {
        // Guarda os dados antigos e os novos (a senha nunca vai para o log)
        registrarLog("ALTERACAO", "USUARIOS", dadosLog([
            "ANTES_ID"           => $antigo["ID"],
            "ANTES_NOME"         => $antigo["NOME"],
            "ANTES_EMAIL"        => $antigo["EMAIL"],
            "ANTES_CATEGORIA_ID" => $antigo["CATEGORIA_ID"],
            "ANTES_EMPRESA_ID"   => $antigo["EMPRESA_ID"],
            "DEPOIS_NOME"         => $nome,
            "DEPOIS_EMAIL"        => $email,
            "DEPOIS_CATEGORIA_ID" => $categoria_id,
            "DEPOIS_EMPRESA_ID"   => $empresa_id,
            "SENHA_ALTERADA"      => $senha !== "" ? "S" : "N",
        ]));
    }
    header("Location: " . ($empresa_id ? "usuarios.php?empresa_id=" . $empresa_id : "usuarios.php"));
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>
