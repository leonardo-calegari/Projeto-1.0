<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include("relatorio_pdf.php");

// Mesma regra da tela pessoas_empresa.php:
// admin escolhe a empresa pela URL; os demais ficam na empresa da sessão
$empresa_id = (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) ? (int)$_GET["empresa_id"] : 0;

if (!ehAdmin()) {
    $empresa_id = (int)($empresaLogadaId ?? 0);
    if ($empresa_id <= 0) {
        http_response_code(403);
        exit("Usuário sem empresa vinculada.");
    }
}

$st = $conn->prepare("SELECT NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
$st->bind_param("i", $empresa_id);
$st->execute();
$empresa = $st->get_result()->fetch_assoc();

if (!$empresa) {
    http_response_code(404);
    exit("Empresa não encontrada.");
}

$busca = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";

$sql = "SELECT P.ID, P.NOME, P.CPF, P.TELEFONE, P.INGRESSO_PERMANENTE, C.NOME AS NOME_CARGO
        FROM PESSOAS P
        LEFT JOIN CARGOS C ON C.ID = P.CARGO_ID
        WHERE P.EMPRESA_ID = ? AND P.EXCLUIDO_EM IS NULL";

$params = [$empresa_id];
$tipos  = "i";

// A tela pesquisa por nome, cargo, CPF e telefone
if ($busca !== "") {
    $sql .= " AND (UPPER(P.NOME) LIKE UPPER(?)
                OR UPPER(C.NOME) LIKE UPPER(?)
                OR UPPER(P.CPF) LIKE UPPER(?)
                OR UPPER(P.TELEFONE) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo, $termo, $termo);
    $tipos .= "ssss";
}
$sql .= " ORDER BY P.ID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$linhas = [];
while ($r = $result->fetch_assoc()) {
    $linhas[] = [
        $r["ID"],
        $r["NOME"],
        !empty($r["NOME_CARGO"]) ? $r["NOME_CARGO"] : "—",
        $r["CPF"],
        $r["TELEFONE"],
        $r["INGRESSO_PERMANENTE"] === "S" ? "Sim" : "Não",
    ];
}

$partes = ["Empresa: " . $empresa["NOME_FANTASIA"]];
if ($busca !== "") {
    $partes[] = 'Pesquisa: "' . $busca . '"';
}

gerarRelatorioPdf(
    "Pessoas - " . $empresa["NOME_FANTASIA"],
    ["ID", "Nome", "Cargo", "CPF", "Telefone", "Permanente"],
    $linhas,
    implode(" | ", $partes),
    "relatorio_pessoas_empresa.pdf"
);