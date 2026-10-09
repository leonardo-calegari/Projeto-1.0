// Filtro automático: filtra as linhas da tabela enquanto o usuário digita na pesquisa.
// Ignora maiúsculas, minúsculas e acentos. Não considera a coluna de Ações.
(function () {
    function normalizar(texto) {
        return (texto || "")
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    // Texto da linha, só das células de dados (ignora células com links ou botões)
    function textoDaLinha(linha) {
        var partes = [];
        var celulas = linha.querySelectorAll("td");
        for (var i = 0; i < celulas.length; i++) {
            if (celulas[i].querySelector("a, button")) continue;
            partes.push(celulas[i].textContent);
        }
        return normalizar(partes.join(" "));
    }

    document.addEventListener("DOMContentLoaded", function () {
        var campo  = document.querySelector("input.input-pesquisa");
        var tabela = document.querySelector("table");
        if (!campo || !tabela) return;

        function filtrar() {
            var termo  = normalizar(campo.value.trim());
            var linhas = tabela.querySelectorAll("tr");

            for (var i = 1; i < linhas.length; i++) { // 0 = cabeçalho
                var linha = linhas[i];
                if (linha.querySelector("td[colspan]")) continue; // linha "Nenhum registro"
                linha.style.display = (termo === "" || textoDaLinha(linha).indexOf(termo) > -1) ? "" : "none";
            }
        }

        campo.addEventListener("input", filtrar);
        filtrar();
    });
})();
