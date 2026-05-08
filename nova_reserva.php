<?php
require_once 'includes/auth.php';
require_once 'includes/conflitos.php';
requireLogin();

$user = currentUser();

// Load active rooms for the dropdown
$salasResult = $conn->query("SELECT id, nome, capacidade FROM salas WHERE status = 'ativa' ORDER BY nome");
if (!$salasResult) die('SQL error (salas): ' . $conn->error);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto     = trim($_POST['titulo'] ?? '');
    $sala_id     = (int)($_POST['sala'] ?? 0);
    $data        = $_POST['data'] ?? '';
    $hora_inicio = $_POST['horaInicio'] ?? '';
    $hora_fim    = $_POST['horaFim'] ?? '';

    // Recurrence fields
    $recorrente  = isset($_POST['recorrente']);
    $tipo        = $_POST['frequencia'] ?? 'semanal';
    if (!in_array($tipo, ['semanal', 'mensal'], true)) $tipo = 'semanal';

    $erros = [];

    if ($assunto === '') $erros[] = 'Assunto é obrigatório.';
    if ($sala_id <= 0)   $erros[] = 'Selecione uma sala.';
    if ($data === '')    $erros[] = 'Data é obrigatória.';
    if ($hora_inicio === '' || $hora_fim === '') $erros[] = 'Hora início e fim são obrigatórias.';
    if ($hora_inicio !== '' && $hora_fim !== '' && $hora_fim <= $hora_inicio) $erros[] = 'Hora fim deve ser maior que hora início.';
    if ($data !== '' && $data < date('Y-m-d')) $erros[] = 'Não é possível reservar no passado.';

    // Verify room is active
    if ($sala_id > 0) {
        $chk = $conn->prepare("SELECT status FROM salas WHERE id = ?");
        if (!$chk) die('SQL error (check sala): ' . $conn->error);
        $chk->bind_param('i', $sala_id);
        $chk->execute();
        $salaRow = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$salaRow || $salaRow['status'] !== 'ativa') {
            $erros[] = 'A sala selecionada não está ativa.';
        }
    }

    // Conflict check for the main date (reservas + recurrence patterns)
    if (empty($erros)) {
        $conflicts = checkFullConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim);
        if ($conflicts > 0) {
            $erros[] = "Conflito de horário: já existe uma reserva (ou recorrência ativa) nessa sala para o período indicado em {$data}.";
        }
    }

    if (empty($erros)) {
        if ($recorrente) {
            // ── Create recurring reservation (next 6 months) ──
            $dia_semana = date('w', strtotime($data));
            $dia_mes    = date('j', strtotime($data));
            $fim = (new DateTime($data))->modify('+6 months')->format('Y-m-d');

            $stmt = $conn->prepare("INSERT INTO recorrencias (sala_id, usuario_id, assunto, tipo, dia_semana, dia_mes, hora_inicio, hora_fim, data_inicio, data_fim, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            if (!$stmt) die('SQL error (insert recorrencia): ' . $conn->error);
            $stmt->bind_param('iissiissss', $sala_id, $user['id'], $assunto, $tipo, $dia_semana, $dia_mes, $hora_inicio, $hora_fim, $data, $fim);
            $stmt->execute();
            $recorrencia_id = $stmt->insert_id;
            $stmt->close();

            // Generate individual reservations
            $dates = generateDates($data, $fim, $tipo);
            $conflictDates = [];
            $created = 0;

            $ins = $conn->prepare("INSERT INTO reservas (sala_id, usuario_id, assunto, data_reserva, hora_inicio, hora_fim, status, recorrencia_id) VALUES (?, ?, ?, ?, ?, ?, 'confirmada', ?)");
            if (!$ins) die('SQL error (insert reservas recorrentes): ' . $conn->error);

            foreach ($dates as $d) {
                if (checkFullConflict($conn, $sala_id, $d, $hora_inicio, $hora_fim, null, $recorrencia_id) > 0) {
                    $conflictDates[] = date('d/m/Y', strtotime($d));
                    continue;
                }
                $ins->bind_param('iissssi', $sala_id, $user['id'], $assunto, $d, $hora_inicio, $hora_fim, $recorrencia_id);
                $ins->execute();
                $created++;
            }
            $ins->close();

            if (!empty($conflictDates)) {
                setFlash('warning', "Recorrência criada com {$created} reservas. Conflitos ignorados nas datas: " . implode(', ', $conflictDates));
            } else {
                setFlash('success', "Recorrência criada com {$created} reservas.");
            }
            header('Location: recorrencias.php');
            exit();
        } else {
            // ── Single reservation ──
            $stmt = $conn->prepare("INSERT INTO reservas (sala_id, usuario_id, assunto, data_reserva, hora_inicio, hora_fim, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmada')");
            if (!$stmt) die('SQL error (insert reserva): ' . $conn->error);
            $stmt->bind_param('iissss', $sala_id, $user['id'], $assunto, $data, $hora_inicio, $hora_fim);
            if (!$stmt->execute()) die('SQL error (insert reserva): ' . $stmt->error);
            $stmt->close();

            setFlash('success', 'Reserva criada com sucesso!');
            header('Location: reservas.php');
            exit();
        }
    } else {
        setFlash('error', implode(' ', $erros));
    }
}

/**
 * Generate a list of dates based on recurrence type.
 */
function generateDates($start, $end, $tipo) {
    $dates = [];
    $current = new DateTime($start);
    $endDate = new DateTime($end);
    $maxMonths = 6;
    $limit = (new DateTime($start))->modify("+{$maxMonths} months");
    if ($endDate > $limit) $endDate = $limit;

    while ($current <= $endDate) {
        $dates[] = $current->format('Y-m-d');
        switch ($tipo) {
            case 'semanal': $current->modify('+1 week'); break;
            case 'mensal':  $current->modify('+1 month'); break;
            default:        $current->modify('+1 week'); break;
        }
    }
    return $dates;
}

$page_title = "Nova Reserva";
$additional_scripts = '
<script>
    document.getElementById("recorrente").addEventListener("change", function() {
        document.getElementById("recorrenciaOptions").style.display = this.checked ? "block" : "none";
    });
    
    function updatePreview() {
        const titulo = document.getElementById("titulo").value || "-";
        const salaEl = document.getElementById("sala");
        const sala = salaEl.selectedOptions[0]?.text || "-";
        const data = document.getElementById("data").value || "-";
        const horaInicio = document.getElementById("horaInicio").value || "-";
        const horaFim = document.getElementById("horaFim").value || "-";
        const participantes = document.getElementById("participantes").value || "-";
        
        document.getElementById("previewTitulo").textContent = titulo;
        document.getElementById("previewSala").textContent = sala === "Selecione uma sala" ? "-" : sala;
        document.getElementById("previewData").textContent = data;
        document.getElementById("previewHorario").textContent = horaInicio + " - " + horaFim;
        document.getElementById("previewResponsavel").textContent = "' . addslashes(htmlspecialchars($user['nome'])) . '";
        document.getElementById("previewParticipantes").textContent = participantes;
    }
    
    ["titulo", "sala", "data", "horaInicio", "horaFim", "participantes"].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener("input", updatePreview); el.addEventListener("change", updatePreview); }
    });
    updatePreview();
</script>';

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary d-md-none me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h4 class="mb-0">Nova Reserva</h4>
            <small class="text-muted">Criar uma nova reserva de sala</small>
        </div>
        <a href="reservas.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Voltar</a>
    </div>

    <div class="container-fluid px-4">
        <?php renderFlash(); ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Detalhes da Reserva</h5></div>
                    <div class="card-body">
                        <form method="POST" action="nova_reserva.php">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="titulo" class="form-label">Título da Reserva *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ex: Reunião de Projeto" value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="sala" class="form-label">Sala *</label>
                                    <select class="form-select" id="sala" name="sala" required>
                                        <option value="">Selecione uma sala</option>
                                        <?php
                                        $salasResult->data_seek(0);
                                        while ($s = $salasResult->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo (($_POST['sala'] ?? '') == $s['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['nome']) . ' (' . $s['capacidade'] . ' pessoas)'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Responsável</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nome']); ?>" disabled>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="data" class="form-label">Data *</label>
                                    <input type="date" class="form-control" id="data" name="data" value="<?php echo htmlspecialchars($_POST['data'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="horaInicio" class="form-label">Hora Início *</label>
                                    <input type="time" class="form-control" id="horaInicio" name="horaInicio" value="<?php echo htmlspecialchars($_POST['horaInicio'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="horaFim" class="form-label">Hora Fim *</label>
                                    <input type="time" class="form-control" id="horaFim" name="horaFim" value="<?php echo htmlspecialchars($_POST['horaFim'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descrição opcional"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="participantes" class="form-label">Número de Participantes</label>
                                    <input type="number" class="form-control" id="participantes" name="participantes" min="1" value="<?php echo htmlspecialchars($_POST['participantes'] ?? ''); ?>" placeholder="Ex: 8">
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="recorrente" name="recorrente" <?php echo isset($_POST['recorrente']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="recorrente">Reserva Recorrente</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-12" id="recorrenciaOptions" style="display: <?php echo isset($_POST['recorrente']) ? 'block' : 'none'; ?>;">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>Opções de Recorrência</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="frequencia" class="form-label">Frequência</label>
                                                    <select class="form-select" id="frequencia" name="frequencia">
                                                        <option value="semanal" <?php echo (($_POST['frequencia'] ?? '') === 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                                                        <option value="mensal" <?php echo (($_POST['frequencia'] ?? '') === 'mensal') ? 'selected' : ''; ?>>Mensal</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Reservas geradas automaticamente para os próximos 6 meses</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="reservas.php" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Criar Reserva</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Pré-visualização</h5></div>
                    <div class="card-body">
                        <div class="mb-3"><strong>Título:</strong><div id="previewTitulo" class="text-muted">-</div></div>
                        <div class="mb-3"><strong>Sala:</strong><div id="previewSala" class="text-muted">-</div></div>
                        <div class="mb-3"><strong>Data:</strong><div id="previewData" class="text-muted">-</div></div>
                        <div class="mb-3"><strong>Horário:</strong><div id="previewHorario" class="text-muted">-</div></div>
                        <div class="mb-3"><strong>Responsável:</strong><div id="previewResponsavel" class="text-muted">-</div></div>
                        <div class="mb-3"><strong>Participantes:</strong><div id="previewParticipantes" class="text-muted">-</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
