<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php"); // define ehAdmin() e as outras funções de categoria

// Só administrador (categoria 1)
if (!ehAdmin()) {
    http_response_code(403);
    die("Acesso restrito ao administrador.");
}

$result = $conn->query("SELECT * FROM TIPOS ORDER BY ID DESC");

$titulo_pagina = "Tipos";
include("cabecalho.php");
?>

<style>
.toolbar-lista {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 16px 0 24px;
}
.toolbar-lista .botao {
    background: #2563eb;
    color: #fff;
    padding: 11px 22px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    border: none;
}
.toolbar-lista .botao:hover { background: #1d4ed8; }
.toolbar-lista .grupo-esquerda {
    display: flex;
    align-items: center;
    gap: 14px;
}
.contagem-registros {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    margin: 8px 0;
}
</style>

<h1>Tipos</h1>

<div class="toolbar-lista">
    <div class="grupo-esquerda">
        <a href="paginainicial.php" class="btn-voltar">← Voltar</a>
        <a href="tipo_novo.php" class="botao">+ Novo Tipo</a>
    </div>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> tipo<?= $totalRegistros == 1 ? "" : "s" ?></div>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Controla Espaços</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row["ID"]) ?></td>
                <td><?= htmlspecialchars($row["NOME"]) ?></td>
                <td><?= $row["CONTROLA_ESPACOS"] == "S" ? "Sim" : "Não" ?></td>
                <td>
                    <a href="tipo_editar.php?id=<?= $row["ID"] ?>">Editar</a> |
                    <a href="tipo_excluir.php?id=<?= $row["ID"] ?>" onclick="return confirm('Excluir este tipo?')">Excluir</a>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4">Nenhum tipo cadastrado</td>
        </tr>
    <?php } ?>

</table>

<?php include("rodape.php"); ?>