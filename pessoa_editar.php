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

$sql  = "SELECT * FROM PESSOAS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Pessoa não encontrada.");
}

$pessoa = $result->fetch_assoc();

$empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS ORDER BY NOME_FANTASIA");
$cargos   = $conn->query("SELECT ID, NOME FROM CARGOS ORDER BY NOME");

$temFoto = !empty($pessoa["FOTO"]);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Editar Pessoa</title>

<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:white; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:15px; }
.voltar{ color:#0d6efd; text-decoration:none; }
form{ margin-top:20px; }
label{ display:block; margin-top:15px; font-weight:bold; }
input, select{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
input[type="file"]{ padding:8px; }
button{ margin-top:20px; padding:10px 20px; background:#0d6efd; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
#preview_foto{
    display:<?= $temFoto ? "block" : "none" ?>;
    margin-top:12px;
    max-width:200px;
    max-height:200px;
    border-radius:6px;
    border:1px solid #ccc;
}
</style>

</head>

<body>

<div class="container">

<h1>Editar Pessoa</h1>

<a class="voltar" href="pessoas.php">← Voltar</a>

<form method="POST" action="pessoa_atualizar.php" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?= $pessoa["ID"] ?>">
    <input type="hidden" name="foto_atual" value="<?= htmlspecialchars($pessoa["FOTO"]) ?>">

    <label for="empresa_id">Empresa</label>
    <select id="empresa_id" name="empresa_id" required>
        <option value="">Selecione...</option>
        <?php while ($emp = $empresas->fetch_assoc()) { ?>
            <option value="<?= $emp["ID"] ?>" <?= $emp["ID"] == $pessoa["EMPRESA_ID"] ? "selected" : "" ?>>
                <?= htmlspecialchars($emp["NOME_FANTASIA"]) ?>
            </option>
        <?php } ?>
    </select>

    <label for="cargo_id">Cargo</label>
    <select id="cargo_id" name="cargo_id">
        <option value="">Nenhum</option>
        <?php while ($cargo = $cargos->fetch_assoc()) { ?>
            <option value="<?= $cargo["ID"] ?>" <?= $cargo["ID"] == $pessoa["CARGO_ID"] ? "selected" : "" ?>>
                <?= htmlspecialchars($cargo["NOME"]) ?>
            </option>
        <?php } ?>
    </select>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($pessoa["NOME"]) ?>" required>

    <label for="cpf">CPF</label>
    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($pessoa["CPF"]) ?>" maxlength="11">

    <label for="documento">Documento</label>
    <input type="text" id="documento" name="documento" value="<?= htmlspecialchars($pessoa["DOCUMENTO"]) ?>" maxlength="30">

    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($pessoa["TELEFONE"]) ?>" maxlength="14">

    <label for="ingresso_permanente">Ingresso Permanente</label>
    <select id="ingresso_permanente" name="ingresso_permanente" required>
        <option value="N" <?= $pessoa["INGRESSO_PERMANENTE"] == "N" ? "selected" : "" ?>>Não</option>
        <option value="S" <?= $pessoa["INGRESSO_PERMANENTE"] == "S" ? "selected" : "" ?>>Sim</option>
    </select>

    <label for="foto">Foto</label>
    <input type="file" id="foto" name="foto" accept="image/*" onchange="mostrarPreview(this)">
    <img id="preview_foto"
         src="<?= $temFoto ? 'uploads/pessoas/' . htmlspecialchars($pessoa["FOTO"]) : '' ?>"
         alt="Pré-visualização da foto">

    <button type="submit">Atualizar</button>

</form>

</div>

<script>
function mostrarPreview(input) {
    const preview = document.getElementById("preview_foto");
    if (input.files && input.files[0]) {
        const leitor = new FileReader();
        leitor.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = "block";
        };
        leitor.readAsDataURL(input.files[0]);
    }
    // Se o usuário cancelar a seleção, mantém a foto atual (não esconde o preview)
}
</script>

</body>
</html>