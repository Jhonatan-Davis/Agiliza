<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['funcao'] != 'dono') { header("Location: " . BASE_URL . "/views/login/login.php"); exit(); }
$id_negocio=$_SESSION['id_negocio'];$id_dono=$_SESSION['usuario_id'];
$stmt_check_func=$pdo->prepare("SELECT COUNT(*) FROM negocio_membros WHERE id_usuario=:id_dono AND id_negocio=:id_negocio AND funcao='funcionario'");
$stmt_check_func->execute(['id_dono'=>$id_dono,'id_negocio'=>$id_negocio]);$dono_e_funcionario=$stmt_check_func->fetchColumn()>0;
$horarios_db=[];try{$stmt=$pdo->prepare("SELECT * FROM horarios_funcionamento WHERE id_negocio=:id");$stmt->execute(['id'=>$id_negocio]);foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $d)$horarios_db[$d['dia_semana']]=$d;$stmt_locais=$pdo->prepare("SELECT * FROM feriados_personalizados WHERE id_negocio=:id ORDER BY data ASC");$stmt_locais->execute(['id'=>$id_negocio]);$feriados_locais=$stmt_locais->fetchAll(PDO::FETCH_ASSOC);$stmt_feriado_config=$pdo->prepare("SELECT bloquear_feriados_auto FROM negocios WHERE id=:id");$stmt_feriado_config->execute(['id'=>$id_negocio]);$bloquear_feriados_auto=$stmt_feriado_config->fetchColumn();}catch(PDOException $e){die("Erro: ".$e->getMessage());}
$dias_da_semana=[1=>'Segunda-feira',2=>'Terça-feira',3=>'Quarta-feira',4=>'Quinta-feira',5=>'Sexta-feira',6=>'Sábado',0=>'Domingo'];$dias_curto=[1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',0=>'Dom'];
$primeira_vez=empty($horarios_db);$modo_edicao=isset($_GET['editar'])||$primeira_vez;
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Meus Horários - Agiliza</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/views/painel_dono/style_painel.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/views/login/style_cadastro.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
:root{--agiliza-brand:#087f8c;--agiliza-brand-dark:#05616b}
.timepicker::-webkit-calendar-picker-indicator{display:none}
.form-button,.login-button{background:#087f8c!important;border-color:#087f8c!important}
.form-button:hover,.login-button:hover{background:#05616b!important}
.switch input:checked + .slider{background:#087f8c!important}
.calendario-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
.dia-card{border-radius:14px;padding:14px;text-align:center;border:1px solid #e2e8f0;background:#fff}
.dia-card.aberto{border-color:#087f8c;box-shadow:0 4px 16px rgba(8,127,140,.1)}
.dia-card.fechado{opacity:.6;background:#f8fafc}
.dia-card strong{display:block;font-size:.95rem;margin-bottom:6px}
.dia-card .badge{display:inline-block;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;margin-bottom:6px}
.dia-card.aberto .badge{background:#e5f4f3;color:#05616b}
.dia-card.fechado .badge{background:#fee;color:#b91c1c}
.dia-card .horas{font-size:.8rem;color:#60747b;line-height:1.4}
.wizard-dias{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.wizard-dia{border:1px solid #cfe0e0;border-radius:10px;padding:10px;display:flex;justify-content:space-between;align-items:center;background:#fbfdfd}
.wizard-dia:has(input:checked){border-color:#087f8c;background:#e5f4f3}
</style></head><body>
<div class="painel-container">
<header class="mobile-header"><button class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button><h1>Agili<span>za</span></h1></header>
<nav class="sidebar"><div class="sidebar-nav">
<a href="index.php">Dashboard</a><a href="agenda_completa.php">Agenda Completa</a><a href="meu_negocio.php">Meu Negócio</a><a href="minha_equipe.php">Minha Equipe</a><a href="meus_servicos.php">Meus Serviços</a><a href="meus_horarios.php" class="active">Meus Horários</a><a href="historico.php">Histórico e Vendas</a><a href="financeiro.php">Financeiro</a><a href="meus_clientes.php">Meus Clientes</a>
<?php if($dono_e_funcionario):?><a href="meu_perfil.php" class="link-extra"><i class="fas fa-user-cog"></i> Meu Perfil (Pessoal)</a><a href="meu_portfolio.php" class="link-extra"><i class="fas fa-camera"></i> Meu Portfólio</a><?php endif;?>
</div><div class="sidebar-logout"><a href="<?= BASE_URL ?>/controllers/logout.php">Sair</a></div></nav>
<main class="main-content">
<?php if(isset($_GET['sucesso'])) echo '<div class="auto-dismiss-message" style="background:#087f8c;color:#fff;padding:.75rem;border-radius:9px;margin-bottom:1rem;text-align:center">Horários salvos com sucesso!</div>';?>
<?php if($modo_edicao):?>
<div class="business-setup" style="margin:0 auto">
<div class="setup-kicker"><i class="fas fa-clock"></i> Horários de funcionamento</div>
<h1><?= $primeira_vez?'Configure seus horários':'Editar horários'?></h1>
<p class="setup-subtitle"><?= $primeira_vez?'Primeira vez por aqui — vamos configurar em 3 passos.':'Ajuste dias, horários e feriados.'?></p>
<div class="setup-progress"><div class="setup-progress-step active" data-progress="1"><span class="setup-progress-dot">1</span><small>Dias</small></div><span class="setup-progress-line"></span><div class="setup-progress-step" data-progress="2"><span class="setup-progress-dot">2</span><small>Horários</small></div><span class="setup-progress-line"></span><div class="setup-progress-step" data-progress="3"><span class="setup-progress-dot">3</span><small>Feriados</small></div></div>
<form class="business-setup-form" action="processar_horarios.php" method="POST">
<section class="setup-panel active" data-step="1">
<span class="setup-panel-kicker">Etapa 1 de 3</span><h2>Quais dias você abre?</h2><p class="setup-panel-description">Ative os dias de funcionamento.</p>
<div class="wizard-dias">
<?php foreach($dias_da_semana as $n=>$nome): $h=$horarios_db[$n]??null; $chk=$h&&$h['aberto']?'checked':($primeira_vez&&$n>=1&&$n<=5?'checked':'');?>
<div class="wizard-dia"><span><?=$dias_curto[$n]?> <small style="color:#60747b"><?=$nome?></small></span><label class="switch"><input type="checkbox" name="aberto[<?=$n?>]" <?=$chk?>><span class="slider"></span></label></div>
<?php endforeach;?>
</div>
<div class="setup-actions"><a href="<?= $primeira_vez?'index.php':'meus_horarios.php'?>" class="setup-back" style="text-decoration:none"><?= $primeira_vez?'Depois':'Cancelar'?></a><button type="button" class="login-button setup-next" data-next-step="2">Continuar <i class="fas fa-arrow-right"></i></button></div>
</section>
<section class="setup-panel" data-step="2" hidden>
<span class="setup-panel-kicker">Etapa 2 de 3</span><h2>Defina os horários</h2><p class="setup-panel-description">Preencha manhã e tarde. Deixe vazio se não abrir no período.</p>
<div class="form-card assistente-rapido-card" style="margin-bottom:12px;padding:12px">
<strong style="font-size:.8rem">Assistente rápido</strong>
<div class="horario-inputs" style="margin-top:8px">
<div><label>Abre Manhã</label><input type="text" class="timepicker" id="helper_abertura_manha" placeholder="HH:MM"></div>
<div><label>Fecha Manhã</label><input type="text" class="timepicker" id="helper_fechamento_manha" placeholder="HH:MM"></div>
<div><label>Abre Tarde</label><input type="text" class="timepicker" id="helper_abertura_tarde" placeholder="HH:MM"></div>
<div><label>Fecha Tarde</label><input type="text" class="timepicker" id="helper_fechamento_tarde" placeholder="HH:MM"></div>
</div>
<div class="dias-checkboxes" style="margin:8px 0">
<?php foreach($dias_curto as $n=>$s):?><input type="checkbox" class="helper-dia" value="<?=$n?>" id="h-<?=$n?>"><label for="h-<?=$n?>"><?=$s?></label><?php endforeach;?>
</div>
<div style="display:flex;gap:8px"><button type="button" class="form-button" id="btn_aplicar_horario" style="flex:1">Aplicar</button><button type="button" class="form-button" id="btn_dias_uteis" style="flex:1;background:#60747b!important">Dias úteis</button></div>
</div>
<?php foreach($dias_da_semana as $n=>$nome): $h=$horarios_db[$n]??null;?>
<div class="horario-dia-container" id="container-dia-<?=$n?>" style="border:1px solid #e2e8f0;border-radius:10px;padding:10px;margin-bottom:8px">
<div class="dia-info" style="margin-bottom:8px"><strong><?=$nome?></strong></div>
<div class="horario-inputs">
<div><label>Abre Manhã</label><input type="text" class="timepicker" name="abertura_manha[<?=$n?>]" value="<?=htmlspecialchars($h['hora_abertura_manha']??'')?>" placeholder="HH:MM"></div>
<div><label>Fecha Manhã</label><input type="text" class="timepicker" name="fechamento_manha[<?=$n?>]" value="<?=htmlspecialchars($h['hora_fechamento_manha']??'')?>" placeholder="HH:MM"></div>
<div><label>Abre Tarde</label><input type="text" class="timepicker" name="abertura_tarde[<?=$n?>]" value="<?=htmlspecialchars($h['hora_abertura_tarde']??'')?>" placeholder="HH:MM"></div>
<div><label>Fecha Tarde</label><input type="text" class="timepicker" name="fechamento_tarde[<?=$n?>]" value="<?=htmlspecialchars($h['hora_fechamento_tarde']??'')?>" placeholder="HH:MM"></div>
</div></div>
<?php endforeach;?>
<div class="setup-actions"><button type="button" class="setup-back" data-previous-step="1"><i class="fas fa-arrow-left"></i> Voltar</button><button type="button" class="login-button setup-next" data-next-step="3">Continuar <i class="fas fa-arrow-right"></i></button></div>
</section>
<section class="setup-panel" data-step="3" hidden>
<span class="setup-panel-kicker">Etapa 3 de 3</span><h2>Feriados</h2><p class="setup-panel-description">Configure feriados nacionais e locais. Você pode pular e ajustar depois.</p>
<div style="border:1px solid #cfe0e0;border-radius:10px;padding:14px;background:#fbfdfd;margin-bottom:12px">
<div class="aberto-toggle" style="margin:0"><strong>Bloquear feriados nacionais?</strong><div><span>Não</span><label class="switch"><input type="checkbox" id="chk-feriado-wizard" <?php if($bloquear_feriados_auto) echo 'checked';?>><span class="slider"></span></label><span>Sim</span></div></div>
<p style="font-size:.8rem;color:#60747b;margin:8px 0 0">Salvo junto com os horários ao clicar em Salvar.</p>
</div>
<div style="border:1px solid #cfe0e0;border-radius:10px;padding:14px;background:#fbfdfd">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><strong>Feriados locais</strong><button type="button" class="form-button" id="btn-abrir-modal-feriado" style="padding:6px 12px;font-size:.8rem">+ Adicionar</button></div>
<?php if(count($feriados_locais)>0):?><div style="display:grid;gap:6px"><?php foreach($feriados_locais as $f):?><div style="display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px"><span style="font-size:.85rem"><?=date('d/m/Y',strtotime($f['data']))?> — <?=htmlspecialchars($f['descricao'])?></span><a href="processar_feriados.php?action=del_feriado&id=<?=$f['id']?>" class="icon-delete" onclick="return confirm('Excluir?')"><i class="fas fa-trash-alt" style="color:#c84b4b"></i></a></div><?php endforeach;?></div><?php else:?><p style="text-align:center;color:#888;font-size:.85rem">Nenhum feriado local cadastrado.</p><?php endif;?>
</div>
<div class="setup-actions"><button type="button" class="setup-back" data-previous-step="2"><i class="fas fa-arrow-left"></i> Voltar</button><button type="submit" class="login-button setup-submit"><i class="fas fa-check"></i> Salvar horários</button></div>
</section>
</form>
<form id="form-feriado-config" action="processar_feriados.php" method="POST" style="display:none"><input type="hidden" name="action" value="salvar_config"><input type="hidden" name="bloquear_feriados" id="hidden-bloquear"></form>
</div>
<?php else:?>
<h2>Meus horários</h2><p>Visão geral do funcionamento.</p>
<div class="calendario-grid" style="margin:16px 0">
<?php foreach($dias_da_semana as $n=>$nome): $h=$horarios_db[$n]??null; $aberto=$h&&$h['aberto'];?>
<div class="dia-card <?= $aberto?'aberto':'fechado'?>"><strong><?=$nome?></strong><span class="badge"><?= $aberto?'Aberto':'Fechado'?></span>
<?php if($aberto): $manha=trim(($h['hora_abertura_manha']??'').' - '.($h['hora_fechamento_manha']??''),' -'); $tarde=trim(($h['hora_abertura_tarde']??'').' - '.($h['hora_fechamento_tarde']??''),' -');?>
<div class="horas"><?php if($manha) echo '<div><i class="far fa-clock"></i> Manhã: '.$manha.'</div>'; if($tarde) echo '<div>Tarde: '.$tarde.'</div>'; if(!$manha&&!$tarde) echo '<div>Horário não definido</div>';?></div>
<?php else:?><div class="horas">Fechado</div><?php endif;?></div>
<?php endforeach;?>
</div>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-align:center">
<p style="color:#60747b;font-size:.9rem;margin:0 0 12px">Precisa ajustar os dias ou horários?</p>
<a href="meus_horarios.php?editar=1" class="login-button" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;justify-content:center;background:#087f8c;color:#fff;padding:10px 20px;border-radius:9px;font-weight:700"><i class="fas fa-pen"></i> editar horario de funcionamento</a>
</div>
<?php endif;?>
</main></div>
<div class="modal-overlay" id="modal-feriado-overlay"><div class="modal-container"><div class="modal-header"><h2>Adicionar feriado local</h2><button class="modal-close-btn" id="modal-feriado-close-btn">&times;</button></div><div class="modal-body">
<form action="processar_feriados.php" method="POST" class="form-feriado-local"><input type="hidden" name="action" value="add_feriado"><div class="form-group"><label>Data</label><input type="date" name="data_feriado" required></div><div class="form-group"><label>Descrição</label><input type="text" name="descricao_feriado" placeholder="Aniversário da Cidade" required></div><button type="submit" class="form-button">Adicionar</button></form>
</div></div></div>
<script>
const setupPanels=[...document.querySelectorAll('.setup-panel')],setupProgressSteps=[...document.querySelectorAll('.setup-progress-step')];
function showSetupStep(s){setupPanels.forEach(p=>{const a=Number(p.dataset.step)===s;p.hidden=!a;p.classList.toggle('active',a)});setupProgressSteps.forEach(e=>{const p=Number(e.dataset.progress);e.classList.toggle('active',p===s);e.classList.toggle('completed',p<s)});}
document.querySelectorAll('[data-next-step]').forEach(b=>b.addEventListener('click',()=>showSetupStep(Number(b.dataset.nextStep))));
document.querySelectorAll('[data-previous-step]').forEach(b=>b.addEventListener('click',()=>showSetupStep(Number(b.dataset.previousStep))));
if(setupPanels.length) showSetupStep(1);
const btnAplicar=document.getElementById('btn_aplicar_horario'),btnDiasUteis=document.getElementById('btn_dias_uteis');
const hAb=document.getElementById('helper_abertura_manha'),hFc=document.getElementById('helper_fechamento_manha'),hAbT=document.getElementById('helper_abertura_tarde'),hFcT=document.getElementById('helper_fechamento_tarde');
const cbs=document.querySelectorAll('.helper-dia');
if(btnDiasUteis) btnDiasUteis.addEventListener('click',()=>cbs.forEach(cb=>cb.checked=(cb.value>=1&&cb.value<=5)));
if(btnAplicar) btnAplicar.addEventListener('click',()=>{const ab=hAb.value,fc=hFc.value,abT=hAbT.value,fcT=hFcT.value;cbs.forEach(cb=>{if(cb.checked){const d=cb.value,c=document.getElementById('container-dia-'+d);if(c){c.querySelector('input[name="abertura_manha['+d+']"]').value=ab;c.querySelector('input[name="fechamento_manha['+d+']"]').value=fc;c.querySelector('input[name="abertura_tarde['+d+']"]').value=abT;c.querySelector('input[name="fechamento_tarde['+d+']"]').value=fcT;}}})});
const chkWizard=document.getElementById('chk-feriado-wizard');
document.querySelector('.business-setup-form')?.addEventListener('submit',()=>{if(chkWizard){document.getElementById('hidden-bloquear').value=chkWizard.checked?'on':'';const fd=new FormData(document.getElementById('form-feriado-config'));fetch('processar_feriados.php',{method:'POST',body:fd});}});
document.addEventListener('DOMContentLoaded',()=>{const a=document.querySelector('.auto-dismiss-message');if(a) setTimeout(()=>a.classList.add('fade-out'),3000)});
const btnAbrir=document.getElementById('btn-abrir-modal-feriado'),overlay=document.getElementById('modal-feriado-overlay'),btnFechar=document.getElementById('modal-feriado-close-btn');
if(btnAbrir) btnAbrir.addEventListener('click',()=>overlay.classList.add('show'));
if(btnFechar) btnFechar.addEventListener('click',()=>overlay.classList.remove('show'));
if(overlay) overlay.addEventListener('click',e=>{if(e.target===overlay) overlay.classList.remove('show')});
document.querySelectorAll('.timepicker').forEach(el=>{if(el._flatpickr) el._flatpickr.destroy();});
flatpickr(".timepicker",{enableTime:true,noCalendar:true,dateFormat:"H:i",time_24hr:true,minuteIncrement:15,allowInput:true,clickOpens:true,wrap:false,onKeyDown:(s,d,inst,e)=>{if(e.key==='Enter'){e.preventDefault();inst.close();e.target.blur();}}});
</script>
<script src="script_painel.js"></script>
</body></html>
