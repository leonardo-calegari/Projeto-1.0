<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$id = intval($_GET["id"]);

if ($id <= 0) {
    die("ID inválido.");
}

$sql  = "SELECT * FROM CARGOS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Cargo não encontrado.");
}

$cargo = $result->fetch_assoc();

// Funcionário/Expositor só pode editar cargo da própria empresa
if (!$is_admin && $cargo["ID_EMPRESA"] != intval($_SESSION["empresa_id"])) {
    die("Você não tem permissão para editar este cargo.");
}

if ($is_admin) {
    $empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");
} else {
    $stmtEmp = $conn->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
    $stmtEmp->bind_param("i", $cargo["ID_EMPRESA"]);
    $stmtEmp->execute();
    $empresa_sessao = $stmtEmp->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Editar Cargo</title>

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

<h1>Editar Cargo</h1>

<a href="cargos.php" class="btn-voltar">← Voltar</a>

<form method="POST" action="cargo_atualizar.php">

    <input type="hidden" name="id" value="<?= $cargo["ID"] ?>">

    <?php if ($is_admin) { ?>
        <label for="empresa_id">Empresa</label>
        <select id="empresa_id" name="empresa_id" required>
            <?php while ($emp = $empresas->fetch_assoc()) { ?>
                <option value="<?= $emp["ID"] ?>" <?= $emp["ID"] == $cargo["ID_EMPRESA"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($emp["NOME_FANTASIA"]) ?>
                </option>
            <?php } ?>
        </select>
    <?php } else { ?>
        <label>Empresa</label>
        <input type="text" value="<?= htmlspecialchars($empresa_sessao["NOME_FANTASIA"]) ?>" disabled>
    <?php } ?>

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cargo["NOME"]) ?>" maxlength="255" required autofocus>

    <button type="submit">Atualizar</button>

</form>

</div>

</body>
</html>