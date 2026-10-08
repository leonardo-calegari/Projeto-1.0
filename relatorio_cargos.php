<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include("relatorio_pdf.php");

$busca = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";

// Mesma regra da tela de cargos:
// admin pode filtrar por empresa; os demais veem só a empresa da sessão
$filtro_emp = 0;
if (ehAdmin()) {
    if (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) {
        $filtro_emp = (int)$_GET["empresa_id"];
    }
} else {
    $filtro_emp = (int)($empresaLogadaId ?? 0);
    if ($filtro_emp <= 0) {
        http_response_code(403);
        exit("Usuário sem empresa vinculada.");
    }
}

$condicoes = [];
$params    = [];
$tipos     = "";

if ($filtro_emp > 0) {
    $condicoes[] = "C.ID_EMPRESA = ?";
    $params[]    = $filtro_emp;
    $tipos      .= "i";
}

// A tela pesquisa só pelo nome do cargo
if ($busca !== "") {
    $condicoes[] = "UPPER(C.NOME) LIKE UPPER(?)";
    $params[]    = "%" . $busca . "%";
    $tipos      .= "s";
}

$sql = "SELECT C.ID, C.NOME, E.NOME_FANTASIA
        FROM CARGOS C
        INNER JOIN EMPRESAS E ON E.ID = C.ID_EMPRESA";
if ($condicoes) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY E.NOME_FANTASIA, C.NOME";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// A coluna Empresa só aparece para o admin, como na tela
$colunas = ehAdmin() ? ["ID", "Empresa", "Nome"] : ["ID", "Nome"];

$linhas = [];
while ($r = $result->fetch_assoc()) {
    $linhas[] = ehAdmin()
        ? [$r["ID"], $r["NOME_FANTASIA"], $r["NOME"]]
        : [$r["ID"], $r["NOME"]];
}

// Texto do filtro aplicado
$partes = [];
if ($filtro_emp > 0) {
    $st = $conn->prepare("SELECT NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
    $st->bind_param("i", $filtro_emp);
    $st->execute();
    $emp = $st->get_result()->fetch_assoc();
    if ($emp) $partes[] = "Empresa: " . $emp["NOME_FANTASIA"];
}
if ($busca !== "") {
    $partes[] = 'Pesquisa: "' . $busca . '"';
}

gerarRelatorioPdf(
    "Cargos",
    $colunas,
    $linhas,
    implode(" | ", $partes),
    "relatorio_cargos.pdf"
);