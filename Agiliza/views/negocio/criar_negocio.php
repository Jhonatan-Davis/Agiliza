<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

// --- Porteiro ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "/cadastro/selecao.php");
    exit();
}

// --- Busca as Categorias "Mãe" ---
try {
    $stmt_cat = $pdo->query("SELECT * FROM categorias_negocio ORDER BY nome_categoria ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar categorias: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure seu Negócio - Agiliza</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/views/login/style_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container business-setup">
        <div class="setup-kicker"><i class="fas fa-store"></i> Configuração inicial</div>
        <h1>Seu Negócio</h1>
        <p class="setup-subtitle">
            Parabéns! Vamos configurar sua empresa.
        </p>

        <div class="setup-progress" aria-label="Etapas da configuração">
            <div class="setup-progress-step active" data-progress="1"><span class="setup-progress-dot">1</span><small>Identidade</small></div>
            <span class="setup-progress-line"></span>
            <div class="setup-progress-step" data-progress="2"><span class="setup-progress-dot">2</span><small>Serviço</small></div>
            <span class="setup-progress-line"></span>
            <div class="setup-progress-step" data-progress="3"><span class="setup-progress-dot">3</span><small>Localização</small></div>
        </div>

        <form class="business-setup-form" action="<?= BASE_URL ?>/controllers/NegocioController.php?action=criar" method="POST">
            <section class="setup-panel active" data-step="1">
                <span class="setup-panel-kicker">Etapa 1 de 3</span>
                <h2>Vamos começar pelo básico</h2>
                <p class="setup-panel-description">Como seus clientes vão encontrar o seu negócio?</p>
                <div class="form-group">
                    <label for="nome_negocio">Nome do seu negócio</label>
                    <input type="text" id="nome_negocio" name="nome_negocio" placeholder="Ex: Barbearia do Zé" required>
                </div>
                <div class="form-group">
                    <label for="id_categoria">Qual é o nicho do seu negócio?</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Selecione um nicho</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome_categoria']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="login-button setup-next" data-next-step="2">Continuar <i class="fas fa-arrow-right"></i></button>
            </section>

            <section class="setup-panel" data-step="2" hidden>
                <span class="setup-panel-kicker">Etapa 2 de 3</span>
                <h2>Conte um pouco mais</h2>
                <p class="setup-panel-description">Escolha o tipo de serviço que você oferece.</p>
                <div class="form-group">
                    <label for="id_tipo_negocio">Qual o tipo do seu negócio?</label>
                    <select id="id_tipo_negocio" name="id_tipo_negocio" required disabled>
                        <option value="">Escolha um nicho primeiro</option>
                    </select>
                </div>
                <div class="setup-actions">
                    <button type="button" class="setup-back" data-previous-step="1"><i class="fas fa-arrow-left"></i> Voltar</button>
                    <button type="button" class="login-button setup-next" data-next-step="3">Continuar <i class="fas fa-arrow-right"></i></button>
                </div>
            </section>

            <section class="setup-panel" data-step="3" hidden>
                <span class="setup-panel-kicker">Etapa 3 de 3</span>
                <h2>Onde seus clientes encontram você?</h2>
                <p class="setup-panel-description">Essas informações ajudam no mapa e na localização.</p>
                <div class="form-group cep-field">
                    <label for="cep">CEP</label>
                    <div class="cep-input-row">
                        <input type="text" id="cep" name="cep" inputmode="numeric" autocomplete="postal-code" maxlength="9" placeholder="00000-000" required>
                        <span class="cep-status" id="cep-status" role="status"></span>
                    </div>
                    <small class="field-hint">Digite o CEP para preencher o endereco automaticamente.</small>
                </div>
                <div class="address-fields-grid">
                    <div class="form-group address-street-field">
                        <label for="logradouro">Rua / avenida</label>
                        <input type="text" id="logradouro" name="logradouro" autocomplete="address-line1" placeholder="Nome da rua" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_endereco">Numero</label>
                        <input type="text" id="numero_endereco" name="numero_endereco" autocomplete="address-line2" placeholder="Ex: 120">
                    </div>
                    <div class="form-group">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" autocomplete="address-level3" placeholder="Seu bairro" required>
                    </div>
                    <div class="form-group">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" autocomplete="address-level2" placeholder="Sua cidade" required>
                    </div>
                </div>
                <input type="hidden" id="endereco_texto" name="endereco_texto">
                <div class="form-group setup-reference-field">
                    <label for="ponto_referencia">Ponto de referência <span>(opcional)</span></label>
                    <input type="text" id="ponto_referencia" name="ponto_referencia" placeholder="Ex: Ao lado da padaria">
                </div>
                <div class="setup-actions">
                    <button type="button" class="setup-back" data-previous-step="2"><i class="fas fa-arrow-left"></i> Voltar</button>
                    <button type="submit" class="login-button setup-submit"><i class="fas fa-check"></i> Salvar e concluir</button>
                </div>
            </section>
            
        </form>
    </div>

<script>
    const selectCategoria = document.getElementById('id_categoria');
    const selectTipo = document.getElementById('id_tipo_negocio');
    const businessForm = document.querySelector('.business-setup-form');
    const cepInput = document.getElementById('cep');
    const cepStatus = document.getElementById('cep-status');
    let cepRequestController;
    const setupPanels = [...document.querySelectorAll('.setup-panel')];
    const setupProgressSteps = [...document.querySelectorAll('.setup-progress-step')];
    let currentStep = 1;

    function showSetupStep(step) {
        currentStep = step;
        setupPanels.forEach((panel) => {
            const isActive = Number(panel.dataset.step) === step;
            panel.hidden = !isActive;
            panel.classList.toggle('active', isActive);
        });
        setupProgressSteps.forEach((progressStep) => {
            const progress = Number(progressStep.dataset.progress);
            progressStep.classList.toggle('active', progress === step);
            progressStep.classList.toggle('completed', progress < step);
        });
    }

    document.querySelectorAll('[data-next-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('.setup-panel');
            const requiredFields = [...panel.querySelectorAll('input[required], select[required]')];
            const valid = requiredFields.every((field) => {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    return false;
                }
                return true;
            });

            if (valid) {
                showSetupStep(Number(button.dataset.nextStep));
            }
        });
    });

    document.querySelectorAll('[data-previous-step]').forEach((button) => {
        button.addEventListener('click', () => showSetupStep(Number(button.dataset.previousStep)));
    });

    function formatCep(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);
        return digits.length > 5 ? `${digits.slice(0, 5)}-${digits.slice(5)}` : digits;
    }

    function setCepStatus(message, type = '') {
        cepStatus.textContent = message;
        cepStatus.className = `cep-status ${type}`;
    }

    cepInput.addEventListener('input', () => {
        cepInput.value = formatCep(cepInput.value);
        if (cepInput.value.replace(/\D/g, '').length < 8) setCepStatus('');
    });

    cepInput.addEventListener('blur', async () => {
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            setCepStatus('Informe 8 digitos', 'invalid');
            return;
        }
        if (cepRequestController) cepRequestController.abort();
        cepRequestController = new AbortController();
        setCepStatus('Buscando...', 'loading');

        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, { signal: cepRequestController.signal });
            const data = await response.json();
            if (data.erro) {
                setCepStatus('CEP nao encontrado', 'invalid');
                return;
            }
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('numero_endereco').focus();
            setCepStatus('Endereco encontrado', 'success');
        } catch (error) {
            if (error.name !== 'AbortError') setCepStatus('Preencha manualmente', 'invalid');
        }
    });

    businessForm.addEventListener('submit', () => {
        const parts = [
            document.getElementById('logradouro').value.trim(),
            document.getElementById('numero_endereco').value.trim(),
            document.getElementById('bairro').value.trim(),
            document.getElementById('cidade').value.trim(),
            cepInput.value.trim()
        ].filter(Boolean);
        document.getElementById('endereco_texto').value = parts.join(', ');
    });

    selectCategoria.addEventListener('change', async () => {
        const idCategoria = selectCategoria.value;
        
        selectTipo.innerHTML = '<option value="">Carregando...</option>';
        selectTipo.disabled = true;

        if (!idCategoria) {
            selectTipo.innerHTML = '<option value="">-- Escolha um nicho primeiro --</option>';
            return;
        }

        try {
            // O fetch está correto (ambos os arquivos estão na pasta /negocio/)
            const response = await fetch(`<?= BASE_URL ?>/controllers/NegocioController.php?action=tipos&id_categoria=${idCategoria}`);
            
            // [NOVO DEBUG] Verifica se o arquivo não foi encontrado (404)
            if (!response.ok) {
                 throw new Error(`Erro de rede: ${response.statusText}`);
            }
            
            const tipos = await response.json();

            if (tipos.sucesso && tipos.dados.length > 0) {
                selectTipo.innerHTML = '<option value="">-- Selecione o tipo --</option>';
                tipos.dados.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.id;
                    option.textContent = tipo.nome_tipo;
                    selectTipo.appendChild(option);
                });
                selectTipo.disabled = false; // Habilita o dropdown
            } else {
                selectTipo.innerHTML = '<option value="">-- Nenhum tipo encontrado --</option>';
            }
        } catch (error) {
            console.error("Erro ao buscar tipos:", error);
            selectTipo.innerHTML = `<option value="">-- Erro ao carregar --</option>`;
        }
    });

    showSetupStep(currentStep);
</script>

</body>
</html>