<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include_once("funcoes_log.php");
include_once("funcoes_limite.php");

// Empresa que vai receber as pessoas
$empresa_id = (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) ? (int)$_GET["empresa_id"] : 0;

// Expositor (e funcionário vinculado a uma empresa) só importam na própria empresa
$empresaSessao = (int)($empresaLogadaId ?? 0);
if (ehExpositor() || (ehFuncionario() && $empresaSessao > 0)) {
    $empresa_id = $empresaSessao;
}

$stmt = $conn->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) {
    header("Location: empresa.php");
    exit;
}

function importarPessoas(mysqli $conn, string $caminho, int $empresaId, bool $admin, int $usuarioId): array
{
    // Cargos desta empresa: ID => nome em minúsculo
    $cargos = [];
    $st = $conn->prepare("SELECT ID, NOME FROM CARGOS WHERE ID_EMPRESA = ?");
    $st->bind_param("i", $empresaId);
    $st->execute();
    $rs = $st->get_result();
    while ($c = $rs->fetch_assoc()) {
        $cargos[(int)$c["ID"]] = mb_strtolower(trim($c["NOME"]), "UTF-8");
    }

    // Pessoas já cadastradas (para não duplicar ao importar duas vezes o mesmo arquivo)
    $cpfsExistentes   = [];
    $nomesExistentes  = [];
    $st = $conn->prepare("SELECT NOME, CPF FROM PESSOAS WHERE EMPRESA_ID = ? AND EXCLUIDO_EM IS NULL");
    $st->bind_param("i", $empresaId);
    $st->execute();
    $rs = $st->get_result();
    while ($p = $rs->fetch_assoc()) {
        if (!empty($p["CPF"])) $cpfsExistentes[$p["CPF"]] = true;
        $nomesExistentes[mb_strtolower(trim($p["NOME"]), "UTF-8")] = true;
    }

    // Limite de pessoas: admin só recebe aviso; os demais param ao atingir o limite
    $limiteInfo = function_exists("obterLimitePessoas") ? obterLimitePessoas($conn, $empresaId) : ["total" => 0, "limite" => 0];
    $temLimite  = $limiteInfo["limite"] > 0;
    $restante   = ($temLimite && !$admin) ? max(0, $limiteInfo["limite"] - $limiteInfo["total"]) : null;

    // Lê o arquivo e garante UTF-8
    $conteudo = file_get_contents($caminho);
    $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
    if (!mb_check_encoding($conteudo, "UTF-8")) {
        $conteudo = mb_convert_encoding($conteudo, "UTF-8", "ISO-8859-1");
    }
    $linhas = preg_split('/\r\n|\r|\n/', $conteudo);

    // Cabeçalho -> posição das colunas (a ordem das colunas não importa)
    $cabecalho = array_map(fn($c) => strtoupper(trim($c)), explode(";", array_shift($linhas)));
    $pos = array_flip($cabecalho);
    if (!isset($pos["NOME"])) {
        return ["erro" => "Cabeçalho inválido: a coluna NOME não foi encontrada."];
    }

    $inseridas = 0;
    $erros  = [];
    $avisos = [];

    $stmt = $conn->prepare(
        "INSERT INTO PESSOAS (EMPRESA_ID, NOME, INGRESSO_PERMANENTE, CPF, DOCUMENTO, TELEFONE, CARGO_ID)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $conn->begin_transaction();
    try {
        foreach ($linhas as $i => $linha) {
            $numLinha = $i + 2;
            if (trim($linha) === "") continue;

            $c = explode(";", $linha);
            $campo = fn(string $n) => isset($pos[$n]) ? trim(strip_tags($c[$pos[$n]] ?? "")) : "";

            $nome = $campo("NOME");
            if ($nome === "") {
                $erros[] = "Linha $numLinha: nome vazio.";
                continue;
            }

            // CPF: só números, 11 dígitos
            $cpf = preg_replace('/\D/', '', $campo("CPF"));
            if ($cpf !== "" && strlen($cpf) !== 11) {
                $erros[] = "Linha $numLinha ($nome): CPF inválido.";
                continue;
            }
            $cpf = $cpf === "" ? null : $cpf;

            // Telefone: só números, até 14 dígitos
            $telefone = preg_replace('/\D/', '', $campo("TELEFONE"));
            if (strlen($telefone) > 14) {
                $erros[] = "Linha $numLinha ($nome): telefone com mais de 14 dígitos.";
                continue;
            }
            $telefone = $telefone === "" ? null : $telefone;

            $documento = mb_substr($campo("DOCUMENTO"), 0, 30, "UTF-8");
            $documento = $documento === "" ? null : $documento;

            // INGRESSO_PERMANENTE só aceita S ou N
            $ing = mb_strtoupper($campo("INGRESSO_PERMANENTE"), "UTF-8");
            $ingresso = in_array($ing, ["S", "SIM", "1", "X"], true) ? "S" : "N";

            // Cargo: procura pelo nome entre os cargos da empresa (opcional)
            $cargoId = null;
            $cargoTexto = $campo("CARGO");
            if ($cargoTexto !== "") {
                $achado = array_search(mb_strtolower($cargoTexto, "UTF-8"), $cargos, true);
                if ($achado === false) {
                    $avisos[] = "Linha $numLinha ($nome): cargo '$cargoTexto' não existe nesta empresa; pessoa cadastrada sem cargo.";
                } else {
                    $cargoId = $achado;
                }
            }

            // Duplicada?
            $nomeChave = mb_strtolower($nome, "UTF-8");
            if (($cpf !== null && isset($cpfsExistentes[$cpf])) || ($cpf === null && isset($nomesExistentes[$nomeChave]))) {
                $erros[] = "Linha $numLinha ($nome): já cadastrada nesta empresa.";
                continue;
            }

            // Limite de pessoas (não vale para admin)
            if ($restante !== null && $inseridas >= $restante) {
                $erros[] = "Linha $numLinha ($nome): limite de pessoas da empresa atingido.";
                continue;
            }

            $stmt->bind_param("isssssi", $empresaId, $nome, $ingresso, $cpf, $documento, $telefone, $cargoId);
            $stmt->execute();
            $inseridas++;

            if ($cpf !== null) $cpfsExistentes[$cpf] = true;
            $nomesExistentes[$nomeChave] = true;

            if (function_exists("registrarLog")) {
                registrarLog("INCLUSAO", "PESSOAS", dadosLog([
                    "ID"                  => $conn->insert_id,
                    "EMPRESA_ID"          => $empresaId,
                    "NOME"                => $nome,
                    "CPF"                 => $cpf,
                    "INGRESSO_PERMANENTE" => $ingresso,
                    "ORIGEM"              => "importacao",
                ]));
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ["erro" => "Falha na importação, nada foi gravado: " . $e->getMessage()];
    }

    // Admin: só avisa quando passou do limite
    if ($admin && $temLimite && ($limiteInfo["total"] + $inseridas) > $limiteInfo["limite"]) {
        $avisos[] = "A empresa passou do limite de pessoas (" . ($limiteInfo["total"] + $inseridas) . " de " . $limiteInfo["limite"] . ").";
    }

    return ["inseridas" => $inseridas, "erros" => $erros, "avisos" => $avisos];
}

$resultado = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_FILES["arquivo"]) || $_FILES["arquivo"]["error"] !== UPLOAD_ERR_OK) {
        $resultado = ["erro" => "Selecione um arquivo válido."];
    } else {
        $resultado = importarPessoas($conn, $_FILES["arquivo"]["tmp_name"], $empresa_id, ehAdmin(), $usuarioLogadoId);
    }
}

$titulo_pagina = "Importar Pessoas";
include("cabecalho.php");
?>

<h1>Importar Pessoas</h1>

<p style="margin:12px 0;font-weight:600;">Empresa: <?= htmlspecialchars($empresa["NOME_FANTASIA"]) ?></p>

<p style="margin:12px 0;color:#475569;">
    Arquivo .txt ou .csv separado por <code>;</code>. A primeira linha é o cabeçalho, e a ordem das colunas não importa:
    <code>NOME;CPF;DOCUMENTO;TELEFONE;INGRESSO_PERMANENTE;CARGO</code><br>
    Só <strong>NOME</strong> é obrigatório. INGRESSO_PERMANENTE aceita S ou N (SIM também vale).
    O cargo precisa existir na empresa, senão a pessoa entra sem cargo.
</p>

<?php if ($resultado) { ?>
    <?php if (isset($resultado["erro"])) { ?>
        <p style="color:#b91c1c;font-weight:600;"><?= htmlspecialchars($resultado["erro"]) ?></p>
    <?php } else { ?>
        <p style="color:#15803d;font-weight:600;"><?= $resultado["inseridas"] ?> pessoa(s) importada(s).</p>

        <?php if ($resultado["avisos"]) { ?>
            <p style="color:#b45309;font-weight:600;margin-top:10px;">Avisos:</p>
            <ul>
                <?php foreach ($resultado["avisos"] as $a) { ?>
                    <li><?= htmlspecialchars($a) ?></li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if ($resultado["erros"]) { ?>
            <p style="color:#b91c1c;font-weight:600;margin-top:10px;"><?= count($resultado["erros"]) ?> linha(s) ignorada(s):</p>
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