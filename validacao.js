// Validação de CPF e CNPJ ao sair do campo (blur).
// Mostra mensagem em vermelho abaixo do campo; não bloqueia o envio do formulário.
// Campos: <input id="cpf"> e <input id="cnpj">
(function () {
    function cpfValido(valor) {
        var c = valor.replace(/\D/g, "");
        if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;

        for (var t = 9; t < 11; t++) {
            var soma = 0;
            for (var i = 0; i < t; i++) {
                soma += parseInt(c.charAt(i), 10) * (t + 1 - i);
            }
            var digito = ((soma * 10) % 11) % 10;
            if (digito !== parseInt(c.charAt(t), 10)) return false;
        }
        return true;
    }

    // Aceita CNPJ numérico e o CNPJ alfanumérico (letras A-Z nas 12 primeiras posições)
    function cnpjValido(valor) {
        var c = valor.toUpperCase().replace(/[^0-9A-Z]/g, "");
        if (!/^[0-9A-Z]{12}[0-9]{2}$/.test(c)) return false;
        if (/^(.)\1{13}$/.test(c)) return false;

        function digitoVerificador(base) {
            var pesos = base.length === 12
                ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            var soma = 0;
            for (var i = 0; i < base.length; i++) {
                soma += (base.charCodeAt(i) - 48) * pesos[i];
            }
            var resto = soma % 11;
            return resto < 2 ? 0 : 11 - resto;
        }

        var d1 = digitoVerificador(c.substring(0, 12));
        var d2 = digitoVerificador(c.substring(0, 12) + d1);
        return c.substring(12) === "" + d1 + d2;
    }

    function elementoErro(campo) {
        var id = campo.id + "-erro";
        var el = document.getElementById(id);
        if (!el) {
            el = document.createElement("small");
            el.id = id;
            el.style.cssText = "display:block;color:#b91c1c;margin-top:4px;font-size:13px;";
            campo.insertAdjacentElement("afterend", el);
        }
        return el;
    }

    function ligar(campo, validador, mensagem) {
        if (!campo) return;

        campo.addEventListener("blur", function () {
            var erro = elementoErro(campo);
            if (campo.value.trim() === "" || validador(campo.value)) {
                erro.textContent = "";
                campo.style.borderColor = "";
            } else {
                erro.textContent = mensagem;
                campo.style.borderColor = "#b91c1c";
            }
        });

        campo.addEventListener("input", function () {
            elementoErro(campo).textContent = "";
            campo.style.borderColor = "";
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        ligar(document.getElementById("cpf"),  cpfValido,  "CPF inválido.");
        ligar(document.getElementById("cnpj"), cnpjValido, "CNPJ inválido.");
    });
})();
