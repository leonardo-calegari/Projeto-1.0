<?php
// Paginação das listagens: 10 registros por página.
//
// Uso em cada tela:
//   include("paginacao.php");                          (junto dos outros include)
//   <?php $pg = prepararPaginacao($result); ?>         (depois do resultado da consulta, antes da tabela)
//   <?php for ($i = 0; $i < $pg["limite"] && ($row = $result->fetch_assoc()); $i++) { ?>
//   <?php exibirPaginacao($pg); ?>                     (logo depois do </table>)

const REGISTROS_POR_PAGINA = 10;

function prepararPaginacao($result): array
{
    $total   = $result ? (int)$result->num_rows : 0;
    $paginas = max(1, (int)ceil($total / REGISTROS_POR_PAGINA));

    $pagina = (isset($_GET["pagina"]) && is_numeric($_GET["pagina"])) ? (int)$_GET["pagina"] : 1;
    $pagina = max(1, min($pagina, $paginas));
    $offset = ($pagina - 1) * REGISTROS_POR_PAGINA;

    // Pula direto para o primeiro registro da página
    if ($result && $offset > 0 && $offset < $total) {
        $result->data_seek($offset);
    }

    return [
        "total"   => $total,
        "pagina"  => $pagina,
        "paginas" => $paginas,
        "limite"  => REGISTROS_POR_PAGINA,
    ];
}

function exibirPaginacao(array $pg): void
{
    if ($pg["paginas"] <= 1) {
        return;
    }

    static $cssEmitido = false;
    if (!$cssEmitido) {
        echo '<style>
        .paginacao { display:flex; gap:4px; margin:20px 0; align-items:center; flex-wrap:wrap; }
        .paginacao a, .paginacao span {
            min-width:38px; padding:8px 12px; text-align:center; border:1px solid #cbd5e1;
            border-radius:6px; text-decoration:none; color:#2563eb; background:#fff; font-size:14px;
        }
        .paginacao a:hover { background:#f1f5f9; }
        .paginacao .ativa { background:#2563eb; color:#fff; border-color:#2563eb; font-weight:600; }
        .paginacao .desab { color:#94a3b8; background:#f8fafc; }
        .paginacao-info { font-size:13px; color:#64748b; margin-top:-8px; }
        </style>';
        $cssEmitido = true;
    }

    // Mantém pesquisa e filtros ao trocar de página
    $url = function (int $p): string {
        $q = $_GET;
        $q["pagina"] = $p;
        return htmlspecialchars("?" . http_build_query($q));
    };

    $atual  = $pg["pagina"];
    $ultima = $pg["paginas"];
    $ini    = max(1, $atual - 2);
    $fim    = min($ultima, $atual + 2);

    echo '<nav class="paginacao">';

    echo $atual > 1
        ? '<a href="' . $url($atual - 1) . '">&laquo;</a>'
        : '<span class="desab">&laquo;</span>';

    if ($ini > 1) {
        echo '<a href="' . $url(1) . '">1</a>';
        if ($ini > 2) echo '<span class="desab">&hellip;</span>';
    }

    for ($i = $ini; $i <= $fim; $i++) {
        echo $i == $atual
            ? '<span class="ativa">' . $i . '</span>'
            : '<a href="' . $url($i) . '">' . $i . '</a>';
    }

    if ($fim < $ultima) {
        if ($fim < $ultima - 1) echo '<span class="desab">&hellip;</span>';
        echo '<a href="' . $url($ultima) . '">' . $ultima . '</a>';
    }

    echo $atual < $ultima
        ? '<a href="' . $url($atual + 1) . '">&raquo;</a>'
        : '<span class="desab">&raquo;</span>';

    echo '</nav>';
    echo '<div class="paginacao-info">Página ' . $atual . ' de ' . $ultima . ' &mdash; ' . REGISTROS_POR_PAGINA . ' registros por página</div>';
}