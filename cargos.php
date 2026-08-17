<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

$busca      = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";
$filtro_emp = $is_admin && isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"]) ? intval($_GET["empresa_id"]) : 0;

$sqlBase = "SELECT C.*, E.NOME_FANTASIA
            FROM CARGOS C
            INNER JOIN EMPRESAS E ON E.ID = C.ID_EMPRESA";

$condicoes = [];
$params    = [];
$tipos     = "";

if ($is_admin) {
    if ($filtro_emp > 0) {
        $condicoes[] = "C.ID_EMPRESA = ?";
        $params[]    = $filtro_emp;
        $tipos      .= "i";
    }
} else {
    // Funcionário e Expositor só veem os cargos da própria empresa
    $condicoes[] = "C.ID_EMPRESA = ?";
    $params[]    = intval($_SESSION["empresa_id"]);
    $tipos      .= "i";
}

if ($busca !== "") {
    $condicoes[] = "UPPER(C.NOME) LIKE UPPER(?)";
    $params[]    = "%" . $busca . "%";
    $tipos      .= "s";
}

$sql = $sqlBase;
if (count($condicoes) > 0) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY E.NOME_FANTASIA, C.NOME";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Lista de empresas para o filtro (só admin usa)
if ($is_admin) {
    $empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");
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
    flex-wrap: wrap;
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
.form-pesquisa .select-empresa {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
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

        <?php if ($is_admin) { ?>
            <select name="empresa_id" class="select-empresa" onchange="this.form.submit()">
                <option value="">Todas as empresas</option>
                <?php while ($emp = $empresas->fetch_assoc()) { ?>
                    <option value="<?= $emp["ID"] ?>" <?= $emp["ID"] == $filtro_emp ? "selected" : "" ?>>
                        <?= htmlspecialchars($emp["NOME_FANTASIA"]) ?>
                    </option>
                <?php } ?>
            </select>
        <?php } ?>

        <input type="text" name="busca" class="input-pesquisa" placeholder="Pesquisar..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-pesquisar" title="Pesquisar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </button>
        <?php if ($busca !== "" || $filtro_emp > 0) { ?>
            <a href="cargos.php" class="btn-limpar" title="Limpar pesquisa">Limpar</a>
        <?php } ?>
    </form>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> cargo<?= $totalRegistros == 1 ? "" : "s" ?></div>

<table>
    <tr>
        <th>ID</th>
        <?php if ($is_admin) { ?><th>Empresa</th><?php } ?>
        <th>Nome</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row["ID"]) ?></td>
                <?php if ($is_admin) { ?><td><?= htmlspecialchars($row["NOME_FANTASIA"]) ?></td><?php } ?>
                <td><?= htmlspecialchars($row["NOME"]) ?></td>
                <td>
                    <a href="cargo_editar.php?id=<?= $row["ID"] ?>">Editar</a> |
                    <a href="cargo_excluir.php?id=<?= $row["ID"] ?>" onclick="return confirm('Excluir este cargo?')">Excluir</a>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>    
            <td colspan="<?= $is_admin ? 4 : 3 ?>">Nenhum cargo cadastrado</td>
        </tr>
    <?php } ?>

</table>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> cargo<?= $totalRegistros == 1 ? "" : "s" ?></div>

<?php include("rodape.php"); ?>