<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

// Funcionário/Expositor não acessam a listagem geral: vão direto para a própria empresa
if (!$is_admin) {
    header("Location: pessoas_empresa.php?empresa_id=" . intval($_SESSION["empresa_id"]));
    exit;
}

$busca      = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";
$filtro_emp = isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"]) ? intval($_GET["empresa_id"]) : 0;

$sqlBase = "SELECT P.*, E.NOME_FANTASIA, C.NOME AS NOME_CARGO
        FROM PESSOAS P
        INNER JOIN EMPRESAS E ON E.ID = P.EMPRESA_ID
        LEFT JOIN CARGOS C ON C.ID = P.CARGO_ID
        WHERE P.EXCLUIDO_EM IS NULL";

$condicoes = [];
$params    = [];
$tipos     = "";

if ($filtro_emp > 0) {
    $condicoes[] = "P.EMPRESA_ID = ?";
    $params[]    = $filtro_emp;
    $tipos      .= "i";
}

if ($busca !== "") {
    $condicoes[] = "(UPPER(P.NOME) LIKE UPPER(?)
           OR UPPER(E.NOME_FANTASIA) LIKE UPPER(?)
           OR UPPER(C.NOME) LIKE UPPER(?)
           OR UPPER(P.CPF) LIKE UPPER(?)
           OR UPPER(P.TELEFONE) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo, $termo, $termo, $termo);
    $tipos .= "sssss";
}

$sql = $sqlBase;
if (count($condicoes) > 0) {
    $sql .= " AND " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY P.ID DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");

$titulo_pagina = "Pessoas";
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
.toolbar-lista .botao.desabilitado {
    background: #cbd5e1;
    color: #64748b;
    cursor: not-allowed;
    pointer-events: none;
}
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
.form-pesquisa .select-empresa {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
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

<h1>Pessoas</h1>

<div class="toolbar-lista">
    <div class="grupo-esquerda">
        <a href="paginainicial.php" class="btn-voltar">← Voltar</a>

        <?php if ($filtro_emp > 0) { ?>
            <a class="botao" href="pessoa_nova.php?empresa_id=<?= $filtro_emp ?>">+ Nova Pessoa</a>
        <?php } else { ?>
            <span class="botao desabilitado" title="Selecione uma empresa no filtro para cadastrar">+ Nova Pessoa</span>
        <?php } ?>
    </div>

    <form method="GET" action="pessoas.php" class="form-pesquisa">
        <select name="empresa_id" class="select-empresa" onchange="this.form.submit()">
            <option value="">Todas as empresas</option>
            <?php while ($emp = $empresas->fetch_assoc()) { ?>
                <option value="<?= $emp["ID"] ?>" <?= $emp["ID"] == $filtro_emp ? "selected" : "" ?>>
                    <?= htmlspecialchars($emp["NOME_FANTASIA"]) ?>
                </option>
            <?php } ?>
        </select>

        <input type="text" name="busca" class="input-pesquisa" placeholder="Pesquisar..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-pesquisar" title="Pesquisar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </button>
        <?php if ($busca !== "" || $filtro_emp > 0) { ?>
            <a href="pessoas.php" class="btn-limpar" title="Limpar pesquisa">Limpar</a>
        <?php } ?>
    </form>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> pessoa<?= $totalRegistros == 1 ? "" : "s" ?></div>

<table>
    <tr>
        <th>ID</th>
        <th class="col-foto">Foto</th>
        <th>Nome</th>
        <th>Empresa</th>
        <th>Cargo</th>
        <th>CPF</th>
        <th>Telefone</th>
        <th>Permanente</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row["ID"]) ?></td>
                <td class="col-foto">
                    <?php if (!empty($row["FOTO"])) { ?>
                        <img src="uploads/pessoas/<?= htmlspecialchars($row["FOTO"]) ?>" alt="Foto" class="foto-miniatura">
                    <?php } else { ?>
                        <span class="sem-foto">—</span>
                    <?php } ?>
                </td>
                <td><?= htmlspecialchars($row["NOME"]) ?></td>
                <td><?= htmlspecialchars($row["NOME_FANTASIA"]) ?></td>
                <td><?= !empty($row["NOME_CARGO"]) ? htmlspecialchars($row["NOME_CARGO"]) : "—" ?></td>
                <td><?= htmlspecialchars($row["CPF"]) ?></td>
                <td><?= htmlspecialchars($row["TELEFONE"]) ?></td>
                <td><?= htmlspecialchars($row["INGRESSO_PERMANENTE"]) ?></td>
                <td>
                    <a href="pessoa_editar.php?id=<?= $row["ID"] ?>">Editar</a> |
                    <a href="pessoa_excluir.php?id=<?= $row["ID"] ?>" onclick="return confirm('Excluir esta pessoa?')">Excluir</a>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="9">Nenhuma pessoa cadastrada</td>
        </tr>
    <?php } ?>

</table>

<?php include("rodape.php"); ?>