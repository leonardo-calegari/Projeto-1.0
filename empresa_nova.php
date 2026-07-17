<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$tipos = $conn->query("SELECT ID, NOME, CONTROLA_ESPACOS FROM TIPOS ORDER BY NOME");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Nova Empresa</title>

<style>
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:white; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:15px; }
.voltar{ color:#0d6efd; text-decoration:none; }
form{ margin-top:20px; }
label{ display:block; margin-top:15px; }
input, select{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
button{ margin-top:20px; padding:10px 20px; background:#0d6efd; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
.btn-voltar{ display:inline-block; padding:8px 16px; background:#6c757d; color:white; text-decoration:none; border-radius:5px; margin-bottom:20px; }
.btn-voltar:hover{ background:#565e64; }
#campo_espacos{ display:none; }
</style>

</head>

<body>

<div class="container">

<h1>Nova Empresa</h1>

<a href="paginainicial.php" class="btn-voltar">← Voltar</a>

<form method="POST" action="empresa_salvar.php">

    <label for="nome_fantasia">Nome Fantasia</label>
    <input type="text" id="nome_fantasia" name="nome_fantasia" required autofocus>

    <label for="razao_social">Razão Social</label>
    <input type="text" id="razao_social" name="razao_social" required>

    <label for="cnpj">CNPJ</label>
    <input type="text" id="cnpj" name="cnpj" maxlength="14" required>

    <label for="tipo_id">Tipo</label>
    <select id="tipo_id" name="tipo_id" required onchange="verificarTipo(this)">
        <option value="">Selecione...</option>
        <?php while ($tipo = $tipos->fetch_assoc()) { ?>
            <option value="<?= $tipo["ID"] ?>" data-controla="<?= $tipo["CONTROLA_ESPACOS"] ?>">
                <?= htmlspecialchars($tipo["NOME"]) ?>
            </option>
        <?php } ?>
    </select>

    <div id="campo_espacos">
        <label for="quantidade_espacos">Quantidade de Espaços</label>
        <input type="number" id="quantidade_espacos" name="quantidade_espacos" min="0">
    </div>

    <button type="submit">Salvar</button>

</form>

</div>

<script>
function verificarTipo(select) {
    var opcao = select.options[select.selectedIndex];
    var controla = opcao.getAttribute("data-controla");
    var campo = document.getElementById("campo_espacos");
    var input = document.getElementById("quantidade_espacos");

    if (controla === "S") {
        campo.style.display = "block";
        input.required = true;
    } else {
        campo.style.display = "none";
        input.required = false;
        input.value = "";
    }
}
</script>

</body>
</html> 