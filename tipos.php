<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");

$sql    = "SELECT * FROM TIPOS ORDER BY ID DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Tipos</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:Arial,sans-serif; background:#f5f5f5; padding:40px; }
h1{ margin-bottom:15px; }
a{ color:#0d6efd; }
table{ border-collapse:collapse; width:100%; background:#fff; margin-top:10px; }
th, td{ border:1px solid #ddd; padding:10px; text-align:left; }
th{ background:#f0f0f0; }
.botao{ display:inline-block; padding:10px 16px; background:#0d6efd; color:white; text-decoration:none; border-radius:5px; margin-bottom:15px; }
.botao:hover{ background:#0056d2; }
.btn-voltar{ display:inline-block; padding:8px 16px; background:#6c757d; color:white; text-decoration:none; border-radius:5px; margin-bottom:20px; }
.btn-voltar:hover{ background:#565e64; }
</style>
</head>

<body>

<h1>Tipos</h1>

<a href="paginainicial.php" class="btn-voltar">← Voltar</a>

<a href="tipo_novo.php" class="botao">+ Novo Tipo</a>

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

</body>
</html>