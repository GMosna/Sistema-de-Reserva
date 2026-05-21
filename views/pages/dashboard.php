<?php
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Visão geral do sistema';
$extraCss     = ['fullcalendar-custom.css'];
$extraJsCdn   = [
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
];

require_once VIEWS_PATH . '/components/badge.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div><div class="text-muted small mb-1">Reservas Hoje</div><div class="h4 mb-0"><?= $reservasHoje ?></div></div>
            <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div><div class="text-muted small mb-1">Salas Ativas</div><div class="h4 mb-0"><?= $salasAtivas ?></div></div>
            <div class="stat-icon"><i class="bi bi-door-open"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div><div class="text-muted small mb-1">Usuários Ativos</div><div class="h4 mb-0"><?= $usersAtivos ?></div></div>
            <div class="stat-icon"><i class="bi bi-people"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div><div class="text-muted small mb-1">Pendentes</div><div class="h4 mb-0"><?= $pendentes ?></div></div>
            <div class="stat-icon" style="background:var(--warning);color:#fff"><i class="bi bi-clock-history"></i></div>
        </div>
    </div>
</div>

<!-- Calendar Row -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-2">
                <div id="fullcalendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Today lists -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Ações Rápidas</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= APP_BASE ?>/reservas/nova" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nova Reserva</a>
                    <a href="<?= APP_BASE ?>/salas" class="btn btn-outline-primary"><i class="bi bi-door-open me-2"></i>Gerir Salas</a>
                    <?php if (($_SESSION['usuario_perfil'] ?? '') === 'admin'): ?>
                    <a href="<?= APP_BASE ?>/usuarios" class="btn btn-outline-primary"><i class="bi bi-people me-2"></i>Gerir Usuários</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-2"></i>Reservas Normais — Hoje</span>
                <a href="<?= APP_BASE ?>/reservas" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Sala</th><th>Horário</th><th>Assunto</th><th>Responsável</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php if (empty($normais)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Sem reservas normais hoje</td></tr>
                        <?php else: ?>
                            <?php foreach ($normais as $row): ?>
                            <tr>
                                <td><?= e($row['sala_nome']) ?></td>
                                <td><?= substr($row['hora_inicio'], 0, 5) . ' – ' . substr($row['hora_fim'], 0, 5) ?></td>
                                <td><?= e($row['assunto']) ?></td>
                                <td><?= e($row['usuario_nome']) ?></td>
                                <td><?= statusBadge($row['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recorrencias)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-repeat me-2"></i>Reservas Recorrentes — Hoje</span>
                <a href="<?= APP_BASE ?>/recorrencias" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Sala</th><th>Tipo</th><th>Horário</th><th>Assunto</th><th>Responsável</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($recorrencias as $rc): ?>
                        <tr>
                            <td><?= e($rc['sala_nome']) ?></td>
                            <td><span class="badge bg-info"><?= ucfirst($rc['tipo']) ?></span></td>
                            <td><?= substr($rc['hora_inicio'], 0, 5) . ' – ' . substr($rc['hora_fim'], 0, 5) ?></td>
                            <td><?= e($rc['assunto']) ?></td>
                            <td><?= e($rc['usuario_nome']) ?></td>
                            <td><?= statusBadge($rc['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Event detail / edit modal -->
<!-- Calendar event modal -->
<div class="modal fade" id="calEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0 rounded-4 overflow-hidden" style="box-shadow:0 24px 64px rgba(0,0,0,.18),0 4px 16px rgba(0,0,0,.10)">
            <!-- Status-tinted header -->
            <div id="calModalHeader" style="padding:1.25rem 1.5rem .875rem;position:relative;">
                <div id="calModalStatus" style="display:inline-flex;align-items:center;gap:6px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.45rem;"></div>
                <h5 id="calEventTitle" style="margin:0;font-size:1rem;font-weight:700;line-height:1.35;padding-right:2.25rem;color:#111827;"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute;top:.9rem;right:1rem;opacity:.45;transform:scale(.8);"></button>
            </div>

            <!-- View mode -->
            <div id="calEventView">
                <div id="calEventBody" style="padding:.625rem 1.5rem .875rem;display:flex;flex-direction:column;gap:.5rem;background:#fff;"></div>
                <div style="padding:.75rem 1.25rem 1.125rem;display:flex;justify-content:flex-end;gap:.5rem;border-top:1px solid #F3F4F6;background:#fff;">
                    <button type="button" class="btn btn-sm px-4 fw-semibold" style="background:#F3F4F6;color:#374151;border:none;border-radius:8px;" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-sm btn-primary px-4 fw-semibold d-none" id="btnEditarReserva" style="border-radius:8px;">
                        <i class="bi bi-pencil-square me-1"></i>Editar
                    </button>
                </div>
            </div>

            <!-- Edit mode -->
            <div id="calEventEdit" class="d-none" style="background:#fff;">
                <div style="padding:1rem 1.5rem;display:flex;flex-direction:column;gap:.875rem;">
                    <div>
                        <label style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;display:block;margin-bottom:.3rem;">Assunto</label>
                        <input type="text" class="form-control form-control-sm" id="editAssunto" maxlength="200" placeholder="Título da reserva" style="border-radius:8px;">
                    </div>
                    <div style="display:flex;align-items:flex-end;gap:.5rem;">
                        <div style="flex:1;">
                            <label style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;display:block;margin-bottom:.3rem;">Início</label>
                            <input type="time" class="form-control form-control-sm" id="editHoraInicio" style="border-radius:8px;">
                        </div>
                        <div style="padding-bottom:.45rem;color:#D1D5DB;font-weight:600;flex-shrink:0;">→</div>
                        <div style="flex:1;">
                            <label style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;display:block;margin-bottom:.3rem;">Fim</label>
                            <input type="time" class="form-control form-control-sm" id="editHoraFim" style="border-radius:8px;">
                        </div>
                    </div>
                    <div id="editError" class="d-none" style="border-radius:8px;background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;font-size:.8rem;padding:.5rem .75rem;"></div>
                </div>
                <div style="padding:.75rem 1.25rem 1.125rem;display:flex;justify-content:space-between;gap:.5rem;border-top:1px solid #F3F4F6;">
                    <button type="button" class="btn btn-sm px-3 fw-semibold" style="background:#F3F4F6;color:#374151;border:none;border-radius:8px;" id="btnVoltarView">
                        <i class="bi bi-arrow-left me-1"></i>Voltar
                    </button>
                    <button type="button" class="btn btn-sm btn-primary px-4 fw-semibold" id="btnSalvarEdit" style="border-radius:8px;">
                        <i class="bi bi-check2 me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scope modal -->
<div class="modal fade" id="calScopeModal" tabindex="-1" aria-hidden="true" style="z-index:1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:320px;">
        <div class="modal-content border-0 rounded-4 overflow-hidden" style="box-shadow:0 24px 64px rgba(0,0,0,.18);">
            <div style="padding:1.5rem 1.5rem .75rem;">
                <div style="width:38px;height:38px;background:#EDE9FE;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:.875rem;">
                    <i class="bi bi-arrow-repeat" style="color:#7C3AED;font-size:1rem;"></i>
                </div>
                <h6 style="font-size:.9375rem;font-weight:700;color:#111827;margin-bottom:.25rem;">Reserva Recorrente</h6>
                <p style="font-size:.8125rem;color:#6B7280;margin:0;">Qual alteração deseja aplicar?</p>
            </div>
            <div style="padding:.5rem 1rem 1.25rem;display:flex;flex-direction:column;gap:.5rem;">
                <button type="button" class="btn fw-semibold py-2 text-start px-3" style="border:1.5px solid #E5E7EB;border-radius:10px;color:#374151;background:#fff;" id="btnScopeOnly">
                    <i class="bi bi-calendar2-event me-2" style="color:#6B7280;"></i>Somente esta ocorrência
                </button>
                <button type="button" class="btn btn-primary fw-semibold py-2 text-start px-3" style="border-radius:10px;" id="btnScopeAll">
                    <i class="bi bi-calendar2-range me-2"></i>Todas as ocorrências
                </button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
var APP_BASE          = '<?= APP_BASE ?>';
var CURRENT_USER_ID   = <?= (int)($_SESSION['usuario_id']   ?? 0) ?>;
var CURRENT_USER_PERF = '<?= htmlspecialchars($_SESSION['usuario_perfil'] ?? '', ENT_QUOTES) ?>';
var CSRF_TOKEN        = '<?= csrf_token() ?>';

document.addEventListener('DOMContentLoaded', function () {

    // ── FullCalendar ──────────────────────────────────────────────────
    var calEl = document.getElementById('fullcalendar');
    if (calEl && typeof FullCalendar !== 'undefined') {
        var eventModal = null;
        var cal = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            firstDay: 0,
            height: 'auto',
            dayMaxEvents: 3,
            nowIndicator: true,
            scrollTime: '08:00:00',
            slotDuration: '00:30:00',
            slotLabelInterval: '01:00',
            allDaySlot: false,
            slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: false},
            headerToolbar: {
                left:   'prev,next',
                center: 'title',
                right:  'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {today: 'Hoje', month: 'Mês', week: 'Semana', list: 'Lista'},
            moreLinkText: function(n) { return '+' + n + ' Mais'; },
            eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: false},
            eventContent: function(arg) {
                var p = arg.event.extendedProps;
                var firstName = p.usuario ? p.usuario.split(' ')[0] : '';
                var time = arg.timeText || '';
                return {
                    html: (time ? '<span class="fc-event-time">' + esc(time) + '</span> ' : '') +
                          '<span class="fc-event-title">' + esc(arg.event.title) + '</span>' +
                          (firstName ? ' <span class="fc-event-user">' + esc(firstName) + '</span>' : '')
                };
            },
            datesSet: function(dateInfo) {
                var months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                var d = dateInfo.view.currentStart;
                var titleEl = calEl.querySelector('.fc-toolbar-title');
                if (titleEl) {
                    titleEl.innerHTML = '<strong>' + months[d.getMonth()] + '</strong> <span class="fc-title-year">' + d.getFullYear() + '</span>';
                }
                var chunk = calEl.querySelector('.fc-toolbar .fc-toolbar-chunk:nth-child(2)');
                if (chunk && !chunk.querySelector('.fc-cal-legend')) {
                    var leg = document.createElement('div');
                    leg.className = 'fc-cal-legend';
                    leg.innerHTML =
                        '<span><span class="fc-leg-dot" style="background:#10B981"></span>Confirmada</span>' +
                        '<span><span class="fc-leg-dot" style="background:#F59E0B"></span>Pendente</span>' +
                        '<span><span class="fc-leg-dot" style="background:#EF4444"></span>Cancelada</span>';
                    chunk.appendChild(leg);
                }
            },
            dayHeaderContent: function(arg) {
                var days = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                var name = days[arg.date.getUTCDay()];
                if (arg.view.type === 'timeGridWeek' || arg.view.type === 'timeGridDay') {
                    return {
                        html: '<div class="fc-goog-col-head">' +
                              '<span class="fc-goog-weekday">' + name + '</span>' +
                              '<span class="fc-goog-daynum' + (arg.isToday ? ' today' : '') + '">' + arg.date.getUTCDate() + '</span>' +
                              '</div>'
                    };
                }
                return { domNodes: [Object.assign(document.createElement('span'), {textContent: name})] };
            },
            events: function(info, successCallback, failureCallback) {
                function pad(n) { return String(n).padStart(2, '0'); }
                function dateStr(d) {
                    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                }
                var s, e;
                var rangeMs = info.end.getTime() - info.start.getTime();
                var isMonth = rangeMs > 14 * 24 * 60 * 60 * 1000;
                if (isMonth) {
                    var mid = new Date((info.start.getTime() + info.end.getTime()) / 2);
                    s = dateStr(new Date(mid.getFullYear(), mid.getMonth(), 1));
                    e = dateStr(new Date(mid.getFullYear(), mid.getMonth() + 1, 0));
                } else {
                    s = dateStr(info.start);
                    e = dateStr(new Date(info.end.getTime() - 1));
                }
                fetch(APP_BASE + '/api/events?start=' + s + '&end=' + e)
                    .then(function(r) { return r.json(); })
                    .then(successCallback)
                    .catch(failureCallback);
            },
            eventDidMount: function (info) {
                var p = info.event.extendedProps;
                var statusLabel = {confirmada: 'Confirmada', pendente: 'Pendente', cancelada: 'Cancelada'}[p.status] || p.status;
                info.el.setAttribute('data-bs-toggle', 'tooltip');
                info.el.setAttribute('data-bs-html', 'true');
                info.el.setAttribute('data-bs-placement', 'top');
                info.el.setAttribute('title',
                    '<strong>' + esc(info.event.title) + '</strong><br>' +
                    '<span class="text-white-50">Sala:</span> ' + esc(p.sala) + '<br>' +
                    '<span class="text-white-50">Resp.:</span> ' + esc(p.usuario) + '<br>' +
                    '<span class="text-white-50">Horário:</span> ' + p.horaInicio + '–' + p.horaFim + '<br>' +
                    '<span class="text-white-50">Estado:</span> ' + statusLabel
                );
                new bootstrap.Tooltip(info.el, {trigger: 'hover'});
            },
            eventClick: function (info) {
                var ev = info.event;
                var p  = ev.extendedProps;

                var statusBg   = {confirmada:'#ECFDF5', pendente:'#FFFBEB', cancelada:'#FFF1F2'};
                var statusFg   = {confirmada:'#065F46', pendente:'#92400E', cancelada:'#9F1239'};
                var statusDot  = {confirmada:'#10B981', pendente:'#F59E0B', cancelada:'#EF4444'};
                var statusLbl  = {confirmada:'Confirmada', pendente:'Pendente', cancelada:'Cancelada'};

                document.getElementById('calModalHeader').style.background = statusBg[p.status] || '#F9FAFB';
                document.getElementById('calModalStatus').innerHTML =
                    '<span style="width:7px;height:7px;border-radius:50%;background:' + (statusDot[p.status]||'#6B7280') + ';display:inline-block;flex-shrink:0;"></span>' +
                    '<span style="color:' + (statusFg[p.status]||'#374151') + '">' + esc(statusLbl[p.status] || p.status) + '</span>';
                document.getElementById('calEventTitle').textContent = ev.title;

                var dp = (p.data || '').split('-');
                var dataFmt = dp.length === 3 ? dp[2]+'/'+dp[1]+'/'+dp[0] : (p.data||'');

                document.getElementById('calEventBody').innerHTML =
                    infoRow('bi-door-open',   'Sala',        esc(p.sala)) +
                    infoRow('bi-person',      'Responsável', esc(p.usuario)) +
                    infoRow('bi-clock',       'Horário',     p.horaInicio + ' – ' + p.horaFim) +
                    infoRow('bi-calendar3',   'Data',        dataFmt) +
                    (p.recorrenciaId ? infoRow('bi-arrow-repeat','Tipo','<span style="background:#EDE9FE;color:#5B21B6;font-size:.7rem;font-weight:600;padding:2px 9px;border-radius:20px;display:inline-block;">Recorrente</span>') : '');

                var canEdit = (CURRENT_USER_PERF === 'admin') || (p.usuarioId === CURRENT_USER_ID);
                document.getElementById('btnEditarReserva').classList.toggle('d-none', !canEdit);

                _editing = {id: ev.id, title: ev.title, horaInicio: p.horaInicio, horaFim: p.horaFim, recorrenciaId: p.recorrenciaId};

                document.getElementById('calEventView').classList.remove('d-none');
                document.getElementById('calEventEdit').classList.add('d-none');
                document.getElementById('editError').classList.add('d-none');

                if (!eventModal) eventModal = new bootstrap.Modal(document.getElementById('calEventModal'));
                eventModal.show();
            },
            dateClick: function (info) {
                window.location.href = APP_BASE + '/reservas/nova?data=' + info.dateStr;
            }
        });
        cal.render();

        // ── Edit handlers ─────────────────────────────────────────────
        var _editing   = null;
        var scopeModal = null;
        var _pendingSave = null;

        document.getElementById('btnEditarReserva').addEventListener('click', function () {
            if (!_editing) return;
            document.getElementById('editAssunto').value    = _editing.title;
            document.getElementById('editHoraInicio').value = _editing.horaInicio;
            document.getElementById('editHoraFim').value    = _editing.horaFim;
            document.getElementById('editError').classList.add('d-none');
            document.getElementById('calEventView').classList.add('d-none');
            document.getElementById('calEventEdit').classList.remove('d-none');
        });

        document.getElementById('btnVoltarView').addEventListener('click', function () {
            document.getElementById('calEventView').classList.remove('d-none');
            document.getElementById('calEventEdit').classList.add('d-none');
        });

        document.getElementById('btnSalvarEdit').addEventListener('click', function () {
            if (!_editing) return;
            var assunto    = document.getElementById('editAssunto').value.trim();
            var horaInicio = document.getElementById('editHoraInicio').value;
            var horaFim    = document.getElementById('editHoraFim').value;
            if (!assunto || !horaInicio || !horaFim) { showEditErr('Preencha todos os campos.'); return; }
            if (horaInicio >= horaFim)               { showEditErr('Hora de início deve ser anterior ao fim.'); return; }

            _pendingSave = {assunto: assunto, hora_inicio: horaInicio, hora_fim: horaFim};

            if (_editing.recorrenciaId) {
                if (!scopeModal) scopeModal = new bootstrap.Modal(document.getElementById('calScopeModal'), {backdrop: false});
                scopeModal.show();
            } else {
                doSave('single');
            }
        });

        document.getElementById('btnScopeOnly').addEventListener('click', function () {
            if (scopeModal) scopeModal.hide();
            doSave('single');
        });
        document.getElementById('btnScopeAll').addEventListener('click', function () {
            if (scopeModal) scopeModal.hide();
            doSave('all');
        });

        function doSave(scope) {
            if (!_editing || !_pendingSave) return;
            var btn = document.getElementById('btnSalvarEdit');
            btn.disabled = true;
            fetch(APP_BASE + '/api/events/' + _editing.id + '/update', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
                body: JSON.stringify(Object.assign({}, _pendingSave, {scope: scope}))
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                if (data.error) { showEditErr(data.error); }
                else            { eventModal.hide(); cal.refetchEvents(); }
            })
            .catch(function () { btn.disabled = false; showEditErr('Erro de comunicação. Tente novamente.'); });
        }

        function showEditErr(msg) {
            var el = document.getElementById('editError');
            el.textContent = msg;
            el.classList.remove('d-none');
        }
    }

    function esc(t) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(t));
        return d.innerHTML;
    }

    function infoRow(icon, label, value) {
        return '<div style="display:flex;align-items:center;gap:.7rem;">' +
            '<span style="width:30px;height:30px;background:#F3F4F6;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
            '<i class="bi ' + icon + '" style="font-size:.78rem;color:#6B7280;"></i></span>' +
            '<div style="min-width:0;"><div style="font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9CA3AF;line-height:1;">' + label + '</div>' +
            '<div style="font-size:.8375rem;color:#111827;font-weight:500;margin-top:2px;">' + value + '</div></div>' +
            '</div>';
    }
});
<?php $inlineJs = ob_get_clean(); ?>
