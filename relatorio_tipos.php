<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include("relatorio_pdf.php");

// Só administrador (categoria 1), como a tela de tipos
if (!ehAdmin()) {
    http_response_code(403);
    die("Acesso restrito ao administrador.");
}

$result = $conn->query("SELECT ID, NOME, CONTROLA_ESPACOS FROM TIPOS ORDER BY ID DESC");

$linhas = [];
while ($r = $result->fetch_assoc()) {
    $linhas[] = [
        $r["ID"],
        $r["NOME"],
        $r["CONTROLA_ESPACOS"] == "S" ? "Sim" : "Não",
    ];
}

gerarRelatorioPdf(
    "Tipos",
    ["ID", "Nome", "Controla Espaços"],
    $linhas,
    "",
    "relatorio_tipos.pdf"
);