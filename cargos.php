<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$busca = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";

if ($busca !== "") {
    $stmt = $conn->prepare("SELECT * FROM CARGOS WHERE UPPER(NOME) LIKE UPPER(?) ORDER BY NOME");
    $termo = "%" . $busca . "%";
    $stmt->bind_param("s", $termo);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql    = "SELECT * FROM CARGOS ORDER BY NOME";
    $result = $conn->query($sql);
}

$titulo_pagina = "Cargos";
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
.form-pesquisa {
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-pesquisa .input-pesquisa {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    min-width: 260px;
}
.form-pesquisa .input-pesquisa:focus {
    outline: none;
    border-color: #2563eb;
}
.btn-pesquisar {
    background: #475569;
    border: none;
    color: #fff;
    width: 44px;
    height: 44px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
}
.btn-pesquisar:hover { background: #334155; }
.btn-pesquisar svg { width: 18px; height: 18px; }
.btn-limpar {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}
.btn-limpar:hover { text-decoration: underline; }
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

<h1>Cargos</h1>

<div class="toolbar-lista">
    <div class="grupo-esquerda">
        <a href="paginainicial.php" class="btn-voltar">← Voltar</a>
        <a href="cargo_novo.php" class="botao">+ Novo Cargo</a>
    </div>

    <form method="GET" action="cargos.php" class="form-pesquisa">
        <input type="text" name="busca" class="input-pesquisa" placeholder="Pesquisar..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-pesquisar" title="Pesquisar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </button>
        <?php if ($busca !== "") { ?>
            <a href="cargos.php" class="btn-limpar" title="Limpar pesquisa">Limpar</a>
        <?php } ?>
    </form>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> registro<?= $totalRegistros == 1 ? "" : "s" ?></div>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row["ID"]) ?></td>
                <td><?= htmlspecialchars($row["NOME"]) ?></td>
                <td>
                    <a href="cargo_editar.php?id=<?= $row["ID"] ?>">Editar</a> |
                    <a href="cargo_excluir.php?id=<?= $row["ID"] ?>" onclick="return confirm('Excluir este cargo?')">Excluir</a>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="3">Nenhum cargo cadastrado</td>
        </tr>
    <?php } ?>

</table>

<?php include("rodape.php"); ?>