const form = document.getElementById('registration-form');
const message = document.getElementById('form-message');
const submitButton = form.querySelector('button[type="submit"]');
const apiUrl = new URL('backend/api/cadastrar_cartao.php', document.baseURI);

function normalizarUID(valor) {
    return valor.trim().toUpperCase().replace(/[\s:-]/g, '');
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const nome = document.getElementById('nome').value.trim();
    const uid = normalizarUID(document.getElementById('rfid').value);

    if (!nome || !uid) {
        message.textContent = 'Preencha o nome e o UID do cartão.';
        message.dataset.tipo = 'erro';
        return;
    }

    if (!/^(?:[0-9A-F]{8}|[0-9A-F]{14}|[0-9A-F]{20})$/.test(uid)) {
        message.textContent = 'UID inválido. Informe 4, 7 ou 10 bytes em hexadecimal.';
        message.dataset.tipo = 'erro';
        return;
    }

    submitButton.disabled = true;
    submitButton.textContent = 'Cadastrando...';
    message.textContent = '';

    try {
        const resposta = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, uid })
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.mensagem || 'Não foi possível realizar o cadastro.');
        }

        message.textContent = dados.mensagem;
        message.dataset.tipo = 'sucesso';
        form.reset();
    } catch (erro) {
        message.textContent = erro.message.includes('Failed to fetch')
            ? 'Não foi possível conectar ao servidor. Verifique o Apache e o caminho da API.'
            : erro.message;
        message.dataset.tipo = 'erro';
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Cadastrar';
    }
});
