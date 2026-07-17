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

$sql  = "SELECT * FROM CARGOS WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Cargo não encontrado.");
}

$cargo = $result->fetch_assoc();
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
input{ width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
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

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cargo["NOME"]) ?>" maxlength="255" required autofocus>

    <button type="submit">Atualizar</button>

</form>

</div>

</body>
</html>