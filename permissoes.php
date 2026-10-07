<?php
// Carrega as permissões do usuário logado.
// Use depois de session_start() e include("conexao.php").
//
// Define:
//   $categoriaLogado  -> 1 = admin, 2 = funcionário, 3 = expositor (0 = desconhecida)
//   $empresaLogadaId  -> empresa do expositor (null para admin/funcionário)
//   $usuarioLogadoId  -> id do usuário

$usuarioLogadoId = (int)($_SESSION["usuario_id"] ?? 0);
$categoriaLogado = (int)($_SESSION["categoria_id"] ?? 0);

// Sessão antiga (login sem categoria): busca no banco pelo id do usuário
if ($categoriaLogado === 0 && $usuarioLogadoId > 0) {
    $st = $conn->prepare("SELECT CATEGORIA_ID, EMPRESA_ID FROM USUARIOS WHERE ID = ?");
    $st->bind_param("i", $usuarioLogadoId);
    $st->execute();
    $linha = $st->get_result()->fetch_assoc();

    if ($linha) {
        $linha = array_change_key_case($linha, CASE_LOWER);
        $_SESSION["categoria_id"] = (int)$linha["categoria_id"];
        $_SESSION["empresa_id"]   = $linha["empresa_id"];
        $categoriaLogado = (int)$linha["categoria_id"];
    }
}

$empresaLogadaId = $_SESSION["empresa_id"] ?? null;

function ehAdmin(): bool       { global $categoriaLogado; return $categoriaLogado === 1; }
function ehFuncionario(): bool { global $categoriaLogado; return $categoriaLogado === 2; }
function ehExpositor(): bool   { global $categoriaLogado; return $categoriaLogado === 3; }