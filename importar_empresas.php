<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include_once("funcoes_log.php");

// Somente administrador (categoria 1)
if (!ehAdmin()) {
    http_response_code(403);
    exit("Acesso negado.");
}

const ANO_FIXO = 2026;

function importarEmpresas(mysqli $conn, string $caminho, int $usuarioId): array
{
    // 1) Tipos em um array: ID => nome em minúsculo
    $nomesTipos = [];
    $rs = $conn->query("SELECT ID, NOME FROM TIPOS");
    while ($t = $rs->fetch_assoc()) {
        $nomesTipos[(int)$t["ID"]] = mb_strtolower(trim($t["NOME"]), "UTF-8");
    }

    // 2) Lê o arquivo e garante UTF-8
    $conteudo = file_get_contents($caminho);
    $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo); // remove BOM
    if (!mb_check_encoding($conteudo, "UTF-8")) {
        $conteudo = mb_convert_encoding($conteudo, "UTF-8", "ISO-8859-1");
    }
    $linhas = preg_split('/\r\n|\r|\n/', $conteudo);

    // 3) Cabeçalho -> posição de cada coluna
    $cabecalho = array_map(fn($c) => strtoupper(trim($c)), explode(";", array_shift($linhas)));
    $pos = array_flip($cabecalho);
    foreach (["NOME_FANTASIA", "RAZAO_SOCIAL", "TIPO_CADASTRO"] as $col) {
        if (!isset($pos[$col])) {
            return ["erro" => "Cabeçalho inválido: coluna $col não encontrada."];
        }
    }

    $inseridas = 0;
    $erros = [];

    $stmt = $conn->prepare(
        "INSERT INTO EMPRESAS (ANO, NOME_FANTASIA, RAZAO_SOCIAL, TIPO_ID, QUANTIDADE_ESPACOS)
         VALUES (?, ?, ?, ?, ?)"
    );

    $conn->begin_transaction();
    try {
        foreach ($linhas as $i => $linha) {
            $numLinha = $i + 2; // +1 do cabeçalho, +1 porque começa em 1
            if (trim($linha) === "") continue;

            $c = explode(";", $linha);
            $fantasia  = trim(strip_tags($c[$pos["NOME_FANTASIA"]] ?? ""));
            $razao     = trim(strip_tags($c[$pos["RAZAO_SOCIAL"]] ?? ""));
            $tipoTexto = trim($c[$pos["TIPO_CADASTRO"]] ?? "");

            // NOME_FANTASIA e TIPO_ID são NOT NULL no banco
            if ($fantasia === "") {
                $erros[] = "Linha $numLinha: nome fantasia vazio.";
                continue;
            }

            // Procura o ID do tipo pelo nome
            $tipoId = array_search(mb_strtolower($tipoTexto, "UTF-8"), $nomesTipos, true);
            if ($tipoId === false) {
                $erros[] = "Linha $numLinha ($fantasia): tipo '$tipoTexto' não encontrado.";
                continue;
            }

            $espacos = 0; // os tipos desta importação não controlam espaços
            $ano = ANO_FIXO;
            $stmt->bind_param("issii", $ano, $fantasia, $razao, $tipoId, $espacos);
            $stmt->execute();
            $inseridas++;

            if (function_exists("registrarLog")) {
                registrarLog("INCLUSAO", "EMPRESAS", dadosLog([
                    "ID"            => $conn->insert_id,
                    "NOME_FANTASIA" => $fantasia,
                    "RAZAO_SOCIAL"  => $razao,
                    "TIPO_ID"       => $tipoId,
                    "ORIGEM"        => "importacao",
                ]));
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ["erro" => "Falha na importação, nada foi gravado: " . $e->getMessage()];
    }

    return ["inseridas" => $inseridas, "erros" => $erros];
}

$resultado = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_FILES["arquivo"]) || $_FILES["arquivo"]["error"] !== UPLOAD_ERR_OK) {
        $resultado = ["erro" => "Selecione um arquivo válido."];
    } else {
        $resultado = importarEmpresas($conn, $_FILES["arquivo"]["tmp_name"], $usuarioLogadoId);
    }
}

$titulo_pagina = "Importar Empresas";
include("cabecalho.php");
?>

<h1>Importar Empresas</h1>

<p style="margin:12px 0;color:#475569;">
    Arquivo .txt ou .csv separado por <code>;</code>, com a primeira linha:
    <code>NOME_FANTASIA;RAZAO_SOCIAL;TIPO_CADASTRO</code>
</p>

<?php if ($resultado) { ?>
    <?php if (isset($resultado["erro"])) { ?>
        <p style="color:#b91c1c;font-weight:600;"><?= htmlspecialchars($resultado["erro"]) ?></p>
    <?php } else { ?>
        <p style="color:#15803d;font-weight:600;"><?= $resultado["inseridas"] ?> empresa(s) importada(s).</p>
        <?php if ($resultado["erros"]) { ?>
            <p style="color:#b45309;font-weight:600;"><?= count($resultado["erros"]) ?> linha(s) ignorada(s):</p>
            <ul>
                <?php foreach ($resultado["erros"] as $e) { ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php } ?>
            </ul>
        <?php } ?>
    <?php } ?>
<?php } ?>

<form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
    <a href="empresa.php" class="btn-voltar">← Voltar</a>
    <input type="file" name="arquivo" accept=".txt,.csv" required>
    <button type="submit" class="botao">Importar</button>
</form>

<?php include("rodape.php"); ?>