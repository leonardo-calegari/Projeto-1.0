<?php
// Arquivo TEMPORÁRIO de diagnóstico. Apague depois de usar.
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();

echo "<pre style='font:14px/1.5 monospace'>";

echo "PASTA QUE O APACHE ESTA USANDO:\n  " . __DIR__ . "\n\n";

echo "ARQUIVOS NESTA PASTA:\n";
foreach (["login.php", "empresa.php", "permissoes.php", "importar_empresas.php", "cabecalho.php", "rodape.php", "funcoes_log.php", "conexao.php"] as $arq) {
    $caminho = __DIR__ . DIRECTORY_SEPARATOR . $arq;
    if (file_exists($caminho)) {
        echo "  OK      $arq   (modificado em " . date("d/m/Y H:i:s", filemtime($caminho)) . ")\n";
    } else {
        echo "  FALTA   $arq\n";
    }
}

echo "\nO empresa.php DESTA PASTA TEM O CODIGO NOVO?\n";
$emp = __DIR__ . DIRECTORY_SEPARATOR . "empresa.php";
if (file_exists($emp)) {
    $txt = file_get_contents($emp);
    echo "  contem permissoes.php: " . (strpos($txt, "permissoes.php") !== false ? "SIM" : "NAO (ainda e o arquivo antigo)") . "\n";
    echo "  contem botao Importar: " . (strpos($txt, "importar_empresas.php") !== false ? "SIM" : "NAO (ainda e o arquivo antigo)") . "\n";
}

echo "\nSESSAO:\n";
var_dump($_SESSION);

if (file_exists(__DIR__ . "/conexao.php") && file_exists(__DIR__ . "/permissoes.php") && isset($_SESSION["usuario"])) {
    include __DIR__ . "/conexao.php";
    include __DIR__ . "/permissoes.php";
    echo "\nRESULTADO DO permissoes.php:\n";
    echo "  categoria encontrada: " . $categoriaLogado . "  (precisa ser 1 para ver o botao)\n";
    echo "  ehAdmin(): " . (ehAdmin() ? "SIM" : "NAO") . "\n";
}

echo "</pre>";