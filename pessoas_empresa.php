<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("funcoes_limite.php");

$categoria_id = intval($_SESSION["categoria_id"]);
$is_admin     = ($categoria_id == 1);

if (!isset($_GET["empresa_id"]) || !is_numeric($_GET["empresa_id"])) {
    header("Location: empresa.php");
    exit;
}

$empresa_id = (int) $_GET["empresa_id"];

// Funcionário/Expositor só podem ver a própria empresa,
// mesmo que tentem trocar o empresa_id na URL
if (!$is_admin) {
    $empresa_id = intval($_SESSION["empresa_id"]);
}

$stmtEmpresa = $conn->prepare("SELECT * FROM EMPRESAS WHERE ID = ?");
$stmtEmpresa->bind_param("i", $empresa_id);
$stmtEmpresa->execute();
$empresaResult = $stmtEmpresa->get_result();

if ($empresaResult->num_rows === 0) {
    header("Location: empresa.php");
    exit;
}

$empresa = $empresaResult->fetch_assoc();

$busca = isset($_GET["busca"]) ? trim($_GET["busca"]) : "";

$sqlBase = "SELECT P.*, E.NOME_FANTASIA, C.NOME AS NOME_CARGO
        FROM PESSOAS P
        INNER JOIN EMPRESAS E ON E.ID = P.EMPRESA_ID
        LEFT JOIN CARGOS C ON C.ID = P.CARGO_ID
        WHERE P.EMPRESA_ID = ? AND P.EXCLUIDO_EM IS NULL";

if ($busca !== "") {
    $sql = $sqlBase . "
        AND (UPPER(P.NOME) LIKE UPPER(?)
           OR UPPER(C.NOME) LIKE UPPER(?)
           OR UPPER(P.CPF) LIKE UPPER(?)
           OR UPPER(P.TELEFONE) LIKE UPPER(?))
        ORDER BY P.ID DESC";
    $stmt  = $conn->prepare($sql);
    $termo = "%" . $busca . "%";
    $stmt->bind_param("isss", $empresa_id, $termo, $termo, $termo, $termo);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql  = $sqlBase . " ORDER BY P.ID DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$limiteInfo    = obterLimitePessoas($conn, $empresa_id);
$temLimite     = $limiteInfo["limite"] > 0;
$limiteAtingido = $temLimite && $limiteInfo["total"] >= $limiteInfo["limite"];

// Categoria 3 (Expositor) não pode nem clicar em Nova Pessoa quando atingir o limite
$bloquearBotaoNovo = ($categoria_id == 3) && $limiteAtingido;

$titulo_pagina = "Pessoas - " . $empresa["NOME_FANTASIA"];
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
.toolbar-lista .grupo-esquerda {
    display: flex;
    align-items: center;
    gap: 14px;
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
.contagem-registros {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    margin: 8px 0;
}
.contagem-limite {
    font-size: 14px;
    font-weight: 600;
    margin: 8px 0;
    color: #475569;
}
.contagem-limite.atingido {
    color: #b91c1c;
}
.aviso-limite {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 14px;
    margin: 8px 0 16px;
}
</style>

<h1>Pessoas — <?= htmlspecialchars($empresa["NOME_FANTASIA"]) ?></h1>

<div class="toolbar-lista">
    <div class="grupo-esquerda">
        <a href="empresa.php" class="btn-voltar">← Voltar</a>

        <?php if ($bloquearBotaoNovo) { ?>
            <span class="botao desabilitado" title="Limite de pessoas atingido">+ Nova Pessoa</span>
        <?php } else { ?>
            <a class="botao" href="pessoa_nova.php?empresa_id=<?= $empresa_id ?>">+ Nova Pessoa</a>
        <?php } ?>
    </div>

    <form method="GET" action="pessoas_empresa.php" class="form-pesquisa">
        <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">
        <input type="text" name="busca" class="input-pesquisa" placeholder="Pesquisar..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-pesquisar" title="Pesquisar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </button>
        <?php if ($busca !== "") { ?>
            <a href="pessoas_empresa.php?empresa_id=<?= $empresa_id ?>" class="btn-limpar" title="Limpar pesquisa">Limpar</a>
        <?php } ?>
    </form>
</div>

<?php $totalRegistros = $result ? $result->num_rows : 0; ?>
<div class="contagem-registros"><?= $totalRegistros ?> registro<?= $totalRegistros == 1 ? "" : "s" ?></div>

<?php if ($temLimite) { ?>
    <div class="contagem-limite <?= $limiteAtingido ? "atingido" : "" ?>">
        <?= $limiteInfo["total"] ?> de <?= $limiteInfo["limite"] ?> pessoas cadastradas
    </div>
<?php } ?>

<?php if ($limiteAtingido) { ?>
    <div class="aviso-limite">
        Limite de pessoas atingido para esta empresa.
        <?php if ($categoria_id == 2) { ?>
            Para cadastrar mais pessoas, será necessária a autorização de um administrador.
        <?php } elseif ($categoria_id == 3) { ?>
            Não é possível cadastrar novas pessoas até que o limite seja liberado por um administrador.
        <?php } ?>
    </div>
<?php } ?>

<table>
    <tr>
        <th>ID</th>
        <th class="col-foto">Foto</th>
        <th>Nome</th>
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
            <td colspan="8">Nenhuma pessoa cadastrada para esta empresa</td>
        </tr>
    <?php } ?>

</table>

<?php include("rodape.php"); ?>