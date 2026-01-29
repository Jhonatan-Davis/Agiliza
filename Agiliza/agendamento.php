<?php
session_start();
require 'conexao.php';

// --- 1. O "Porteiro" (Auth-Guard) ---
if (!isset($_SESSION['usuario_id'])) {
    // Pega a URL atual para que o login possa redirecionar de volta
    $redirect_url = 'agendamento.php?' . $_SERVER['QUERY_STRING'];
    header("Location: login/login.php?redirect=" . urlencode($redirect_url));
    exit();
}

// --- 2. Validar o Negócio ---
if (!isset($_GET['id_negocio'])) {
    die("Erro: ID do negócio não fornecido.");
}
$id_negocio = (int)$_GET['id_negocio'];

// Define a data de "hoje"
$data_hoje_formatada = date('Y-m-d');

try {
    // Busca dados do negócio (para o título)
    $stmt_negocio = $pdo->prepare("SELECT nome_negocio FROM negocios WHERE id = :id");
    $stmt_negocio->execute(['id' => $id_negocio]);
    $negocio = $stmt_negocio->fetch(PDO::FETCH_ASSOC);

    if (!$negocio) {
        die("Negócio não encontrado.");
    }

    // Busca dados do usuário (para o header)
    $stmt_user = $pdo->prepare("SELECT nome, foto_perfil_url FROM usuarios WHERE id = :id");
    $stmt_user->execute(['id' => $_SESSION['usuario_id']]);
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - Agiliza</title>
    <link rel="stylesheet" href="style_site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script> </head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">Agili<span>za</span></a>
        <nav>
            <div class="user-dropdown">
                <button class="dropdown-btn">
                    <?php if (!empty($usuario['foto_perfil_url'])): ?>
                        <img src="<?php echo htmlspecialchars($usuario['foto_perfil_url']); ?>" alt="Perfil" class="header-perfil-foto">
                    <?php endif; ?>
                    Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-content">
                    <a href="meu_perfil.php">Meu Perfil</a>
                    <a href="meus_agendamentos.php">Meus Agendamentos</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-link">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="site-container">
        <h1>Agendar Horário</h1>
        <p class="wizard-subtitulo">Para: <strong><?php echo htmlspecialchars($negocio['nome_negocio']); ?></strong></p>

        <div class="wizard-stepper">
            <div class="step active" id="stepper-1">
                <div class="step-icon">1</div>
                <span>Serviço</span>
            </div>
            <div class="step" id="stepper-2">
                <div class="step-icon">2</div>
                <span>Profissional</span>
            </div>
            <div class="step" id="stepper-3">
                <div class="step-icon">3</div>
                <span>Horário</span>
            </div>
            <div class="step" id="stepper-4">
                <div class="step-icon">4</div>
                <span>Confirmar</span>
            </div>
        </div>

        <div class="wizard-content">

            <div id="passo-1-servico" class="wizard-step active-step">
                <h2>Qual serviço você gostaria de agendar?</h2>
                <div id="lista-servicos" class="card-grid">
                    <p>Carregando serviços...</p>
                </div>
            </div>

            <div id="passo-2-profissional" class="wizard-step">
                <button class="wizard-back-btn" data-target="passo-1-servico">&larr; Voltar (Escolher outro serviço)</button>
                <h2>Com qual profissional?</h2>
                <div id="lista-profissionais" class="card-grid professional-grid">
                    </div>
            </div>
            
            <div id="passo-3-horario" class="wizard-step">
                <button class="wizard-back-btn" data-target="passo-2-profissional">&larr; Voltar (Escolher outro profissional)</button>
                <h2>Quando?</h2>
                
                <div class="form-group" style="margin-top: 1.5rem; max-width: 300px;">
                    <label for="data_agendamento">Escolha o dia:</label>
                    <input type="text" id="data_agendamento" name="data_agendamento" class="search-input" placeholder="Selecione uma data...">
                </div>
                
                <div class="horarios-container" id="horarios-container" style="display: none;">
                    <h3 id="horarios-titulo">Horários disponíveis para...</h3>
                    
                    <div class="horarios-grid" id="horarios-grid">
                        </div>

                    <div id="area-lista-espera" class="waiting-list-card" style="display: none;">
                        <h4>
                            <i class="fas fa-clock"></i> Não encontrou um horário?
                        </h4>
                        <p>
                            Entre na lista de espera para o dia <strong><span id="data-lista-espera"></span></strong>. 
                            Se alguém cancelar, te avisaremos por e-mail imediatamente!
                        </p>
                        <button id="btn-entrar-lista" type="button">
                            <i class="fas fa-bell"></i> Avise-me se vagar um horário
                        </button>
                    </div>

                </div>
            </div>

            <div id="passo-4-confirmacao" class="wizard-step">
                <button class="wizard-back-btn" data-target="passo-3-horario">&larr; Voltar (Escolher outro horário)</button>
                <h2>Confirme seu Agendamento</h2>
                
                <div class="resumo-card" id="resumo-agendamento">
                    <p>Carregando resumo...</p>
                </div>

                <button class="form-button" id="btn-confirmar-agendamento" style="width: 100%; margin-top: 1rem; padding: 1rem; font-size: 1.2rem;">
                    Confirmar e Agendar
                </button>
            </div>

        </div> </main>

<script>
    // --- [NOVO] Lógica da Lista de Espera ---
    const boxLista = document.getElementById('box-lista-espera');
    const btnLista = document.getElementById('btn-entrar-lista');

    // 1. Função para entrar na lista
    btnLista.addEventListener('click', async () => {
        const data = inputData.value; // Pega a data do calendário
        const idFunc = agendamentoSelecionado.profissional.id;
        
        if (!data || !idFunc) return;

        btnLista.disabled = true;
        btnLista.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';

        try {
            const response = await fetch('processar_lista_espera.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id_negocio=${id_negocio}&id_funcionario=${idFunc}&data=${data}`
            });
            const res = await response.json();

            if (res.sucesso) {
                alert('Pronto! Você será avisado por e-mail se um horário vagar neste dia.');
                btnLista.innerHTML = '<i class="fas fa-check"></i> Você está na lista!';
                btnLista.style.backgroundColor = '#4CAF50';
                btnLista.style.borderColor = '#4CAF50';
            } else {
                alert('Erro: ' + res.erro);
                btnLista.disabled = false;
                btnLista.innerHTML = '<i class="fas fa-bell"></i> Tentar Novamente';
            }
        } catch (error) {
            console.error(error);
            alert('Erro de conexão.');
        }
    });

    // 2. Atualizar a função carregarHorarios para MOSTRAR o botão
    // (Você precisa encontrar a função carregarHorarios existente e adicionar isso no final dela)
    // --- 1. Elementos Globais ---
    const id_negocio = <?php echo $id_negocio; ?>;
    let agendamentoSelecionado = {}; 

    const passos = {
        'passo-1-servico': document.getElementById('passo-1-servico'),
        'passo-2-profissional': document.getElementById('passo-2-profissional'),
        'passo-3-horario': document.getElementById('passo-3-horario'),
        'passo-4-confirmacao': document.getElementById('passo-4-confirmacao')
    };
    const steppers = {
        'passo-1-servico': document.getElementById('stepper-1'),
        'passo-2-profissional': document.getElementById('stepper-2'),
        'passo-3-horario': document.getElementById('stepper-3'),
        'passo-4-confirmacao': document.getElementById('stepper-4')
    };
    
    const listaServicosContainer = document.getElementById('lista-servicos');
    const listaProfissionaisContainer = document.getElementById('lista-profissionais');
    const horariosContainer = document.getElementById('horarios-container');
    const horariosGrid = document.getElementById('horarios-grid');
    const inputData = document.getElementById('data_agendamento');
    const resumoContainer = document.getElementById('resumo-agendamento');
    const btnConfirmar = document.getElementById('btn-confirmar-agendamento');
    const areaListaEspera = document.getElementById('area-lista-espera');
    const btnListaEspera = document.getElementById('btn-entrar-lista');

    
    // --- 2. Função de Navegação ---
    function irParaPasso(idPasso) {
        Object.values(passos).forEach(passo => passo.classList.remove('active-step'));
        Object.values(steppers).forEach(stepper => stepper.classList.remove('active'));
        
        let passoAlvoEncontrado = false;
        Object.keys(steppers).forEach(key => {
            if (!passoAlvoEncontrado) {
                steppers[key].classList.add('active');
            }
            if (key === idPasso) {
                passoAlvoEncontrado = true;
            }
        });

        passos[idPasso].classList.add('active-step');
        window.scrollTo(0, 0);
        
        // Se for o Passo 3, carrega os horários da data selecionada
        if (idPasso === 'passo-3-horario') {
            const dataInicial = fp.selectedDates.length > 0 ? fp.formatDate(fp.selectedDates[0], "Y-m-d") : fp.formatDate(new Date(), "Y-m-d");
            carregarHorarios(dataInicial);
        }
    }
    
    // --- 3. Lógica dos Botões "Voltar" ---
    document.querySelectorAll('.wizard-back-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const alvo = e.target.dataset.target;
            irParaPasso(alvo);
        });
    });

    // --- 4. PASSO 1: Carregar Serviços ---
    async function carregarServicos() {
        listaServicosContainer.innerHTML = '<p>Carregando serviços...</p>';
        try {
            const response = await fetch(`buscar_dados_agendamento.php?action=servicos&id_negocio=${id_negocio}`);
            const servicos = await response.json();
            listaServicosContainer.innerHTML = '';
            if (servicos.erro || servicos.length === 0) {
                listaServicosContainer.innerHTML = '<p>Nenhum serviço disponível para agendamento online.</p>';
                return;
            }
            servicos.forEach(servico => {
                const card = document.createElement('div');
                card.className = 'wizard-card-item servico-card';
                card.innerHTML = `<div class="card-content"><strong>${servico.nome_servico}</strong><span>${servico.duracao_minutos} min</span></div><div class="card-price">R$ ${parseFloat(servico.preco).toFixed(2).replace('.', ',')}</div>`;
                card.addEventListener('click', () => {
                    agendamentoSelecionado.servico = servico;
                    irParaPasso('passo-2-profissional');
                    carregarProfissionais();
                });
                listaServicosContainer.appendChild(card);
            });
        } catch (error) {
            console.error('Erro ao buscar serviços:', error);
            listaServicosContainer.innerHTML = '<p>Erro ao carregar serviços.</p>';
        }
    }
    
    // --- 5. PASSO 2: Carregar Profissionais ---
    async function carregarProfissionais() {
        listaProfissionaisContainer.innerHTML = '<p>Carregando profissionais...</p>';
        try {
            const response = await fetch(`buscar_dados_agendamento.php?action=profissionais&id_negocio=${id_negocio}`);
            const profissionais = await response.json();
            listaProfissionaisContainer.innerHTML = '';
            if (profissionais.erro) {
                listaProfissionaisContainer.innerHTML = `<p style="color:red;">${profissionais.erro}</p>`;
                return;
            }
            if (profissionais.length > 1) {
                const itemQualquer = document.createElement('div');
                itemQualquer.className = 'wizard-card-item professional-card';
                itemQualquer.dataset.id_prof = 0;
                itemQualquer.innerHTML = `<img src="uploads/default_qualquer.png" alt="Qualquer"><div class="card-content"><strong>Qualquer um</strong></div>`;
                itemQualquer.addEventListener('click', () => {
                    agendamentoSelecionado.profissional = { id: 0, nome: 'Qualquer' };
                    irParaPasso('passo-3-horario');
                });
                listaProfissionaisContainer.appendChild(itemQualquer);
            }
            profissionais.forEach(prof => {
                const item = document.createElement('div');
                item.className = 'wizard-card-item professional-card';
                item.dataset.id_prof = prof.id;
                const imgUrl = prof.foto_perfil_url ? prof.foto_perfil_url : 'uploads/default_perfil.png';
                let portfolioHtml = '';
                if (prof.portfolio && prof.portfolio.length > 0) {
                    portfolioHtml = `<div class="professional-portfolio-popup"><h5>Portfólio de ${prof.nome.split(' ')[0]}</h5><div class="portfolio-grid">${prof.portfolio.map(url => `<img src="${url}" alt="Corte">`).join('')}</div></div>`;
                }
                item.innerHTML = `<img src="${imgUrl}" alt="${prof.nome}"><div class="card-content"><strong>${prof.nome.split(' ')[0]}</span></div>${portfolioHtml}`;
                item.addEventListener('click', () => {
                    agendamentoSelecionado.profissional = prof;
                    irParaPasso('passo-3-horario');
                });
                listaProfissionaisContainer.appendChild(item);
            });
        } catch (error) {
            console.error('Erro ao buscar profissionais:', error);
            listaProfissionaisContainer.innerHTML = '<p>Erro ao carregar profissionais.</p>';
        }
    }
    
   // --- 6. PASSO 3: Carregar Horários (ATUALIZADA COM LISTA DE ESPERA) ---
    async function carregarHorarios(dataSelecionada) {
        horariosContainer.style.display = 'block';
        horariosGrid.innerHTML = '<p>Carregando horários...</p>';
        areaListaEspera.style.display = 'none'; // Esconde a lista enquanto carrega

        const servicoId = agendamentoSelecionado.servico.id;
        const funcId = agendamentoSelecionado.profissional.id;
        
        // Atualiza o título
        const dataFormatada = new Date(dataSelecionada + 'T00:00:00');
        document.getElementById('horarios-titulo').textContent = `Horários disponíveis para ${dataFormatada.toLocaleDateString('pt-BR')}:`;

        try {
            const response = await fetch(`buscar_dados_agendamento.php?action=horarios&id_negocio=${id_negocio}&id_funcionario=${funcId}&id_servico=${servicoId}&data=${dataSelecionada}`);
            const horarios = await response.json();
            horariosGrid.innerHTML = ''; 

            // Se tiver erro (ex: Feriado), mostra o erro e NÃO mostra a lista de espera
            if (horarios.erro) {
                horariosGrid.innerHTML = `<p style="color: red; font-weight: bold;">${horarios.erro}</p>`;
                return;
            }

            // Se não tiver horários (array vazio), mostra aviso
            if (horarios.length === 0) {
                horariosGrid.innerHTML = '<p>Nenhum horário disponível para esta data.</p>';
            } else {
                // Se tiver horários, cria os botões
                horarios.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = 'time-slot-btn';
                    btn.textContent = slot;
                    btn.addEventListener('click', () => selecionarHorario(slot, dataSelecionada));
                    horariosGrid.appendChild(btn);
                });
            }

            // [NOVO] SEMPRE mostra a opção de lista de espera se não for feriado
            // (O cliente pode querer entrar na lista mesmo se houver horários ruins)
            areaListaEspera.style.display = 'block';
            
            // Reseta o botão para o estado original
            btnListaEspera.disabled = false;
            btnListaEspera.innerHTML = '<i class="fas fa-bell"></i> Avise-me se vagar';
            btnListaEspera.style.backgroundColor = '#e0a800';

        } catch (error) {
            console.error('Erro:', error);
            horariosGrid.innerHTML = '<p>Erro ao carregar horários.</p>';
        }
    }
    // --- [NOVO] Lógica do Botão "Entrar na Lista" ---
    btnListaEspera.addEventListener('click', async () => {
        const data = inputData.value || new Date().toISOString().split('T')[0];
        const funcId = agendamentoSelecionado.profissional.id;
        
        if(funcId == 0) {
             alert("Por favor, selecione um profissional específico para entrar na lista de espera.");
             return;
        }

        btnListaEspera.disabled = true;
        btnListaEspera.innerHTML = 'Processando...';

        try {
            const response = await fetch('processar_lista_espera.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_negocio=${id_negocio}&id_funcionario=${funcId}&data=${data}`
            });
            const res = await response.json();

            if (res.sucesso) {
                btnListaEspera.innerHTML = '<i class="fas fa-check"></i> Você está na lista!';
                btnListaEspera.style.backgroundColor = '#28a745'; // Verde
                btnListaEspera.style.borderColor = '#28a745';
            } else {
                alert('Erro: ' + res.erro);
                btnListaEspera.disabled = false;
                btnListaEspera.innerHTML = 'Tentar Novamente';
            }
        } catch (error) {
            console.error(error);
            alert('Erro de conexão.');
        }
    });

    // --- 7. PASSO 3.5: Selecionar Horário (Transição 3 -> 4) ---
    function selecionarHorario(slot, data) {
        agendamentoSelecionado.data = data;
        agendamentoSelecionado.horario = slot;
        
        document.querySelectorAll('.time-slot-btn').forEach(btn => btn.classList.remove('selected'));
        event.target.classList.add('selected');
        
        gerarResumo();
        irParaPasso('passo-4-confirmacao');
    }

    // --- 8. PASSO 4: Gerar Resumo ---
    function gerarResumo() {
        const servico = agendamentoSelecionado.servico;
        const prof = agendamentoSelecionado.profissional;
        const data = new Date(agendamentoSelecionado.data + 'T00:00:00');
        const horario = agendamentoSelecionado.horario;

        resumoContainer.innerHTML = `
            <h3>Por favor, confirme os dados:</h3>
            <ul>
                <li>
                    <span>Serviço:</span>
                    <strong>${servico.nome_servico} (${servico.duracao_minutos} min)</strong>
                </li>
                <li>
                    <span>Profissional:</span>
                    <strong>${prof.nome}</strong>
                </li>
                <li>
                    <span>Data:</span>
                    <strong>${data.toLocaleDateString('pt-BR')}</strong>
                </li>
                <li>
                    <span>Horário:</span>
                    <strong>${horario}</strong>
                </li>
                <li>
                    <span>Preço:</span>
                    <strong>R$ ${parseFloat(servico.preco).toFixed(2).replace('.', ',')}</strong>
                </li>
            </ul>
        `;
    }

    // --- 9. PASSO 4.5: FINALIZAR AGENDAMENTO (Botão Confirmar) ---
    btnConfirmar.addEventListener('click', async () => {
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Agendando...';

        const formData = new FormData();
        formData.append('id_negocio', id_negocio);
        formData.append('id_servico', agendamentoSelecionado.servico.id);
        formData.append('id_funcionario', agendamentoSelecionado.profissional.id);
        formData.append('data_hora_inicio', `${agendamentoSelecionado.data} ${agendamentoSelecionado.horario}`);

        try {
            const response = await fetch('processar_agendamento.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();

            if (resultado.sucesso) {
                passos['passo-4-confirmacao'].innerHTML = `
                    <h2 style="color: #4CAF50;">Agendamento Confirmado!</h2>
                    <p>Ótimo! Seu horário foi reservado com sucesso.</p>
                    <p>Enviamos uma confirmação para o seu e-mail e notificamos o profissional.</p>
                    <a href="meus_agendamentos.php" class="form-button" style="margin-top: 1rem;">Ver Meus Agendamentos</a>
                `;
                steppers['passo-4-confirmacao'].classList.add('active');
            } else {
                resumoContainer.innerHTML += `<p style="color: red; font-weight: bold; margin-top: 1rem;">Erro: ${resultado.erro}</p>`;
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = 'Tentar Novamente';
            }

        } catch (error) {
            resumoContainer.innerHTML += `<p style="color: red; font-weight: bold; margin-top: 1rem;">Erro de conexão. Tente novamente.</p>`;
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Tentar Novamente';
        }
    });

    // --- 10. INICIAR O SCRIPT ---
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownBtn) {
        dropdownBtn.addEventListener('click', (e) => { e.stopPropagation(); dropdownContent.classList.toggle('show'); });
        window.addEventListener('click', (e) => { if (dropdownContent.classList.contains('show') && !e.target.closest('.user-dropdown')) { dropdownContent.classList.remove('show'); }});
    }
    
    // Inicializa o Calendário
    const fp = flatpickr(inputData, {
        dateFormat: "Y-m-d",
        minDate: "today",
        defaultDate: "today",
        "locale": "pt",
        onChange: function(selectedDates, dateStr, instance) {
            // Só recarrega os horários se o passo 3 estiver ativo
            if (passos['passo-3-horario'].classList.contains('active-step')) {
                carregarHorarios(dateStr);
            }
        }
    });
    
    // Inicia o assistente
    irParaPasso('passo-1-servico');
    carregarServicos();
    // [NOVO] SEMPRE mostra a opção de lista de espera se não for feriado
            areaListaEspera.style.display = 'block';
            
            // [ADICIONE ISSO] Atualiza a data no texto do card
            const dataFormatadaTexto = new Date(dataSelecionada + 'T00:00:00').toLocaleDateString('pt-BR');
            document.getElementById('data-lista-espera').textContent = dataFormatadaTexto;
            
            // Reseta o botão para o estado original (Roxo)
            btnListaEspera.disabled = false;
            btnListaEspera.innerHTML = '<i class="fas fa-bell"></i> Avise-me se vagar um horário';
            btnListaEspera.style.backgroundColor = ''; // Remove cores inline antigas

</script>
<?php require 'footer.php'; ?>
</body>
</html>