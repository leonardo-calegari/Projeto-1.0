<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");
include_once("paginacao.php");

// Só administrador (categoria 1)
if (!ehAdmin()) {
    http_response_code(403);
    die("Acesso restrito ao administrador.");
}

$busca      = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";
$filtro_emp = (isset($_GET["empresa_id"]) && is_numeric($_GET["empresa_id"])) ? (int)$_GET["empresa_id"] : 0;

// Empresa selecionada (vinda do botão Usuários da lista de empresas ou do filtro)
$empresaSel = null;
if ($filtro_emp > 0) {
    $st = $conn->prepare("SELECT NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
    $st->bind_param("i", $filtro_emp);
    $st->execute();
    $empresaSel = $st->get_result()->fetch_assoc();
    if (!$empresaSel) {
        $filtro_emp = 0;
    }
}

$condicoes = [];
$params    = [];
$tipos     = "";

if ($filtro_emp > 0) {
    $condicoes[] = "U.EMPRESA_ID = ?";
    $params[]    = $filtro_emp;
    $tipos      .= "i";
}

if ($busca !== "") {
    $condicoes[] = "(UPPER(U.NOME) LIKE UPPER(?) OR UPPER(U.EMAIL) LIKE UPPER(?) OR UPPER(E.NOME_FANTASIA) LIKE UPPER(?))";
    $termo = "%" . $busca . "%";
    array_push($params, $termo, $termo, $termo);
    $tipos .= "sss";
}

$sql = "SELECT U.ID AS ID, U.NOME AS NOME, U.EMAIL AS EMAIL, U.CATEGORIA_ID AS CATEGORIA_ID,
               CAT.NOME AS NOME_CATEGORIA, E.NOME_FANTASIA AS NOME_FANTASIA
        FROM USUARIOS U
        LEFT JOIN CATEGORIAS CAT ON CAT.ID = U.CATEGORIA_ID
        LEFT JOIN EMPRESAS E ON E.ID = U.EMPRESA_ID";
if ($condicoes) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}
$sql .= " ORDER BY U.ID DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$empresas = $conn->query("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE EXCLUIDO_EM IS NULL ORDER BY NOME_FANTASIA");

$titulo_pagina = "Usuários";
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
.toolbar-lista .grupo-esquerda { display: flex; align-items: center; gap: 14px; }
.form-pesquisa { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.form-pesquisa .input-pesquisa {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    min-width: 260px;
}
.form-pesquisa .input-pesquisa:focus { outline: none; border-color: #2563eb; }
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
.btn-limpar { color: #2563eb; font-weight: 600; text-decoration: none; white-space: nowrap; }
.btn-limpar:hover { text-decoration: underline; }
.contagem-registros { font-size: 14px; font-weight: 600; color: #475569; margin: 8px 0; }
</style>

<h1>Usuários<?= $empresaSel ? " — " . htmlspecialchars($empresaSel["NOME_FANTASIA"]) : "" ?></h1>

<div class="toolbar-lista">
    <div class="grupo-esquerda">
        <a href="<?= $filtro_emp > 0 ? "empresa.php" : "paginainicial.php" ?>" class="btn-voltar">← Voltar</a>
        <a href="usuario_novo.php<?= $filtro_emp > 0 ? "?empresa_id=" . $filtro_emp : "" ?>" class="botao">+ Novo Usuário</a>
        <a href="relatorio_usuarios.php?empresa_id=<?= $filtro_emp ?>&busca=<?= urlencode($busca) ?>" target="_blank" class="botao">Relatório</a>
    </div>

    <form method="GET" action="usuarios.php" class="form-pesquisa">
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
            <a href="usuarios.php" class="btn-limpar" title="Limpar pesquisa">Limpar</a>
        <?php } ?>
    </form>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<?php $pg = prepararPaginacao($result); ?>
<div class="contagem-registros"><?= $totalRegistros ?> usuário<?= $totalRegistros == 1 ? "" : "s" ?></div>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>E-mail</th>
        <th>Categoria</th>
        <th>Empresa</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php for ($i = 0; $i < $pg["limite"] && ($row = $result->fetch_assoc()); $i++) { ?>
            <tr>
                <td><?= htmlspecialchars($row["ID"]) ?></td>
                <td><?= htmlspecialchars($row["NOME"]) ?></td>
                <td><?= htmlspecialchars($row["EMAIL"]) ?></td>
                <td><?= htmlspecialchars(mb_convert_case($row["NOME_CATEGORIA"] ?? "", MB_CASE_TITLE, "UTF-8")) ?></td>
                <td><?= !empty($row["NOME_FANTASIA"]) ? htmlspecialchars($row["NOME_FANTASIA"]) : "—" ?></td>
                <td>
                    <a href="usuario_editar.php?id=<?= $row["ID"] ?>">Editar</a>
                    <?php if ((int)$row["ID"] !== $usuarioLogadoId) { ?>
                        | <a href="usuario_excluir.php?id=<?= $row["ID"] ?>" onclick="return confirm('Excluir este usuário?')">Excluir</a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="6">Nenhum usuário cadastrado</td>
        </tr>
    <?php } ?>

</table>

<div class="contagem-registros"><?= $totalRegistros ?> usuário<?= $totalRegistros == 1 ? "" : "s" ?></div>

<?php exibirPaginacao($pg); ?>

<script src="js/filtro_tabela.js"></script>

<?php include("rodape.php"); ?>
