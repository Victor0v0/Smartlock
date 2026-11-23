
const dashboardUrl = new URL("backend/api/dashboard.php", document.baseURI);
let accessChart = null;


// ==========================================
// CONVERTER DATA
// ==========================================

function converterData(data) {

    const [ano, mes, dia] = data.split("-").map(Number);

    return new Date(ano, mes - 1, dia);
}


function converterDataHora(valor) {

    const [data, horario = "00:00:00"] = valor.split(" ");
    const [ano, mes, dia] = data.split("-").map(Number);
    const [hora, minuto, segundo = 0] = horario.split(":").map(Number);

    return new Date(ano, mes - 1, dia, hora, minuto, segundo);
}

function formatarDataHora(valor) {

    const partes = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})(?::\d{2})?$/.exec(String(valor));
    if (!partes) return valor;

    const [, ano, mes, dia, hora, minuto] = partes;
    return `${dia}/${mes}/${ano}, ${hora}:${minuto}`;
}

// ==========================================
// EXIBIR REGISTROS DE ACESSO
// ==========================================

function renderAccessList(registros) {

    const container = document.getElementById("access-list");

    // Limpa os registros anteriores.
    container.innerHTML = "";

    if (registros.length === 0) {
        const vazio = document.createElement("p");
        vazio.className = "access-empty";
        vazio.textContent = "Nenhum acesso registrado.";
        container.appendChild(vazio);
        return;
    }

    // Organiza os acessos do mais recente para o mais antigo.
    const registrosOrdenados = [...registros].sort((a, b) =>
        String(b.data_hora).localeCompare(String(a.data_hora))
    );


    // Cria um bloco para cada acesso.
    registrosOrdenados.forEach(registro => {

        const card = document.createElement("div");

        card.className = "access-card";


        // Informações que serão exibidas.
        const informacoes = [

            {
                label: "Nome do usuário",
                value: registro.proprietario || "Proprietário indisponível"
            },

            {
                label: "UID lido",
                value: registro.uid_lido
            },

            {
                label: "Data e horário do acesso",
                value: formatarDataHora(registro.data_hora)
            },

            {
                label: "Resultado",
                value: registro.resultado
            }

        ];


        // Cria cada informação.
        informacoes.forEach(info => {

            const item = document.createElement("div");

            item.className = "information-item";


            const label = document.createElement("span");

            label.className = "information-label";
            label.textContent = info.label;


            const value = document.createElement("span");

            value.className = "information-value";
            value.textContent = info.value;


            item.appendChild(label);
            item.appendChild(value);

            card.appendChild(item);

        });


        container.appendChild(card);

    });

}


// ==========================================
// CRIAR E ATUALIZAR GRÁFICO
// ==========================================

function renderAccessChart(registros) {

    const canvas = document.getElementById("accessChart");
    if (typeof Chart === "undefined") return false;

    const datas = registros.map(item => {
        const [, mes, dia] = item.data.split("-");
        return `${dia}/${mes}`;
    });

    const quantidades = registros.map(item => Number(item.total));


    // Se o gráfico já existir, atualiza os dados.
    if (accessChart) {

        accessChart.data.labels = datas;

        accessChart.data.datasets[0].data = quantidades;

        accessChart.update();

        return true;

    }


    // Criação do gráfico.
    accessChart = new Chart(canvas, {

        type: "bar",

        data: {

            labels: datas,

            datasets: [{

                label: "Tentativas de acesso",

                data: quantidades,

                backgroundColor: "#111111",

                borderColor: "#111111",

                borderWidth: 1,

                borderRadius: 4,

                barPercentage: 0.65,

                categoryPercentage: 0.8

            }]

        },


        options: {

            // Barras verticais.
            indexAxis: "x",

            responsive: true,

            maintainAspectRatio: false,


            plugins: {

                // Oculta a legenda.
                legend: {

                    display: false

                },


                // Informações ao passar o mouse.
                tooltip: {

                    callbacks: {

                        label: function(context) {

                            return context.parsed.y + " acessos";

                        }

                    }

                }

            },


            scales: {

                // EIXO X: DATA DO ACESSO
                x: {

                    ticks: {

                        color: "#111111",

                        autoSkip: true,

                        maxRotation: 45,

                        minRotation: 0

                    },

                    grid: {

                        color: "#dddddd"

                    }

                },


                // EIXO Y: QUANTIDADE DE ACESSOS
                y: {

                    beginAtZero: true,

                    ticks: {

                        stepSize: 1,

                        precision: 0,

                        color: "#111111"

                    },

                    grid: {

                        color: "#dddddd"

                    }

                }

            }

        }

    });

    return true;

}


function atualizarResumo(resumo) {

    const campos = {
        "total-usuarios": resumo.usuarios_total,
        "total-cartoes": resumo.cartoes_total,
        "cartoes-ativos": resumo.cartoes_ativos,
        "cartoes-inativos": resumo.cartoes_inativos,
        "acessos-autorizados": resumo.acessos_autorizados,
        "acessos-negados": resumo.acessos_negados
    };

    Object.entries(campos).forEach(([id, valor]) => {
        document.getElementById(id).textContent = Number(valor).toLocaleString("pt-BR");
    });
}

function mostrarErro(mensagem) {

    const aviso = document.getElementById("dashboard-message");
    aviso.textContent = mensagem;
    aviso.classList.add("is-error");
}

async function carregarDashboard() {

    const aviso = document.getElementById("dashboard-message");
    aviso.textContent = "Carregando dados do sistema...";
    aviso.classList.remove("is-error");

    try {
        const resposta = await fetch(dashboardUrl, {
            headers: { Accept: "application/json" },
            cache: "no-store"
        });
        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.mensagem || "Não foi possível carregar o dashboard.");
        }

        atualizarResumo(dados.resumo);
        renderAccessList(dados.acessos_recentes);
        const graficoDisponivel = renderAccessChart(dados.acessos_por_dia);
        aviso.textContent = graficoDisponivel
            ? ""
            : "Indicadores e acessos carregados; o gráfico está indisponível neste momento.";
    } catch (erro) {
        Object.values({
            usuarios: "total-usuarios",
            cartoes: "total-cartoes",
            ativos: "cartoes-ativos",
            inativos: "cartoes-inativos",
            autorizados: "acessos-autorizados",
            negados: "acessos-negados"
        }).forEach(id => {
            document.getElementById(id).textContent = "--";
        });

        const lista = document.getElementById("access-list");
        lista.replaceChildren();
        const vazio = document.createElement("p");
        vazio.className = "access-empty";
        vazio.textContent = "Os acessos recentes não estão disponíveis.";
        lista.appendChild(vazio);

        mostrarErro("Não foi possível carregar os dados reais do dashboard. Verifique a API e a conexão com o banco.");
        if (accessChart) {
            accessChart.destroy();
            accessChart = null;
        }
    }
}

carregarDashboard();
