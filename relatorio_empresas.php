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

$condicoes = [];
$params    = [];
$tipos     = "";

// Mesma pesquisa da tela de empresas
if ($busca !== "") {
    $condicoes[] = "(UPPER(NOME_FANTASIA) LIKE UPPER(?)
                  OR UPPER(RAZAO_SOCIAL) LIKE UPPER(?)
                  OR UPPER(CNPJ) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo, $termo);
    $tipos .= "sss";
}

// Expositor só enxerga a própria empresa
if (ehExpositor()) {
    $empresaId = (int)($empresaLogadaId ?? 0);
    if ($empresaId <= 0) {
        http_response_code(403);
        exit("Usuário sem empresa vinculada.");
    }
    $condicoes[] = "ID = ?";
    $params[]    = $empresaId;
    $tipos      .= "i";
}

$sql = "SELECT ID, NOME_FANTASIA, RAZAO_SOCIAL, CNPJ FROM EMPRESAS";
if ($condicoes) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY ID DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$linhas = [];
while ($r = $result->fetch_assoc()) {
    $linhas[] = [$r["ID"], $r["NOME_FANTASIA"], $r["RAZAO_SOCIAL"], $r["CNPJ"]];
}

gerarRelatorioPdf(
    "Empresas",
    ["ID", "Nome Fantasia", "Razão Social", "CNPJ"],
    $linhas,
    $busca !== "" ? 'Pesquisa: "' . $busca . '"' : "",
    "relatorio_empresas.pdf"
);
