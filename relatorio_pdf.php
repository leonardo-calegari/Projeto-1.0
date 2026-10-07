<?php
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Gera e exibe um PDF com uma tabela.
 *
 * @param string $titulo   Título do relatório (ex.: "Empresas")
 * @param array  $colunas  Cabeçalhos (ex.: ["ID", "Nome"])
 * @param array  $linhas   Lista de linhas; cada linha é um array de valores na ordem das colunas
 * @param string $filtro   Texto descrevendo o filtro aplicado (ex.: 'Pesquisa: "faro"')
 * @param string $arquivo  Nome do arquivo PDF
 */
function gerarRelatorioPdf(string $titulo, array $colunas, array $linhas, string $filtro = "", string $arquivo = "relatorio.pdf"): void
{
    $autoload = __DIR__ . "/vendor/autoload.php";
    if (!file_exists($autoload)) {
        http_response_code(500);
        exit("Biblioteca de PDF não instalada. No terminal, dentro da pasta do projeto, rode: composer require dompdf/dompdf");
    }
    require_once $autoload;

    date_default_timezone_set("America/Sao_Paulo");

    $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");

    $html  = '<html><head><meta charset="UTF-8"><style>
        @page { margin: 60px 35px 55px 35px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #1e3a5f; }
        .meta { font-size: 8px; color: #64748b; margin: 1px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        thead { display: table-header-group; }
        th { background: #1e3a5f; color: #fff; text-align: left; padding: 6px; font-size: 9px; }
        td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr.par td { background: #f1f5f9; }
        .vazio { text-align: center; color: #64748b; padding: 14px; }
    </style></head><body>';

    $html .= '<h1>' . $e($titulo) . '</h1>';
    $html .= '<div class="meta">Sistema de Credenciamento &mdash; gerado em ' . date("d/m/Y H:i") . '</div>';
    $html .= '<div class="meta">' . ($filtro !== "" ? "Filtro: " . $e($filtro) : "Sem filtro") . '</div>';
    $html .= '<div class="meta"><strong>' . count($linhas) . ' registro' . (count($linhas) == 1 ? "" : "s") . '</strong></div>';

    $html .= '<table><thead><tr>';
    foreach ($colunas as $c) {
        $html .= '<th>' . $e($c) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    if (count($linhas) === 0) {
        $html .= '<tr><td class="vazio" colspan="' . count($colunas) . '">Nenhum registro encontrado</td></tr>';
    } else {
        foreach ($linhas as $i => $linha) {
            $html .= '<tr class="' . ($i % 2 ? "par" : "") . '">';
            foreach ($linha as $valor) {
                $html .= '<td>' . $e($valor) . '</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table></body></html>';

    $opcoes = new Options();
    $opcoes->set("defaultFont", "DejaVu Sans");
    $opcoes->set("isRemoteEnabled", false);

    $dompdf = new Dompdf($opcoes);
    $dompdf->loadHtml($html, "UTF-8");
    $dompdf->setPaper("A4", count($colunas) > 6 ? "landscape" : "portrait");
    $dompdf->render();

    // Numeração de páginas no rodapé
    $canvas  = $dompdf->getCanvas();
    $metrica = $dompdf->getFontMetrics();
    $fonte   = method_exists($metrica, "getFont") ? $metrica->getFont("DejaVu Sans", "normal") : $metrica->get_font("DejaVu Sans", "normal");
    $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 30, "Página {PAGE_NUM} de {PAGE_COUNT}", $fonte, 8, [0.4, 0.4, 0.4]);

    // Abre no navegador (não força download)
    $dompdf->stream($arquivo, ["Attachment" => false]);
    exit;
}
