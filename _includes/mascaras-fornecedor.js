(() => {
    const quantidadeAntesDoCursor = (texto, limite, padrao) =>
        [...texto.slice(0, limite)].filter((caractere) => padrao.test(caractere)).length;

    const posicaoDoCursor = (texto, quantidade, padrao) => {
        if (quantidade === 0) return 0;

        let encontrados = 0;
        for (let indice = 0; indice < texto.length; indice++) {
            if (padrao.test(texto[indice]) && ++encontrados === quantidade) {
                return indice + 1;
            }
        }

        return texto.length;
    };

    const formatarCNPJ = (valor) => {
        const caracteres = valor.toUpperCase().replace(/[^A-Z0-9]/g, '');
        const base = caracteres.slice(0, 12);
        const digitosVerificadores = caracteres.slice(12).replace(/\D/g, '').slice(0, 2);
        const cnpj = base + digitosVerificadores;

        let formatado = cnpj.slice(0, 2);
        if (cnpj.length > 2) formatado += `.${cnpj.slice(2, 5)}`;
        if (cnpj.length > 5) formatado += `.${cnpj.slice(5, 8)}`;
        if (cnpj.length > 8) formatado += `/${cnpj.slice(8, 12)}`;
        if (cnpj.length > 12) formatado += `-${cnpj.slice(12, 14)}`;

        return formatado;
    };

    const formatarTelefone = (valor) => {
        const digitos = valor.replace(/\D/g, '').slice(0, 11);
        if (digitos.length === 0) return '';
        if (digitos.length <= 2) return `(${digitos}`;

        const ddd = digitos.slice(0, 2);
        const numero = digitos.slice(2);
        const celular = numero.startsWith('9');
        const limite = celular ? 9 : 8;
        const separador = celular ? 5 : 4;
        const parteLocal = numero.slice(0, limite);
        const prefixo = `(${ddd}) `;

        if (parteLocal.length <= separador) return prefixo + parteLocal;
        return `${prefixo}${parteLocal.slice(0, separador)}-${parteLocal.slice(separador)}`;
    };

    const aplicarMascara = (campo, formatar, padrao) => {
        const cursor = campo.selectionStart ?? campo.value.length;
        const quantidade = quantidadeAntesDoCursor(campo.value, cursor, padrao);
        campo.value = formatar(campo.value);
        const novoCursor = posicaoDoCursor(campo.value, quantidade, padrao);
        campo.setSelectionRange(novoCursor, novoCursor);
    };

    document.addEventListener('input', (evento) => {
        const campo = evento.target;
        if (!(campo instanceof HTMLInputElement)) return;

        if (campo.dataset.mascara === 'cnpj') {
            aplicarMascara(campo, formatarCNPJ, /[A-Z0-9]/i);
        } else if (campo.dataset.mascara === 'telefone') {
            aplicarMascara(campo, formatarTelefone, /\d/);
        }
    });
})();
