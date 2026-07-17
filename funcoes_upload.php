<?php
/**
 * Processa o upload de uma foto enviada via formulário.
 *
 * @param string      $nomeCampo  Nome do input type="file" (ex: "foto")
 * @param string|null $fotoAtual  Nome do arquivo já salvo (usado na edição, quando
 *                                o usuário não escolhe uma foto nova)
 * @return string|null Nome do arquivo salvo (novo ou o mesmo de antes)
 */
function processarUploadFoto($nomeCampo, $fotoAtual = null) {

    // Nenhum arquivo foi enviado (ex: na edição, usuário não trocou a foto)
    if (!isset($_FILES[$nomeCampo]) || $_FILES[$nomeCampo]["error"] === UPLOAD_ERR_NO_FILE) {
        return $fotoAtual;
    }

    if ($_FILES[$nomeCampo]["error"] !== UPLOAD_ERR_OK) {
        die("Erro no upload da foto (código " . $_FILES[$nomeCampo]["error"] . ").");
    }

    $extensoesPermitidas = ["jpg", "jpeg", "png", "gif", "webp"];
    $extensao = strtolower(pathinfo($_FILES[$nomeCampo]["name"], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("Formato de imagem não permitido. Use JPG, PNG, GIF ou WEBP.");
    }

    $tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    if ($_FILES[$nomeCampo]["size"] > $tamanhoMaximo) {
        die("A foto deve ter no máximo 5MB.");
    }

    $pastaDestino = __DIR__ . "/uploads/pessoas/";
    if (!is_dir($pastaDestino)) {
        mkdir($pastaDestino, 0755, true);
    }

    $nomeArquivo     = uniqid("pessoa_", true) . "." . $extensao;
    $caminhoDestino  = $pastaDestino . $nomeArquivo;

    if (!move_uploaded_file($_FILES[$nomeCampo]["tmp_name"], $caminhoDestino)) {
        die("Erro ao salvar a foto no servidor.");
    }

    return $nomeArquivo;
}