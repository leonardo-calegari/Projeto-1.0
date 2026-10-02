<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

// Só administrador (ajuste o valor conforme sua tabela usuarios)
if (($_SESSION["perfil"] ?? "") !== "admin") {
    die("Acesso restrito a administradores.");
}

$base = __DIR__ . DIRECTORY_SEPARATOR . "logs";
$mes  = $_GET["mes"] ?? "";
$dia  = $_GET["dia"] ?? "";

// Valida o formato para evitar acesso a outras pastas
if ($mes !== "" && !preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = ""; }
if ($dia !== "" && !preg_match('/^\d{2}$/', $dia))       { $dia = ""; }

$titulo_pagina = "Logs";
include("cabecalho.php");
?>

<h1>Logs do sistema</h1>

<?php if ($mes === "") { ?>

    <?php
    $meses = glob($base . DIRECTORY_SEPARATOR . "????-??", GLOB_ONLYDIR) ?: [];
    rsort($meses);
    ?>
    <table>
        <tr><th>Mês</th></tr>
        <?php foreach ($meses as $m) { $nome = basename($m); ?>
            <tr><td><a href="logs.php?mes=<?= $nome ?>"><?= $nome ?></a></td></tr>
        <?php } ?>
        <?php if (!$meses) { ?><tr><td>Nenhum log encontrado</td></tr><?php } ?>
    </table>

<?php } elseif ($dia === "") { ?>

    <?php
    $arquivos = glob($base . DIRECTORY_SEPARATOR . $mes . DIRECTORY_SEPARATOR . "??.log") ?: [];
    rsort($arquivos);
    ?>
    <a href="logs.php" class="btn-voltar">← Voltar</a>
    <table>
        <tr><th>Dia (<?= $mes ?>)</th><th>Última modificação</th></tr>
        <?php foreach ($arquivos as $a) { $d = basename($a, ".log"); ?>
            <tr>
                <td><a href="logs.php?mes=<?= $mes ?>&dia=<?= $d ?>"><?= $d ?></a></td>
                <td><?= date("d/m/Y H:i", filemtime($a)) ?></td>
            </tr>
        <?php } ?>
        <?php if (!$arquivos) { ?><tr><td colspan="2">Nenhum arquivo neste mês</td></tr><?php } ?>
    </table>

<?php } else { ?>

    <?php
    $arquivo = $base . DIRECTORY_SEPARATOR . $mes . DIRECTORY_SEPARATOR . $dia . ".log";
    $linhas  = is_file($arquivo) ? file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    ?>
    <a href="logs.php?mes=<?= $mes ?>" class="btn-voltar">← Voltar</a>
    <p><strong><?= $dia ?>/<?= $mes ?></strong></p>
    <table>
        <tr>
            <th>Usuário (ID)</th><th>Operação</th><th>Tela</th>
            <th>Data</th><th>Hora</th><th>Dados</th>
        </tr>
        <?php foreach ($linhas as $linha) { ?>
            <?php $c = explode(";", $linha, 6); ?>
            <tr>
                <?php for ($i = 0; $i < 6; $i++) { ?>
                    <td><?= htmlspecialchars($c[$i] ?? "") ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        <?php if (!$linhas) { ?><tr><td colspan="6">Arquivo vazio ou não encontrado</td></tr><?php } ?>
    </table>

<?php } ?>

<?php include("rodape.php"); ?>