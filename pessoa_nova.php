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

$empresa_id = (int)$_GET["empresa_id"];

// Funcionário/Expositor só podem cadastrar na própria empresa,
// mesmo que tentem trocar o empresa_id na URL
if (!$is_admin) {
    $empresa_id = intval($_SESSION["empresa_id"]);
}

$stmt = $conn->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESAS WHERE ID = ?");
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) {
    header("Location: empresa.php");
    exit;
}

// Cargos disponíveis: só os cadastrados para essa empresa
$stmtCargos = $conn->prepare("SELECT ID, NOME FROM CARGOS WHERE ID_EMPRESA = ? ORDER BY NOME");
$stmtCargos->bind_param("i", $empresa_id);
$stmtCargos->execute();
$cargos = $stmtCargos->get_result();

$limiteInfo     = obterLimitePessoas($conn, $empresa_id);
$temLimite      = $limiteInfo["limite"] > 0;
$limiteAtingido = $temLimite && $limiteInfo["total"] >= $limiteInfo["limite"];

// Categoria 3 (Expositor): formulário bloqueado ao atingir o limite
$formBloqueado = ($categoria_id == 3) && $limiteAtingido;

// Categoria 2 (Funcionário): precisa de autorização de um Admin ao atingir o limite
$precisaAutorizacao = ($categoria_id == 2) && $limiteAtingido;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Nova Pessoa</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:Arial,sans-serif;
    background:#f5f5f5;
    padding:40px;
}
.container{
    background:#fff;
    max-width:600px;
    padding:30px;
    border-radius:8px;
}
h1{
    margin-bottom:20px;
}
label{
    display:block;
    margin-top:15px;
    font-weight:bold;
}
input,select{
    width:100%;
    padding:10px;
    margin-top:5px;
    border:1px solid #ccc;
    border-radius:4px;
}
input[disabled]{
    background:#f1f5f9;
    color:#555;
}
input[type=file]{
    padding:8px;
}
button{
    margin-top:25px;
    padding:10px 20px;
    background:#0d6efd;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
button:hover{
    background:#0056d2;
}
button[disabled]{
    background:#cbd5e1;
    color:#64748b;
    cursor:not-allowed;
}
.btn-voltar{
    display:inline-block;
    padding:8px 16px;
    background:#6c757d;
    color:#fff;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:20px;
}
.btn-voltar:hover{
    background:#565e64;
}
#preview_foto{
    display:none;
    margin-top:12px;
    max-width:200px;
    max-height:200px;
    border-radius:6px;
    border:1px solid #ccc;
}
.contagem-limite{
    font-size:14px;
    font-weight:600;
    color:#475569;
    margin:6px 0 16px;
}
.contagem-limite.atingido{
    color:#b91c1c;
}
.aviso-limite{
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#b91c1c;
    padding:10px 14px;
    border-radius:6px;
    font-size:14px;
    margin-bottom:16px;
}
.autorizacao{
    background:#fffbeb;
    border:1px solid #fde68a;
    border-radius:6px;
    padding:16px;
    margin-top:20px;
}
.autorizacao p{
    font-size:13px;
    color:#92400e;
    margin-bottom:6px;
}
</style>

</head>

<body>

<div class="container">

<h1>Nova Pessoa</h1>

<a href="pessoas_empresa.php?empresa_id=<?= $empresa_id ?>" class="btn-voltar">
← Voltar
</a>

<?php if ($temLimite) { ?>
    <div class="contagem-limite <?= $limiteAtingido ? "atingido" : "" ?>">
        <?= $limiteInfo["total"] ?> de <?= $limiteInfo["limite"] ?> pessoas cadastradas
    </div>
<?php } ?>

<?php if ($formBloqueado) { ?>

    <div class="aviso-limite">
        O limite de pessoas desta empresa foi atingido. Não é possível cadastrar novas pessoas
        até que um administrador libere o limite.
    </div>

<?php } else { ?>

    <?php if ($limiteAtingido) { ?>
        <div class="aviso-limite">
            O limite de pessoas desta empresa foi atingido.
            <?php if ($precisaAutorizacao) { ?>
                Informe abaixo o email e a senha de um administrador para liberar este cadastro.
            <?php } ?>
        </div>
    <?php } ?>

    <form method="POST" action="pessoa_salvar.php" enctype="multipart/form-data">

        <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

        <label>Empresa</label>
        <input
            type="text"
            value="<?= htmlspecialchars($empresa["NOME_FANTASIA"]) ?>"
            disabled
        >

        <label for="cargo_id">Cargo</label>
        <select id="cargo_id" name="cargo_id">
            <option value="">Nenhum</option>

            <?php while($cargo = $cargos->fetch_assoc()){ ?>

                <option value="<?= $cargo["ID"] ?>">
                    <?= htmlspecialchars($cargo["NOME"]) ?>
                </option>

            <?php } ?>

        </select>

        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required autofocus>

        <label for="cpf">CPF</label>
        <input type="text" id="cpf" name="cpf" maxlength="11">

        <label for="documento">Documento</label>
        <input type="text" id="documento" name="documento" maxlength="30">

        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone" maxlength="14">

        <label for="ingresso_permanente">Ingresso Permanente</label>
        <select id="ingresso_permanente" name="ingresso_permanente">
            <option value="N">Não</option>
            <option value="S">Sim</option>
        </select>

        <label for="foto">Foto</label>
        <input
            type="file"
            id="foto"
            name="foto"
            accept="image/*"
            onchange="mostrarPreview(this)"
        >

        <img id="preview_foto">

        <?php if ($precisaAutorizacao) { ?>
            <div class="autorizacao">
                <p>Autorização de administrador necessária (limite atingido)</p>

                <label for="admin_email">Email do administrador</label>
                <input type="email" id="admin_email" name="admin_email" required>

                <label for="admin_senha">Senha do administrador</label>
                <input type="password" id="admin_senha" name="admin_senha" required>
            </div>
        <?php } ?>

        <button type="submit">Salvar</button>

    </form>

<?php } ?>

</div>

<script>
function mostrarPreview(input){

    const preview = document.getElementById("preview_foto");

    if(input.files && input.files[0]){

        const leitor = new FileReader();

        leitor.onload = function(e){

            preview.src = e.target.result;
            preview.style.display = "block";

        };

        leitor.readAsDataURL(input.files[0]);

    }else{

        preview.style.display = "none";

    }

}
</script>

</body>
</html>