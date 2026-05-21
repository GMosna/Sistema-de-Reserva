<?php
$pageTitle    = 'Nova Reserva';
$pageSubtitle = 'Criar uma nova reserva de sala';
?>

<div class="d-flex justify-content-end mb-4">
    <a href="<?= APP_BASE ?>/reservas" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Detalhes da Reserva</div>
            <div class="card-body">
                <form method="post" action="<?= APP_BASE ?>/reservas">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ex: Reunião de Projeto" value="<?= e($_POST['titulo'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sala *</label>
                            <select class="form-select" id="sala" name="sala" required>
                                <option value="">Selecione uma sala</option>
                                <?php foreach ($salas as $s): ?>
                                <option value="<?= $s->id ?>" <?= (($_POST['sala']??'')==$s->id)?'selected':'' ?>>
                                    <?= e($s->nome) ?> (<?= $s->capacidade ?> pessoas)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsável</label>
                            <input type="text" class="form-control" value="<?= e($user['nome']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data *</label>
                            <input type="date" class="form-control" id="data" name="data" value="<?= e($_POST['data'] ?? '') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora Início *</label>
                            <input type="time" class="form-control" id="horaInicio" name="horaInicio" value="<?= e($_POST['horaInicio'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora Fim *</label>
                            <input type="time" class="form-control" id="horaFim" name="horaFim" value="<?= e($_POST['horaFim'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" rows="3" placeholder="Opcional"><?= e($_POST['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Participantes</label>
                            <input type="number" class="form-control" id="participantes" name="participantes" min="1" value="<?= e($_POST['participantes'] ?? '') ?>" placeholder="Ex: 8">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="recorrente" name="recorrente" <?= isset($_POST['recorrente'])?'checked':'' ?>>
                                <label class="form-check-label" for="recorrente">Reserva Recorrente</label>
                            </div>
                        </div>
                        <div class="col-12" id="recorrenciaOptions" style="display:<?= isset($_POST['recorrente'])?'block':'none' ?>">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Opções de Recorrência</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Frequência</label>
                                            <select class="form-select" name="frequencia">
                                                <option value="semanal" <?= (($_POST['frequencia']??'')==='semanal')?'selected':'' ?>>Semanal</option>
                                                <option value="mensal"  <?= (($_POST['frequencia']??'')==='mensal')?'selected':'' ?>>Mensal</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Reservas geradas para os próximos 6 meses</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= APP_BASE ?>/reservas" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Criar Reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Pré-visualização</div>
            <div class="card-body">
                <div class="mb-2"><strong>Título:</strong> <span id="pvTitulo" class="text-muted">–</span></div>
                <div class="mb-2"><strong>Sala:</strong> <span id="pvSala" class="text-muted">–</span></div>
                <div class="mb-2"><strong>Data:</strong> <span id="pvData" class="text-muted">–</span></div>
                <div class="mb-2"><strong>Horário:</strong> <span id="pvHora" class="text-muted">–</span></div>
                <div class="mb-2"><strong>Responsável:</strong> <span class="text-muted"><?= e($user['nome']) ?></span></div>
                <div class="mb-2"><strong>Participantes:</strong> <span id="pvPart" class="text-muted">–</span></div>
            </div>
        </div>
    </div>
</div>

<?php $inlineJs = '
document.getElementById("recorrente").addEventListener("change",function(){
  document.getElementById("recorrenciaOptions").style.display=this.checked?"block":"none";
});
function updatePreview(){
  var t=document.getElementById("titulo").value||"–";
  var sEl=document.getElementById("sala");
  var s=sEl.selectedOptions[0]?sEl.selectedOptions[0].text:"–";
  if(s==="Selecione uma sala")s="–";
  document.getElementById("pvTitulo").textContent=t;
  document.getElementById("pvSala").textContent=s;
  document.getElementById("pvData").textContent=document.getElementById("data").value||"–";
  var hi=document.getElementById("horaInicio").value, hf=document.getElementById("horaFim").value;
  document.getElementById("pvHora").textContent=(hi&&hf)?hi+" – "+hf:"–";
  document.getElementById("pvPart").textContent=document.getElementById("participantes").value||"–";
}
["titulo","sala","data","horaInicio","horaFim","participantes"].forEach(function(id){
  var el=document.getElementById(id);
  if(el){el.addEventListener("input",updatePreview);el.addEventListener("change",updatePreview);}
});
updatePreview();
'; ?>
