<?php
namespace App\Controllers;

use App\Controller;
use App\Database;

class ApiController extends Controller
{
    // ── FullCalendar events ────────────────────────────────────────────
    // GET /api/events?start=YYYY-MM-DD&end=YYYY-MM-DD
    public function events(): void
    {
        $db    = Database::get();
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-t');

        // Strip FullCalendar ISO timestamps to plain dates
        $start = substr($start, 0, 10);
        $end   = substr($end,   0, 10);

        $stmt = $db->prepare("
            SELECT r.id, r.assunto, r.data_reserva, r.hora_inicio, r.hora_fim,
                   r.status, r.sala_id, r.usuario_id, r.recorrencia_id,
                   s.nome AS sala_nome, u.nome AS usuario_nome
            FROM reservas r
            JOIN salas    s ON s.id = r.sala_id
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.data_reserva BETWEEN ? AND ?
            ORDER BY r.data_reserva, r.hora_inicio
        ");
        $stmt->bind_param('ss', $start, $end);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $events = array_map(function (array $r): array {
            return [
                'id'         => $r['id'],
                'title'      => $r['assunto'],
                'start'      => $r['data_reserva'] . 'T' . $r['hora_inicio'],
                'end'        => $r['data_reserva'] . 'T' . $r['hora_fim'],
                'classNames' => ['status-' . $r['status']],
                'extendedProps' => [
                    'sala'          => $r['sala_nome'],
                    'usuario'       => $r['usuario_nome'],
                    'usuarioId'     => (int)$r['usuario_id'],
                    'status'        => $r['status'],
                    'horaInicio'    => substr($r['hora_inicio'], 0, 5),
                    'horaFim'       => substr($r['hora_fim'],    0, 5),
                    'data'          => $r['data_reserva'],
                    'recorrenciaId' => $r['recorrencia_id'] ? (int)$r['recorrencia_id'] : null,
                ],
            ];
        }, $rows);

        $this->json($events);
    }

    // ── Update event (title + time) from calendar ─────────────────────
    // POST /api/events/{id}/update  body: {assunto, hora_inicio, hora_fim, scope}
    public function updateEvent(string $id): void
    {
        $rid  = (int)$id;
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $assunto    = trim($body['assunto']     ?? '');
        $horaInicio = trim($body['hora_inicio'] ?? '');
        $horaFim    = trim($body['hora_fim']    ?? '');
        $scope      = $body['scope'] ?? 'single';

        if (!$assunto || !$horaInicio || !$horaFim) {
            $this->json(['error' => 'Campos obrigatórios em falta.'], 422); return;
        }
        if ($horaInicio >= $horaFim) {
            $this->json(['error' => 'Hora de início deve ser anterior ao fim.'], 422); return;
        }

        $db   = Database::get();
        $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $this->json(['error' => 'Reserva não encontrada.'], 404); return;
        }

        $user = $this->currentUser();
        if ($user['perfil'] !== 'admin' && (int)$row['usuario_id'] !== (int)$user['id']) {
            $this->json(['error' => 'Sem permissão para editar esta reserva.'], 403); return;
        }

        $excludeRcId = ($scope === 'all' && $row['recorrencia_id']) ? (int)$row['recorrencia_id'] : null;
        $checker     = new \App\Services\ConflictChecker();
        $result      = $checker->check((int)$row['sala_id'], $row['data_reserva'], $horaInicio, $horaFim, $rid, $excludeRcId);
        if ($result['has_conflict']) {
            $this->json(['error' => 'Conflito de horário com outra reserva nesta sala.'], 409); return;
        }

        if ($scope === 'all' && $row['recorrencia_id']) {
            $rcId = (int)$row['recorrencia_id'];
            $s1   = $db->prepare("UPDATE reservas    SET assunto=?, hora_inicio=?, hora_fim=? WHERE recorrencia_id=?");
            $s1->bind_param('sssi', $assunto, $horaInicio, $horaFim, $rcId); $s1->execute(); $s1->close();
            $s2   = $db->prepare("UPDATE recorrencias SET assunto=?, hora_inicio=?, hora_fim=? WHERE id=?");
            $s2->bind_param('sssi', $assunto, $horaInicio, $horaFim, $rcId); $s2->execute(); $s2->close();
        } else {
            $s = $db->prepare("UPDATE reservas SET assunto=?, hora_inicio=?, hora_fim=? WHERE id=?");
            $s->bind_param('sssi', $assunto, $horaInicio, $horaFim, $rid); $s->execute(); $s->close();
        }

        $this->json(['success' => true]);
    }

    // ── Chart: reservas por período (line) ────────────────────────────
    // GET /api/charts/periodo?days=30
    public function chartPeriodo(): void
    {
        $days = max(7, min(90, (int)($_GET['days'] ?? 30)));
        $db   = Database::get();

        $stmt = $db->prepare("
            SELECT data_reserva, COUNT(*) AS total
            FROM reservas
            WHERE data_reserva >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
              AND status != 'cancelada'
            GROUP BY data_reserva
            ORDER BY data_reserva
        ");
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fill zeros for days with no reservations
        $map = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $map[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }
        foreach ($rows as $r) {
            $map[$r['data_reserva']] = (int)$r['total'];
        }

        $this->json([
            'labels' => array_map(fn($d) => date('d/m', strtotime($d)), array_keys($map)),
            'data'   => array_values($map),
        ]);
    }

    // ── Chart: salas mais usadas (horizontal bar) ─────────────────────
    // GET /api/charts/salas
    public function chartSalas(): void
    {
        $db  = Database::get();
        $res = $db->query("
            SELECT s.nome, COUNT(*) AS total
            FROM reservas r
            JOIN salas s ON s.id = r.sala_id
            WHERE r.status != 'cancelada'
              AND r.data_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY s.id, s.nome
            ORDER BY total DESC
            LIMIT 10
        ");
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        $this->json([
            'labels' => array_column($rows, 'nome'),
            'data'   => array_map(fn($r) => (int)$r['total'], $rows),
        ]);
    }

    // ── Chart: taxa de ocupação (donut) ───────────────────────────────
    // GET /api/charts/ocupacao
    public function chartOcupacao(): void
    {
        $db  = Database::get();
        $res = $db->query("
            SELECT status, COUNT(*) AS total
            FROM reservas
            WHERE data_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY status
        ");
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        $map = ['confirmada' => 0, 'pendente' => 0, 'cancelada' => 0];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['total'];
        }

        $this->json([
            'labels' => ['Confirmadas', 'Pendentes', 'Canceladas'],
            'data'   => array_values($map),
            'colors' => ['#10B981', '#F59E0B', '#EF4444'],
        ]);
    }
}
