<?php
session_start();
include("conexao.php");
include("funcoes_log.php");

// Já está logado (com categoria na sessão): vai direto para a página inicial.
// Sessões antigas, sem categoria, caem no formulário para entrar de novo.
if (isset($_SESSION["usuario"]) && (int)($_SESSION["categoria_id"] ?? 0) > 0) {
    header("Location: paginainicial.php");
    exit;
}

$erro  = "";
$email = "";

if (isset($_POST["entrar"])) {
    $email = trim(strip_tags($_POST["email"]));
    $senha = md5(trim($_POST["senha"]));

    $stmt = $conn->prepare("SELECT * FROM USUARIOS WHERE EMAIL = ? AND SENHA = ?");
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        // chaves em minúsculo, para não depender de como a coluna foi criada
        $dados = array_change_key_case($result->fetch_assoc(), CASE_LOWER);

        // 1 = administrador, 2 = funcionário, 3 = expositor
        $categoria = (int)($dados["categoria_id"] ?? 0);

        session_regenerate_id(true);

        $_SESSION["usuario"]      = $dados["nome"];
        $_SESSION["usuario_id"]   = (int)$dados["id"];
        $_SESSION["categoria_id"] = $categoria;
        $_SESSION["empresa_id"]   = $dados["empresa_id"] ?? null;

        if (function_exists("registrarLog")) {
            registrarLog("LOGIN", "LOGIN", dadosLog(["EMAIL" => $email]));
        }

        header("Location: paginainicial.php");
        exit;
    }

    $erro = "Email ou senha inválidos";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Sistema de Credenciamento</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:Arial,sans-serif;
    background:#f4f4f4;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.login{
    width:350px;
    background:#fff;
    padding:30px;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
}
h2{margin-bottom:20px;}
.erro{color:red;margin-bottom:15px;}
input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:4px;
}
button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:4px;
    background:#0d6efd;
    color:#fff;
    cursor:pointer;
}
button:hover{background:#0056d2;}
</style>
</head>
<body>

<div class="login">
    <form method="POST">
        <h2>LOGIN</h2>

        <?php if ($erro != "") { ?>
            <p class="erro"><?= htmlspecialchars($erro) ?></p>
        <?php } ?>

        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required autofocus>
        <input type="password" name="senha" placeholder="Senha" required>

        <button type="submit" name="entrar">Entrar</button>
    </form>
</div>

</body>
</html>