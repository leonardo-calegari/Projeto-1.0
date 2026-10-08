<?php

function writeRegistroLog($linha)
{
    try {
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . "logs";
        $mesDir  = $baseDir . DIRECTORY_SEPARATOR . date("Y-m");

        if (!is_dir($mesDir)) {
            @mkdir($mesDir, 0755, true);
        }

        // Impede o download dos logs pelo navegador (Apache)
        $htaccess = $baseDir . DIRECTORY_SEPARATOR . ".htaccess";
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }

        $arquivo = $mesDir . DIRECTORY_SEPARATOR . date("d") . ".log";
        file_put_contents($arquivo, $linha . "\n", FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Erro ao gravar log de registro: " . $e->getMessage());
    }
}


function dadosLog($registro)
{
    $partes = [];
    foreach ($registro as $campo => $valor) {
        $valor = str_replace([";", ",", "\r", "\n"], " ", (string) $valor);
        $partes[] = strtoupper($campo) . "=" . $valor;
    }
    return implode(",", $partes);
}


function buscarRegistro($conn, $tabela, $id)
{
    $stmt = $conn->prepare("SELECT * FROM $tabela WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: [];
}


function registrarLog($operacao, $tela, $dados = "")
{
    $usuarioId = $_SESSION["usuario_id"] ?? 0;

    $linha = $usuarioId . ";" . $operacao . ";" . $tela . ";"
           . date("Y-m-d") . ";" . date("H:i:s") . ";" . $dados;

    writeRegistroLog($linha);
}