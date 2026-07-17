<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
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

<h1>Novo Cargo</h1>

<a href="cargos.php" class="btn-voltar">← Voltar</a>

<form method="POST" action="cargo_salvar.php">

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" maxlength="255" required autofocus>

    <button type="submit">Salvar</button>

</form>

</div>

</body>
</html>