<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_upload.php");
include("funcoes_limite.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$empresa_id          = intval($_POST["empresa_id"]);
$cargo_id            = !empty($_POST["cargo_id"]) ? intval($_POST["cargo_id"]) : null;
$nome                = trim(strip_tags($_POST["nome"]));
$cpf                 = trim(strip_tags($_POST["cpf"]));
$documento           = trim(strip_tags($_POST["documento"]));
$telefone            = trim(strip_tags($_POST["telefone"]));
$ingresso_permanente = trim(strip_tags($_POST["ingresso_permanente"]));

// Funcionário/Expositor: a empresa é sempre a da sessão, nunca a enviada pelo formulário
if (!$is_admin) {
    $empresa_id = intval($_SESSION["empresa_id"]);
}

if ($empresa_id <= 0 || $nome == "") {
    die("Preencha os campos obrigatórios.");
}

if (!in_array($ingresso_permanente, ["S", "N"])) {
    die("Valor inválido para ingresso permanente.");
}

// ---- Validação do limite de pessoas ----
$limiteInfo     = obterLimitePessoas($conn, $empresa_id);
$temLimite      = $limiteInfo["limite"] > 0;
$limiteAtingido = $temLimite && $limiteInfo["total"] >= $limiteInfo["limite"];

if ($limiteAtingido) {

    if ($categoria_id == 1) {
        // Administrador: segue em frente, só recebe o aviso (feito na tela anterior)

    } elseif ($categoria_id == 2) {
        // Funcionário: exige autorização de um administrador
        $admin_email = isset($_POST["admin_email"]) ? trim($_POST["admin_email"]) : "";
        $admin_senha = isset($_POST["admin_senha"]) ? trim($_POST["admin_senha"]) : "";

        if ($admin_email == "" || $admin_senha == "") {
            die("Limite de pessoas atingido. Informe o email e a senha de um administrador para liberar o cadastro.");
        }

        if (!validarSenhaAdmin($conn, $admin_email, $admin_senha)) {
            die("Email ou senha de administrador inválidos. Cadastro não liberado.");
        }
        // Autorizado: segue em frente

    } else {
        // Expositor (categoria 3) ou qualquer outra: bloqueado
        die("Limite de pessoas atingido para esta empresa. Não é possível cadastrar novas pessoas.");
    }
}
// ---- Fim da validação do limite ----

$foto = processarUploadFoto("foto");

$sql  = "INSERT INTO PESSOAS (EMPRESA_ID, CARGO_ID, NOME, CPF, DOCUMENTO, TELEFONE, INGRESSO_PERMANENTE, FOTO)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissssss", $empresa_id, $cargo_id, $nome, $cpf, $documento, $telefone, $ingresso_permanente, $foto);

if ($stmt->execute()) {
    header("Location: pessoas_empresa.php?empresa_id=" . $empresa_id);
    exit;
} else {
    echo "Erro ao salvar: " . $conn->error;
}
?>