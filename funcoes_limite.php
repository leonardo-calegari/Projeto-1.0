<?php
/**
 * Retorna o total de pessoas ativas cadastradas em uma empresa
 * e o limite permitido conforme o TIPO da empresa.
 *
 * @return array ["total" => int, "limite" => int]
 *               limite = 0 significa "sem limite definido"
 */
function obterLimitePessoas($conn, $empresa_id)
{
    $empresa_id = intval($empresa_id);

    $sql  = "SELECT T.LIMITE_PESSOAS
             FROM EMPRESAS E
             INNER JOIN TIPOS T ON T.ID = E.TIPO_ID
             WHERE E.ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $tipo = $stmt->get_result()->fetch_assoc();

    $limite = $tipo ? intval($tipo["LIMITE_PESSOAS"]) : 0;

    $sql  = "SELECT COUNT(*) AS TOTAL FROM PESSOAS WHERE EMPRESA_ID = ? AND EXCLUIDO_EM IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["TOTAL"];

    return ["total" => intval($total), "limite" => $limite];
}

/**
 * Verifica se a empresa atingiu o limite de pessoas.
 * limite = 0 é tratado como "sem limite" (nunca atinge).
 */
function limiteAtingido($conn, $empresa_id)
{
    $info = obterLimitePessoas($conn, $empresa_id);

    if ($info["limite"] <= 0) {
        return false;
    }

    return $info["total"] >= $info["limite"];
}

/**
 * Valida um email/senha de usuário ADMIN (CATEGORIA_ID = 1).
 * Usado para liberar o cadastro além do limite quando quem está
 * inserindo é um usuário FUNCIONARIO (CATEGORIA_ID = 2).
 */
function validarSenhaAdmin($conn, $email, $senha)
{
    $email = trim(strip_tags($email));
    $senha = md5(trim($senha));

    $sql  = "SELECT ID FROM usuarios WHERE email = ? AND senha = ? AND CATEGORIA_ID = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows === 1;
}   