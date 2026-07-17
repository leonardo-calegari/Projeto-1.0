<?php

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

$usuario = htmlspecialchars($_SESSION["usuario"]);

// Cada página pode definir $titulo_pagina antes de incluir este arquivo.
if (!isset($titulo_pagina)) {
    $titulo_pagina = "Sistema de Credenciamento";
}

$pagina_atual = basename($_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($titulo_pagina) ?></title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:Arial,sans-serif; background:#f5f5f5; }

header{
    height:70px;
    background:#ececec;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 30px;
    border-bottom:1px solid #dcdcdc;
}

.logo{ font-size:28px; font-weight:bold; }
.usuario{ font-size:15px; }

.layout{ display:flex; min-height:calc(100vh - 70px); }

aside{ width:220px; background:#fff; border-right:1px solid #ddd; }

aside a{
    display:block;
    padding:18px 20px;
    color:#333;
    text-decoration:none;
    font-weight:bold;
}

aside a:hover{ background:#f0f0f0; }
aside a.ativo{ background:#e7f0ff; color:#0d6efd; border-left:3px solid #0d6efd; }

main{ flex:1; padding:40px; }

h1{ margin-bottom:25px; color:#333; }

a{ color:#0d6efd; }

table{ border-collapse:collapse; width:100%; background:#fff; margin-top:10px; }
th, td{ border:1px solid #ddd; padding:10px; text-align:left; }
th{ background:#f0f0f0; }

.col-foto{
    width:70px;
    text-align:center;
}

.foto-miniatura{
    width:50px;
    height:50px;
    object-fit:cover;
    border-radius:50%;
    border:1px solid #ddd;
    display:inline-block;
}

.sem-foto{ color:#999; }

.botoes{ display:flex; gap:15px; }

.botao{
    display:inline-block;
    padding:10px 16px;
    background:#0d6efd;
    color:white;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:15px;
}
.botao:hover{ background:#0056d2; }

.btn-voltar{
    display:inline-block;
    padding:8px 16px;
    background:#6c757d;
    color:white;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:20px;
}
.btn-voltar:hover{ background:#565e64; }
</style>
</head>

<body>

<header>
    <div class="logo">SISTEMA DE CREDENCIAMENTO</div>
    <div class="usuario">Olá, <?= $usuario ?></div>
</header>

<div class="layout">

    <aside>
        <a href="empresa.php" class="<?= $pagina_atual === 'empresa.php' ? 'ativo' : '' ?>">EMPRESAS</a>
        <a href="pessoas.php" class="<?= $pagina_atual === 'pessoas.php' ? 'ativo' : '' ?>">PESSOAS</a>
        <a href="cargos.php" class="<?= $pagina_atual === 'cargos.php' ? 'ativo' : '' ?>">CARGOS</a>
    </aside>

    <main>