<?php
session_start();
include("conexao.php");

$erro = "";

if (isset($_POST["entrar"])) {
    $email = trim(strip_tags($_POST["email"]));
    $senha = md5(trim($_POST["senha"]));

    $sql  = "SELECT ID, NOME, CATEGORIA_ID, EMPRESA_ID FROM usuarios WHERE email = ? AND senha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $dados = $result->fetch_assoc();

        $_SESSION["usuario"]      = $dados["NOME"];
        $_SESSION["usuario_id"]   = $dados["ID"];
        $_SESSION["categoria_id"] = $dados["CATEGORIA_ID"];
        $_SESSION["empresa_id"]   = $dados["EMPRESA_ID"];

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
<title>Login</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

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
}

h2{
    margin-bottom:20px;
}

.erro{
    color:red;
    margin-bottom:15px;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
}

button{
    width:100%;
    padding:12px;
    border:none;
    background:#0d6efd;
    color:#fff;
    cursor:pointer;
}

button:hover{
    background:#0056d2;
}
</style>

</head>

<body>

<div class="login">

<form method="POST">

<h2>LOGIN</h2>

<?php if ($erro != "") { ?>
<p class="erro"><?= $erro ?></p>
<?php } ?>

<input type="email" name="email" placeholder="Email" required autofocus>

<input type="password" name="senha" placeholder="Senha" required>

<button type="submit" name="entrar">
Entrar
</button>

</form>

</div>

</body>
</html>