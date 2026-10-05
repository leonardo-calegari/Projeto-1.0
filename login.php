<?php
session_start();
include("conexao.php");
include("funcoes_log.php");

// Já está logado: vai direto para a página inicial
if (isset($_SESSION["usuario"])) {
    header("Location: paginainicial.php");
    exit;
}

$erro  = "";
$email = "";

if (isset($_POST["entrar"])) {
    $email = trim(strip_tags($_POST["email"]));
    $senha = md5(trim($_POST["senha"]));

    $sql  = "SELECT * FROM usuarios WHERE email = ? AND senha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        // chaves em minúsculo, para não depender de como a coluna foi criada
        $dados = array_change_key_case($result->fetch_assoc(), CASE_LOWER);

        // Categoria: usa categoria_id se existir; senão tenta pelo perfil
        // 1 = administrador, 2 = funcionário, 3 = expositor
        $perfil    = $dados["perfil"] ?? "";
        $categoria = $dados["categoria_id"] ?? null;

        if ($categoria === null || $categoria === "") {
            $mapa = [
                "admin"       => 1,
                "administrador" => 1,
                "funcionario" => 2,
                "funcionário" => 2,
                "expositor"   => 3,
            ];
            $categoria = is_numeric($perfil)
                ? (int)$perfil
                : ($mapa[mb_strtolower($perfil)] ?? 0);
        }

        session_regenerate_id(true);

        $_SESSION["usuario"]      = $dados["nome"];
        $_SESSION["usuario_id"]   = $dados["id"];
        $_SESSION["perfil"]       = $perfil;
        $_SESSION["categoria_id"] = (int)$categoria;
        $_SESSION["empresa_id"]   = $dados["empresa_id"] ?? null;

        registrarLog("LOGIN", "LOGIN", dadosLog(["EMAIL" => $email]));

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
<p class="erro"><?= htmlspecialchars($erro) ?></p>
<?php } ?>

<input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required autofocus>

<input type="password" name="senha" placeholder="Senha" required>

<button type="submit" name="entrar">
Entrar
</button>

</form>

</div>

</body>
</html>