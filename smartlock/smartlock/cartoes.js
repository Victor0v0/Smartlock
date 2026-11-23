const apiUrl = new URL('backend/api/cartoes.php', document.baseURI);
let cartoes = [];
let editando = null;
let excluindo = null;
let mensagem = '';
let operando = false;

const lista = document.getElementById('lista');
const contador = document.getElementById('contador');
const overlay = document.getElementById('overlay');

function escapar(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, caractere => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[caractere]));
}

function normalizarUID(valor) {
  return valor.trim().toUpperCase().replace(/[\s:-]/g, '');
}

async function requisitar(metodo, dados) {
  const opcoes = {
    method: metodo,
    headers: { Accept: 'application/json' }
  };

  if (dados !== undefined) {
    opcoes.headers['Content-Type'] = 'application/json';
    opcoes.body = JSON.stringify(dados);
  }

  let resposta;
  try {
    resposta = await fetch(apiUrl, opcoes);
  } catch {
    throw new Error('Não foi possível conectar à API de cartões.');
  }

  let resultado;
  try {
    resultado = await resposta.json();
  } catch {
    throw new Error('A API retornou uma resposta inválida.');
  }

  if (!resposta.ok || !resultado.sucesso) {
    throw new Error(resultado.mensagem || 'Não foi possível concluir a operação.');
  }

  return resultado;
}

function render() {
  contador.textContent = mensagem || `${cartoes.length} ${cartoes.length === 1 ? 'cartão registrado' : 'cartões registrados'}`;

  if (!cartoes.length) {
    lista.innerHTML = mensagem
      ? `<div class="empty" role="alert">${escapar(mensagem)} <button type="button" data-acao="recarregar">Tentar novamente</button></div>`
      : '<div class="empty">Nenhum cartão registrado.</div>';
    return;
  }

  lista.innerHTML = `${mensagem ? `<div class="empty" role="alert">${escapar(mensagem)}</div>` : ''}${cartoes.map(cartao => {
    const emEdicao = Number(editando) === Number(cartao.id);
    const ativo = Number(cartao.ativo) === 1;
    const podeAcessar = Number(cartao.pode_acessar) === 1;
    return `
    <div class="item" data-id="${Number(cartao.id)}">
      <div class="info">
        <div class="field">
          <small>Proprietário</small>
          ${emEdicao
            ? `<input type="text" data-campo="nome" value="${escapar(cartao.proprietario)}" maxlength="100" aria-label="Nome do proprietário">`
            : `<span>${escapar(cartao.proprietario)}</span>`}
        </div>
        <div class="field">
          <small>UID do cartão</small>
          ${emEdicao
            ? `<input type="text" data-campo="uid" value="${escapar(cartao.uid)}" maxlength="64" autocomplete="off" aria-label="UID do cartão">`
            : `<span>${escapar(cartao.uid)}</span>`}
        </div>
        <div class="field">
          <small>Status do cartão</small>
          <span>${ativo ? 'Ativo' : 'Inativo'}</span>
        </div>
        <div class="field">
          <small>Permitir acesso</small>
          <span>${podeAcessar ? 'Permitido' : 'Não permitido'}</span>
        </div>
      </div>
      <div class="actions">
        ${emEdicao
          ? `<button type="button" class="primary" data-acao="salvar">Salvar</button>
             <button type="button" data-acao="cancelar-edicao">Cancelar</button>`
          : `<button type="button" data-acao="editar">Editar</button>
             <button type="button" data-acao="alternar" class="${ativo ? 'danger' : 'primary'}">${ativo ? 'Desativar' : 'Ativar'}</button>
             <button type="button" data-acao="alternar-acesso" class="${podeAcessar ? 'danger' : 'primary'}">${podeAcessar ? 'Bloquear acesso' : 'Permitir acesso'}</button>
             <button type="button" class="danger" data-acao="excluir">Excluir</button>`}
      </div>
    </div>`;
  }).join('')}`;

  const input = lista.querySelector('[data-campo="nome"]');
  if (input) {
    input.focus();
    input.addEventListener('keydown', evento => {
      if (evento.key === 'Enter') salvarEdicao();
      if (evento.key === 'Escape') {
        editando = null;
        mensagem = '';
        render();
      }
    });
  }
}

async function carregarCartoes() {
  mensagem = '';
  contador.textContent = 'Carregando cartões...';
  lista.innerHTML = '';

  try {
    const resultado = await requisitar('GET');
    cartoes = resultado.cartoes;
    render();
  } catch (erro) {
    cartoes = [];
    mensagem = erro.message;
    render();
  }
}

async function salvarEdicao() {
  const item = lista.querySelector(`.item[data-id="${Number(editando)}"]`);
  const nome = item.querySelector('[data-campo="nome"]').value.trim();
  const uid = normalizarUID(item.querySelector('[data-campo="uid"]').value);

  if (!nome) {
    mensagem = 'Informe o nome do proprietário.';
    contador.textContent = mensagem;
    return;
  }

  if (!/^(?:[0-9A-F]{8}|[0-9A-F]{14}|[0-9A-F]{20})$/.test(uid)) {
    mensagem = 'UID inválido. Informe 4, 7 ou 10 bytes em hexadecimal.';
    contador.textContent = mensagem;
    return;
  }

  if (operando) return;
  operando = true;
  try {
    await requisitar('PUT', { id: Number(editando), nome, uid });
    editando = null;
    await carregarCartoes();
  } catch (erro) {
    mensagem = erro.message;
    contador.textContent = mensagem;
  } finally {
    operando = false;
  }
}

lista.addEventListener('click', async evento => {
  const botao = evento.target.closest('button[data-acao]');
  if (!botao) return;

  if (botao.dataset.acao === 'recarregar') {
    await carregarCartoes();
    return;
  }

  const item = botao.closest('.item');
  if (!item) return;

  const id = Number(item.dataset.id);
  const acao = botao.dataset.acao;

  if (acao === 'editar') {
    editando = id;
    mensagem = '';
    render();
  } else if (acao === 'cancelar-edicao') {
    editando = null;
    mensagem = '';
    render();
  } else if (acao === 'salvar') {
    await salvarEdicao();
  } else if (acao === 'alternar' || acao === 'alternar-acesso') {
    if (operando) return;
    operando = true;
    try {
      const cartao = cartoes.find(registro => Number(registro.id) === id);
      const campo = acao === 'alternar' ? 'ativo' : 'pode_acessar';
      const valor = Number(cartao[campo]) === 1 ? 0 : 1;
      await requisitar('PATCH', { id, [campo]: valor });
      await carregarCartoes();
    } catch (erro) {
      mensagem = erro.message;
      contador.textContent = mensagem;
    } finally {
      operando = false;
    }
  } else if (acao === 'excluir') {
    excluindo = id;
    const cartao = cartoes.find(registro => Number(registro.id) === id);
    document.getElementById('modalTexto').textContent =
      `O cartão ${cartao.uid}, de ${cartao.proprietario}, será excluído. Os registros de acesso serão preservados.`;
    overlay.classList.add('open');
  }
});

document.getElementById('cancelar').addEventListener('click', () => {
  excluindo = null;
  overlay.classList.remove('open');
});

document.getElementById('confirmar').addEventListener('click', async () => {
  if (excluindo === null || operando) return;

  operando = true;
  const botao = document.getElementById('confirmar');
  botao.disabled = true;
  try {
    await requisitar('DELETE', { id: Number(excluindo) });
    excluindo = null;
    overlay.classList.remove('open');
    await carregarCartoes();
  } catch (erro) {
    mensagem = erro.message;
    contador.textContent = mensagem;
  } finally {
    botao.disabled = false;
    operando = false;
  }
});

overlay.addEventListener('click', e => {
  if (e.target === overlay) {
    excluindo = null;
    overlay.classList.remove('open');
  }
});

carregarCartoes();
