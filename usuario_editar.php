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

$id = intval($_GET["id"] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

$st = $conn->prepare("SELECT ID AS ID, NOME AS NOME, EMAIL AS EMAIL, CATEGORIA_ID AS CATEGORIA_ID, EMPRESA_ID AS EMPRESA_ID FROM USUARIOS WHERE ID = ?");
$st->bind_param("i", $id);
$st->execute();
$usuario = $st->get_result()->fetch_assoc();

if (!$usuario) {
    die("Usuário não encontrado.");
}

$categorias = $conn->query("SELECT ID, NOME FROM CATEGORIAS ORDER BY ID");
$empresas   = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");
$ehEle      = ($id === $usuarioLogadoId);
$voltar     = !empty($usuario["EMPRESA_ID"]) ? "usuarios.php?empresa_id=" . (int)$usuario["EMPRESA_ID"] : "usuarios.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Usuário</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:#fff; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:20px; }
label{ display:block; margin-top:15px; font-weight:bold; }
input,select{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
button{ margin-top:25px; padding:10px 20px; background:#0d6efd; color:#fff; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
small{ color:#64748b; }
.btn-voltar{ display:inline-block; padding:8px 16px; background:#6c757d; color:#fff; text-decoration:none; border-radius:5px; margin-bottom:20px; }
.btn-voltar:hover{ background:#565e64; }
</style>
</head>
<body>

<div class="container">

<h1>Editar Usuário</h1>

<a href="<?= $voltar ?>" class="btn-voltar">← Voltar</a>

<form method="POST" action="usuario_atualizar.php">

    <input type="hidden" name="id" value="<?= $usuario["ID"] ?>">

    <label for="categoria_id">Categoria</label>
    <select id="categoria_id" name="categoria_id" required onchange="mostrarEmpresa()">
        <?php while ($c = $categorias->fetch_assoc()) { ?>
            <option value="<?= $c["ID"] ?>" <?= $c["ID"] == $usuario["CATEGORIA_ID"] ? "selected" : "" ?>>
                <?= htmlspecialchars(mb_convert_case($c["NOME"], MB_CASE_TITLE, "UTF-8")) ?>
            </option>
        <?php } ?>
    </select>
    <?php if ($ehEle) { ?><small>Você não pode tirar o seu próprio acesso de administrador.</small><?php } ?>

    <div id="campo_empresa" style="display:none;">
        <label for="empresa_id">Empresa</label>
        <select id="empresa_id" name="empresa_id">
            <option value="">Selecione...</option>
            <?php while ($e = $empresas->fetch_assoc()) { ?>
                <option value="<?= $e["ID"] ?>" <?= $e["ID"] == $usuario["EMPRESA_ID"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($e["NOME_FANTASIA"]) ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario["NOME"]) ?>" required>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario["EMAIL"]) ?>" required>

    <label for="senha">Nova senha</label>
    <input type="password" id="senha" name="senha" autocomplete="new-password">
    <small>Deixe em branco para manter a senha atual.</small>

    <button type="submit">Atualizar</button>

</form>

</div>

<script>
function mostrarEmpresa() {
    var cat = document.getElementById("categoria_id");
    var campo = document.getElementById("campo_empresa");
    var empresa = document.getElementById("empresa_id");

    if (cat.value === "3") {
        campo.style.display = "block";
        empresa.required = true;
    } else {
        campo.style.display = "none";
        empresa.required = false;
        empresa.value = "";
    }
}
mostrarEmpresa();
</script>

</body>
</html>
