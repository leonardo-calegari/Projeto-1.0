<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_upload.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$id                  = intval($_POST["id"]);
$empresa_id          = intval($_POST["empresa_id"]);
$cargo_id            = !empty($_POST["cargo_id"]) ? intval($_POST["cargo_id"]) : null;
$nome                = trim(strip_tags($_POST["nome"]));
$cpf                 = trim(strip_tags($_POST["cpf"]));
$documento           = trim(strip_tags($_POST["documento"]));
$telefone            = trim(strip_tags($_POST["telefone"]));
$ingresso_permanente = trim(strip_tags($_POST["ingresso_permanente"]));
$foto_atual          = trim($_POST["foto_atual"]);

if ($id <= 0) {
    die("ID inválido.");
}

// Busca a pessoa atual para validar a que empresa ela pertence
$stmtAtual = $conn->prepare("SELECT EMPRESA_ID FROM PESSOAS WHERE ID = ?");
$stmtAtual->bind_param("i", $id);
$stmtAtual->execute();
$pessoaAtual = $stmtAtual->get_result()->fetch_assoc();

if (!$pessoaAtual) {
    die("Pessoa não encontrada.");
}

if (!$is_admin) {
    // Funcionário/Expositor só podem atualizar pessoa da própria empresa,
    // e não podem mudar a empresa da pessoa
    if ($pessoaAtual["EMPRESA_ID"] != intval($_SESSION["empresa_id"])) {
        die("Você não tem permissão para atualizar esta pessoa.");
    }
    $empresa_id = intval($_SESSION["empresa_id"]);
}

if ($empresa_id <= 0 || $nome == "") {
    die("Preencha os campos obrigatórios.");
}

if (!in_array($ingresso_permanente, ["S", "N"])) {
    die("Valor inválido para ingresso permanente.");
}

// Se o usuário escolher uma foto nova, ela substitui a antiga.
// Caso contrário, mantém o nome de arquivo que já estava salvo.
$foto = processarUploadFoto("foto", $foto_atual);

$sql  = "UPDATE PESSOAS
         SET EMPRESA_ID = ?, CARGO_ID = ?, NOME = ?, CPF = ?, DOCUMENTO = ?, TELEFONE = ?, INGRESSO_PERMANENTE = ?, FOTO = ?
         WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissssssi", $empresa_id, $cargo_id, $nome, $cpf, $documento, $telefone, $ingresso_permanente, $foto, $id);

if ($stmt->execute()) {
    header("Location: pessoas_empresa.php?empresa_id=" . $empresa_id);
    exit;
} else {
    echo "Erro ao atualizar: " . $conn->error;
}
?>