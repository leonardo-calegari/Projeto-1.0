<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$usuario = htmlspecialchars($_SESSION["usuario"]);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Sistema de Credenciamento</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    background:#f5f5f5;
}

header{
    height:70px;
    background:#ececec;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 30px;
    border-bottom:1px solid #dcdcdc;
}

.logo{
    font-size:28px;
    font-weight:bold;
}

.usuario{
    font-size:15px;
}

.layout{
    display:flex;
    height:calc(100vh - 70px);
}

aside{
    width:220px;
    background:#fff;
    border-right:1px solid #ddd;
}

aside a{
    display:block;
    padding:18px 20px;
    color:#333;
    text-decoration:none;
    font-weight:bold;
}

aside a:hover{
    background:#f0f0f0;
}

main{
    flex:1;
    padding:40px;
}

h1{
    margin-bottom:25px;
    color:#333;
}

.botoes{
    display:flex;
    gap:15px;
}

.botao{
    display:inline-block;
    padding:12px 18px;
    background:#0d6efd;
    color:white;
    text-decoration:none;
    border-radius:5px;
}

.botao:hover{
    background:#0056d2;
}

</style>

</head>

<body>

<header>
    <div class="logo">
        SISTEMA DE CREDENCIAMENTO
    </div>

    <div class="usuario">
        Olá, <?= $usuario ?>
    </div>
</header>

<div class="layout">

    <aside>
        <a href="empresa.php">EMPRESAS</a>
        <a href="pessoas.php">PESSOAS</a>
        <a href="cargos.php">cargos</a>
    </aside>

    <main>

        <h1>Bem-vindo</h1>

        <div class="botoes">
            <a href="empresa_nova.php" class="botao">
                Nova Empresa
            </a>

            <a href="pessoa_nova.php" class="botao">
                Pessoa nova
            </a>

            <a href="cargo_novo.php" class="botao">
                cargo novo
            </a>
        </div>

    </main>

</div>

</body>

</html>