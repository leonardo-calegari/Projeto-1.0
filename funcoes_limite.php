<?php
/**
 * Retorna o total de pessoas ativas cadastradas em uma empresa
 * e o limite permitido conforme o TIPO e a quantidade de ESPAÇOS da empresa.
 *
 * Regra: o limite do tipo vale POR ESPAÇO.
 *   - tipo que controla espaços: limite = LIMITE_PESSOAS x QUANTIDADE_ESPACOS
 *     (ex.: limite 2 e 3 espaços = 6 pessoas; 2 espaços = 4 pessoas)
 *   - tipo que não controla espaços: limite = LIMITE_PESSOAS
 *   - empresa com 0 espaços num tipo que controla espaços: conta como 1 espaço
 *
 * @return array ["total" => int, "limite" => int, "limite_tipo" => int, "espacos" => int]
 *               limite = 0 significa "sem limite definido"
 */
function obterLimitePessoas($conn, $empresa_id)
{
    $empresa_id = intval($empresa_id);

    $sql  = "SELECT T.LIMITE_PESSOAS, T.CONTROLA_ESPACOS, E.QUANTIDADE_ESPACOS
             FROM EMPRESAS E
             INNER JOIN TIPOS T ON T.ID = E.TIPO_ID
             WHERE E.ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $tipo = $stmt->get_result()->fetch_assoc();

    $limiteTipo = $tipo ? intval($tipo["LIMITE_PESSOAS"]) : 0;
    $espacos    = $tipo ? intval($tipo["QUANTIDADE_ESPACOS"]) : 0;
    $controla   = $tipo && $tipo["CONTROLA_ESPACOS"] === "S";

    // Sem limite no tipo = sem limite na empresa
    if ($limiteTipo <= 0) {
        $limite = 0;
    } elseif ($controla) {
        $limite = $limiteTipo * max(1, $espacos);
    } else {
        $limite = $limiteTipo;
    }

    $sql  = "SELECT COUNT(*) AS TOTAL FROM PESSOAS WHERE EMPRESA_ID = ? AND EXCLUIDO_EM IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["TOTAL"];

    return [
        "total"       => intval($total),
        "limite"      => $limite,
        "limite_tipo" => $limiteTipo,
        "espacos"     => $espacos,
    ];
}


function limiteAtingido($conn, $empresa_id)
{
    $info = obterLimitePessoas($conn, $empresa_id);

    if ($info["limite"] <= 0) {
        return false;
    }

    return $info["total"] >= $info["limite"];
}


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