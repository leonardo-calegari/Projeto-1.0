<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");

if (!ehAdmin()) {
    http_response_code(403);
    die("Acesso restrito ao administrador.");
}

// Empresa vinda do botão Usuários da lista de empresas (opcional)
$empresa_id  = (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) ? (int)$_GET["empresa_id"] : 0;
$empresaFixa = null;

if ($empresa_id > 0) {
    $st = $conn->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
    $st->bind_param("i", $empresa_id);
    $st->execute();
    $empresaFixa = $st->get_result()->fetch_assoc();
    if (!$empresaFixa) {
        header("Location: usuarios.php");
        exit;
    }
}

$categorias = $conn->query("SELECT ID, NOME FROM CATEGORIAS ORDER BY ID");
$empresas   = $empresaFixa ? null : $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");
$voltar     = $empresaFixa ? "usuarios.php?empresa_id=" . $empresa_id : "usuarios.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Novo Usuário</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:#fff; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:20px; }
label{ display:block; margin-top:15px; font-weight:bold; }
input,select{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
input[disabled]{ background:#f1f5f9; color:#555; }
button{ margin-top:25px; padding:10px 20px; background:#0d6efd; color:#fff; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
.btn-voltar{ display:inline-block; padding:8px 16px; background:#6c757d; color:#fff; text-decoration:none; border-radius:5px; margin-bottom:20px; }
.btn-voltar:hover{ background:#565e64; }
</style>
</head>
<body>

<div class="container">

<h1>Novo Usuário</h1>

<a href="<?= $voltar ?>" class="btn-voltar">← Voltar</a>

<form method="POST" action="usuario_salvar.php">

    <input type="hidden" name="voltar_empresa" value="<?= $empresa_id ?>">

    <?php if ($empresaFixa) { ?>

        <!-- Vindo de uma empresa: cadastra como Expositor (categoria 3) ligado a ela -->
        <input type="hidden" name="categoria_id" value="3">
        <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

        <label>Empresa</label>
        <input type="text" value="<?= htmlspecialchars($empresaFixa["NOME_FANTASIA"]) ?>" disabled>

        <label>Categoria</label>
        <input type="text" value="Expositor" disabled>

    <?php } else { ?>

        <label for="categoria_id">Categoria</label>
        <select id="categoria_id" name="categoria_id" required onchange="mostrarEmpresa()">
            <option value="">Selecione...</option>
            <?php while ($c = $categorias->fetch_assoc()) { ?>
                <option value="<?= $c["ID"] ?>"><?= htmlspecialchars(mb_convert_case($c["NOME"], MB_CASE_TITLE, "UTF-8")) ?></option>
            <?php } ?>
        </select>

        <div id="campo_empresa" style="display:none;">
            <label for="empresa_id">Empresa</label>
            <select id="empresa_id" name="empresa_id">
                <option value="">Selecione...</option>
                <?php while ($e = $empresas->fetch_assoc()) { ?>
                    <option value="<?= $e["ID"] ?>"><?= htmlspecialchars($e["NOME_FANTASIA"]) ?></option>
                <?php } ?>
            </select>
        </div>

    <?php } ?>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" required autofocus>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required>

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required>

    <button type="submit">Salvar</button>

</form>

</div>

<script>
// A empresa só é necessária para a categoria 3 (Expositor)
function mostrarEmpresa() {
    var cat = document.getElementById("categoria_id");
    var campo = document.getElementById("campo_empresa");
    var empresa = document.getElementById("empresa_id");
    if (!cat || !campo) return;

    if (cat.value === "3") {
        campo.style.display = "block";
        empresa.required = true;
    } else {
        campo.style.display = "none";
        empresa.required = false;
        empresa.value = "";
    }
}
</script>

</body>
</html>
