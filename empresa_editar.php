<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$id = intval($_GET["id"]);

if ($id <= 0) {
    die("ID inválido.");
}

$sql  = "SELECT * FROM EMPRESAS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Empresa não encontrada.");
}

$empresa = $result->fetch_assoc();

$tipos = $conn->query("SELECT ID, NOME, CONTROLA_ESPACOS FROM TIPOS ORDER BY NOME");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Editar Empresa</title>

<style>
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:white; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:15px; }
.voltar{ color:#0d6efd; text-decoration:none; }
form{ margin-top:20px; }
label{ display:block; margin-top:15px; }
input, select{ width:100%; padding:10px; margin-top:5px; }
button{ margin-top:20px; padding:10px 20px; background:#0d6efd; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
</style>

</head>

<body>

<div class="container">

<h1>Editar Empresa</h1>

<a class="voltar" href="empresa.php">← Voltar</a>

<form method="POST" action="empresa_atualizar.php">

    <input type="hidden" name="id" value="<?= $empresa["ID"] ?>">

    <label for="nome_fantasia">Nome Fantasia</label>
    <input type="text" id="nome_fantasia" name="nome_fantasia" value="<?= htmlspecialchars($empresa["NOME_FANTASIA"]) ?>" required>

    <label for="razao_social">Razão Social</label>
    <input type="text" id="razao_social" name="razao_social" value="<?= htmlspecialchars($empresa["RAZAO_SOCIAL"]) ?>" required>

    <label for="cnpj">CNPJ</label>
    <input type="text" id="cnpj" name="cnpj" value="<?= htmlspecialchars($empresa["CNPJ"]) ?>" required>

    <label for="tipo_id">Tipo</label>
    <select id="tipo_id" name="tipo_id" required onchange="verificarTipo(this)">
        <option value="">Selecione...</option>
        <?php while ($tipo = $tipos->fetch_assoc()) { ?>
            <option value="<?= $tipo["ID"] ?>"
                data-controla="<?= $tipo["CONTROLA_ESPACOS"] ?>"
                <?= $tipo["ID"] == $empresa["TIPO_ID"] ? "selected" : "" ?>>
                <?= htmlspecialchars($tipo["NOME"]) ?>
            </option>
        <?php } ?>
    </select>

    <div id="campo_espacos" style="display:none;">
        <label for="quantidade_espacos">Quantidade de Espaços</label>
        <input type="number" id="quantidade_espacos" name="quantidade_espacos" min="0" value="<?= htmlspecialchars($empresa["QUANTIDADE_ESPACOS"]) ?>">
    </div>

    <button type="submit">Atualizar</button>

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

// ao carregar a página, verificar o tipo já selecionado
window.onload = function() {
    var select = document.getElementById("tipo_id");
    if (select.value !== "") {
        verificarTipo(select);
    }
};
</script>

</body>
</html>