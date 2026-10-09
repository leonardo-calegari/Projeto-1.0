<?php
// Gera o crachá de uma pessoa como imagem PNG.
// Uso: cracha.php?id=17            -> abre a imagem no navegador
//      cracha.php?id=17&baixar=1   -> baixa o arquivo
//
// Conteúdo do QR Code: ID da empresa (6 dígitos) + ID da pessoa (6 dígitos)
// Exemplo: empresa 23 e pessoa 17 -> 000023000017

// Guarda qualquer aviso do PHP para não corromper a imagem PNG
ob_start();

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include("conexao.php");
include("permissoes.php");

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// ---------- biblioteca do QR Code (Composer) ----------
$autoload = null;
$dir = __DIR__;
for ($i = 0; $i < 5; $i++) {
    if (file_exists($dir . "/vendor/autoload.php")) {
        $autoload = $dir . "/vendor/autoload.php";
        break;
    }
    $pai = dirname($dir);
    if ($pai === $dir) break;
    $dir = $pai;
}
if ($autoload === null) {
    http_response_code(500);
    exit("Bibliotecas não encontradas. No terminal, na pasta do projeto, rode: composer install");
}
require_once $autoload;

if (!class_exists(QRCode::class)) {
    http_response_code(500);
    exit("Biblioteca do QR Code não instalada. No terminal, na pasta do projeto, rode: composer require chillerlan/php-qrcode:^5.0");
}
if (!function_exists("imagecreatetruecolor")) {
    http_response_code(500);
    exit("A extensão GD do PHP está desligada. No php.ini do XAMPP, ative a linha extension=gd e reinicie o Apache.");
}

// ---------- dados da pessoa ----------
$id = (isset($_GET["id"]) && is_numeric($_GET["id"])) ? (int)$_GET["id"] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit("ID inválido.");
}

$sql = "SELECT P.ID, P.EMPRESA_ID, P.NOME, P.FOTO, P.INGRESSO_PERMANENTE,
               E.NOME_FANTASIA, C.NOME AS CARGO
        FROM PESSOAS P
        INNER JOIN EMPRESAS E ON E.ID = P.EMPRESA_ID
        LEFT JOIN CARGOS C ON C.ID = P.CARGO_ID
        WHERE P.ID = ? AND P.EXCLUIDO_EM IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    http_response_code(404);
    exit("Pessoa não encontrada.");
}

// Expositor (e funcionário vinculado) só emitem crachá da própria empresa
$empresaSessao = (int)($empresaLogadaId ?? 0);
if ((ehExpositor() || (ehFuncionario() && $empresaSessao > 0)) && (int)$p["EMPRESA_ID"] !== $empresaSessao) {
    http_response_code(403);
    exit("Você não tem permissão para emitir este crachá.");
}

$codigo = sprintf("%06d%06d", $p["EMPRESA_ID"], $p["ID"]);

// ---------- fontes (procura uma fonte TTF disponível) ----------
function acharFonte(bool $negrito, string $autoload): ?string
{
    $nomes = $negrito ? ["DejaVuSans-Bold.ttf", "arialbd.ttf"] : ["DejaVuSans.ttf", "arial.ttf"];
    $pastas = [
        __DIR__ . "/fonts",
        dirname($autoload) . "/dompdf/dompdf/lib/fonts",
        "C:/Windows/Fonts",
        "/usr/share/fonts/truetype/dejavu",
        "/usr/share/fonts/dejavu",
    ];
    foreach ($pastas as $pasta) {
        foreach ($nomes as $nome) {
            $caminho = $pasta . "/" . $nome;
            if (is_file($caminho)) return $caminho;
        }
    }
    return null;
}
$fonteNormal  = acharFonte(false, $autoload);
$fonteNegrito = acharFonte(true, $autoload) ?? $fonteNormal;

// ---------- imagem ----------
$W = 540;
$H = 860;
$img = imagecreatetruecolor($W, $H);
imagealphablending($img, true);

$branco = imagecolorallocate($img, 255, 255, 255);
$azul   = imagecolorallocate($img, 30, 58, 95);
$preto  = imagecolorallocate($img, 17, 17, 17);
$cinza  = imagecolorallocate($img, 100, 116, 139);
$claro  = imagecolorallocate($img, 226, 232, 240);
$verde  = imagecolorallocate($img, 21, 128, 61);

imagefilledrectangle($img, 0, 0, $W, $H, $branco);

function larguraTexto(?string $fonte, int $tam, string $texto): int
{
    if ($fonte === null) return strlen($texto) * 9;
    $b = imagettfbbox($tam, 0, $fonte, $texto);
    return (int)($b[2] - $b[0]);
}

// Escreve centralizado em $centroX; reduz a fonte e, se preciso, corta o texto para caber
function escreverCentralizado($img, ?string $fonte, int $tam, int $cor, string $texto, int $centroX, int $y, int $larguraMax): void
{
    while ($tam > 11 && larguraTexto($fonte, $tam, $texto) > $larguraMax) {
        $tam--;
    }
    while (mb_strlen($texto) > 4 && larguraTexto($fonte, $tam, $texto) > $larguraMax) {
        $texto = rtrim(mb_substr($texto, 0, mb_strlen($texto) - 2)) . "…";
    }
    $x = (int)($centroX - larguraTexto($fonte, $tam, $texto) / 2);

    if ($fonte !== null) {
        imagettftext($img, $tam, 0, $x, $y, $cor, $fonte, $texto);
    } else {
        // Sem fonte TTF: usa a fonte embutida do GD (sem acentos)
        imagestring($img, 5, max(10, $x), $y - 14, mb_convert_encoding($texto, "ISO-8859-1", "UTF-8"), $cor);
    }
}

// Cabeçalho com o nome da empresa
imagefilledrectangle($img, 0, 0, $W, 140, $azul);
escreverCentralizado($img, $fonteNegrito, 24, $branco, mb_strtoupper($p["NOME_FANTASIA"], "UTF-8"), $W / 2, 85, 480);

// Foto (recorte centralizado) ou espaço reservado
$fx = 40; $fy = 180; $fw = 230; $fh = 290;
$fotoOk = false;
if (!empty($p["FOTO"])) {
    $caminhoFoto = __DIR__ . "/uploads/pessoas/" . basename($p["FOTO"]);
    if (is_file($caminhoFoto)) {
        $foto = @imagecreatefromstring(file_get_contents($caminhoFoto));
        if ($foto) {
            $sw = imagesx($foto);
            $sh = imagesy($foto);
            $escala = max($fw / $sw, $fh / $sh);
            $cw = (int)($fw / $escala);
            $ch = (int)($fh / $escala);
            $sx = (int)(($sw - $cw) / 2);
            $sy = (int)(($sh - $ch) / 2);
            imagecopyresampled($img, $foto, $fx, $fy, $sx, $sy, $fw, $fh, $cw, $ch);
            imagedestroy($foto);
            $fotoOk = true;
        }
    }
}
if (!$fotoOk) {
    imagefilledrectangle($img, $fx, $fy, $fx + $fw, $fy + $fh, $claro);
    escreverCentralizado($img, $fonteNormal, 16, $cinza, "SEM FOTO", $fx + $fw / 2, $fy + $fh / 2, $fw - 20);
}
imagerectangle($img, $fx, $fy, $fx + $fw, $fy + $fh, $cinza);

// QR Code
if (class_exists(\chillerlan\QRCode\Output\QROutputInterface::class)) {
    // Versão 5 da biblioteca
    $opcoes = new QROptions([
        "outputType"  => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
        "eccLevel"    => \chillerlan\QRCode\Common\EccLevel::M,
        "scale"       => 10,
        "imageBase64" => false,
    ]);
} else {
    // Versão 4 da biblioteca
    $opcoes = new QROptions([
        "outputType"  => QRCode::OUTPUT_IMAGE_PNG,
        "eccLevel"    => QRCode::ECC_M,
        "scale"       => 10,
        "imageBase64" => false,
    ]);
}
$saida = (new QRCode($opcoes))->render($codigo);
if (strpos($saida, "data:") === 0) {
    $saida = base64_decode(substr($saida, strpos($saida, ",") + 1));
}
$qr = imagecreatefromstring($saida);
$qx = 300; $qy = 200; $qt = 200;
imagecopyresampled($img, $qr, $qx, $qy, 0, 0, $qt, $qt, imagesx($qr), imagesy($qr));
imagedestroy($qr);
escreverCentralizado($img, $fonteNormal, 13, $cinza, $codigo, $qx + $qt / 2, $qy + $qt + 32, 200);

// Nome, cargo e informações
escreverCentralizado($img, $fonteNegrito, 30, $preto, $p["NOME"], $W / 2, 550, 480);

if (!empty($p["CARGO"])) {
    escreverCentralizado($img, $fonteNormal, 20, $cinza, $p["CARGO"], $W / 2, 600, 480);
}

escreverCentralizado(
    $img, $fonteNormal, 13, $cinza,
    sprintf("Empresa %06d  |  Pessoa %06d", $p["EMPRESA_ID"], $p["ID"]),
    $W / 2, 650, 480
);

if ($p["INGRESSO_PERMANENTE"] === "S") {
    escreverCentralizado($img, $fonteNegrito, 18, $verde, "INGRESSO PERMANENTE", $W / 2, 710, 480);
}

// Rodapé
imagefilledrectangle($img, 0, 780, $W, $H, $azul);
escreverCentralizado($img, $fonteNegrito, 26, $branco, "CREDENCIAL", $W / 2, 830, 480);

// ---------- saída ----------
ob_end_clean();
header("Content-Type: image/png");
if (isset($_GET["baixar"])) {
    header('Content-Disposition: attachment; filename="cracha_' . $codigo . '.png"');
}
imagepng($img);
imagedestroy($img);