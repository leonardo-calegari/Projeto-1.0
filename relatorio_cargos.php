<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include("relatorio_pdf.php");

$busca      = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";
$filtro_emp = (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) ? (int)$_GET["empresa_id"] : 0;

// Expositor (e funcionário vinculado a uma empresa) só enxergam os cargos da própria empresa
$empresaSessao = (int)($empresaLogadaId ?? 0);
if (ehExpositor() && $empresaSessao <= 0) {
    http_response_code(403);
    exit("Usuário sem empresa vinculada.");
}
if (ehExpositor() || (ehFuncionario() && $empresaSessao > 0)) {
    $filtro_emp = $empresaSessao;
}

$condicoes = [];
$params    = [];
$tipos     = "";

if ($filtro_emp > 0) {
    $condicoes[] = "C.ID_EMPRESA = ?";
    $params[]    = $filtro_emp;
    $tipos      .= "i";
}

if ($busca !== "") {
    $condicoes[] = "(UPPER(C.NOME) LIKE UPPER(?) OR UPPER(E.NOME_FANTASIA) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo);
    $tipos .= "ss";
}

$sql = "SELECT C.ID, C.NOME, E.NOME_FANTASIA
        FROM CARGOS C
        LEFT JOIN EMPRESAS E ON E.ID = C.ID_EMPRESA";
if ($condicoes) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY C.ID DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$linhas = [];
while ($r = $result->fetch_assoc()) {
    $linhas[] = [
        $r["ID"],
        $r["NOME"],
        !empty($r["NOME_FANTASIA"]) ? $r["NOME_FANTASIA"] : "—",
    ];
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
    ["ID", "Nome", "Empresa"],
    $linhas,
    implode(" | ", $partes),
    "relatorio_cargos.pdf"
);