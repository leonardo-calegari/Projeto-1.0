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

// Expositor (e funcionário vinculado a uma empresa) só enxergam a própria empresa
$empresaSessao = (int)($empresaLogadaId ?? 0);
if (ehExpositor() && $empresaSessao <= 0) {
    http_response_code(403);
    exit("Usuário sem empresa vinculada.");
}
if (ehExpositor() || (ehFuncionario() && $empresaSessao > 0)) {
    $filtro_emp = $empresaSessao;
}

$condicoes = ["P.EXCLUIDO_EM IS NULL"];
$params    = [];
$tipos     = "";

if ($filtro_emp > 0) {
    $condicoes[] = "P.EMPRESA_ID = ?";
    $params[]    = $filtro_emp;
    $tipos      .= "i";
}

// Mesma pesquisa da tela de pessoas
if ($busca !== "") {
    $condicoes[] = "(UPPER(P.NOME) LIKE UPPER(?)
                  OR UPPER(E.NOME_FANTASIA) LIKE UPPER(?)
                  OR UPPER(C.NOME) LIKE UPPER(?)
                  OR UPPER(P.CPF) LIKE UPPER(?)
                  OR UPPER(P.TELEFONE) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo, $termo, $termo, $termo);
    $tipos .= "sssss";
}

$sql = "SELECT P.ID, P.NOME, P.CPF, P.TELEFONE, P.INGRESSO_PERMANENTE,
               E.NOME_FANTASIA, C.NOME AS NOME_CARGO
        FROM PESSOAS P
        INNER JOIN EMPRESAS E ON E.ID = P.EMPRESA_ID
        LEFT JOIN CARGOS C ON C.ID = P.CARGO_ID
        WHERE " . implode(" AND ", $condicoes) . "
        ORDER BY P.ID DESC";

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
        $r["NOME_FANTASIA"],
        !empty($r["NOME_CARGO"]) ? $r["NOME_CARGO"] : "—",
        $r["CPF"],
        $r["TELEFONE"],
        $r["INGRESSO_PERMANENTE"] === "S" ? "Sim" : "Não",
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
    "Pessoas",
    ["ID", "Nome", "Empresa", "Cargo", "CPF", "Telefone", "Permanente"],
    $linhas,
    implode(" | ", $partes),
    "relatorio_pessoas.pdf"
);
