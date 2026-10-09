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

$id = intval($_GET["id"] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

if ($id === $usuarioLogadoId) {
    die("Você não pode excluir o seu próprio usuário.");
}

$st = $conn->prepare("SELECT ID AS ID, NOME AS NOME, EMAIL AS EMAIL, CATEGORIA_ID AS CATEGORIA_ID, EMPRESA_ID AS EMPRESA_ID FROM USUARIOS WHERE ID = ?");
$st->bind_param("i", $id);
$st->execute();
$usuario = $st->get_result()->fetch_assoc();
if (!$usuario) {
    die("Usuário não encontrado.");
}

$stmt = $conn->prepare("DELETE FROM USUARIOS WHERE ID = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if (function_exists("registrarLog")) {
        registrarLog("EXCLUSAO", "USUARIOS", dadosLog([
            "ID"           => $usuario["ID"],
            "NOME"         => $usuario["NOME"],
            "EMAIL"        => $usuario["EMAIL"],
            "CATEGORIA_ID" => $usuario["CATEGORIA_ID"],
            "EMPRESA_ID"   => $usuario["EMPRESA_ID"],
        ]));
    }
    header("Location: " . (!empty($usuario["EMPRESA_ID"]) ? "usuarios.php?empresa_id=" . (int)$usuario["EMPRESA_ID"] : "usuarios.php"));
    exit;
} else {
    echo "Erro ao excluir: " . $conn->error;
}
?>
