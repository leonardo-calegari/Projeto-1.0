<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

if ($is_admin) {
    $empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");
} else {
    // Empresa fixa, vinda da sessão
    $stmt = $conn->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
    $stmt->bind_param("i", $_SESSION["empresa_id"]);
    $stmt->execute();
    $empresa_sessao = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Novo Cargo</title>

<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
.container{ background:white; max-width:600px; padding:30px; border-radius:8px; }
h1{ margin-bottom:15px; }
form{ margin-top:20px; }
label{ display:block; margin-top:15px; font-weight:bold; }
input, select{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
input[disabled]{ background:#f1f5f9; color:#555; }
button{ margin-top:20px; padding:10px 20px; background:#0d6efd; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover{ background:#0056d2; }
.btn-voltar {
    display: inline-block;
    padding: 8px 16px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 20px;
}
.btn-voltar:hover {
    background: #565e64;
}
</style>

</head>

<body>

<div class="container">

<h1>Novo Cargo</h1>

<a href="cargos.php" class="btn-voltar">← Voltar</a>

<form method="POST" action="cargo_salvar.php">

    <?php if ($is_admin) { ?>
        <label for="empresa_id">Empresa</label>
        <select id="empresa_id" name="empresa_id" required>
            <option value="">Selecione...</option>
            <?php while ($emp = $empresas->fetch_assoc()) { ?>
                <option value="<?= $emp["ID"] ?>"><?= htmlspecialchars($emp["NOME_FANTASIA"]) ?></option>
            <?php } ?>
        </select>
    <?php } else { ?>
        <label>Empresa</label>
        <input type="text" value="<?= htmlspecialchars($empresa_sessao["NOME_FANTASIA"]) ?>" disabled>
    <?php } ?>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" maxlength="255" required autofocus>

    <button type="submit">Salvar</button>

</form>

</div>

</body>
</html>