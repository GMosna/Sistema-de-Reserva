<?php
namespace App\Controllers;

use App\Controller;
use App\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db   = Database::get();
        $user = $this->currentUser();
        $hoje = date('Y-m-d');

        // ── KPIs ──────────────────────────────────────────────────────
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM reservas WHERE data_reserva = ? AND status != 'cancelada'");
        $stmt->bind_param('s', $hoje);
        $stmt->execute();
        $reservasHoje = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $salasAtivas = (int)$db->query("SELECT COUNT(*) AS c FROM salas WHERE status = 'ativa'")->fetch_assoc()['c'];
        $usersAtivos = (int)$db->query("SELECT COUNT(*) AS c FROM usuarios WHERE ativo = 1")->fetch_assoc()['c'];

        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM reservas WHERE status = 'pendente' AND data_reserva >= ?");
        $stmt->bind_param('s', $hoje);
        $stmt->execute();
        $pendentes = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // ── Calendar ──────────────────────────────────────────────────
        $calMes = max(1, min(12, (int)($_GET['cal_mes'] ?? date('n'))));
        $calAno = max(2020, min(2099, (int)($_GET['cal_ano'] ?? date('Y'))));

        $calPrimeiroDia = mktime(0, 0, 0, $calMes, 1, $calAno);
        $calDiasNoMes   = (int)date('t', $calPrimeiroDia);
        $calDiaSemIni   = (int)date('w', $calPrimeiroDia);

        $calMesAnterior = $calMes - 1;
        $calAnoAnterior = $calAno;
        if ($calMesAnterior < 1) { $calMesAnterior = 12; $calAnoAnterior--; }

        $calMesSeguinte = $calMes + 1;
        $calAnoSeguinte = $calAno;
        if ($calMesSeguinte > 12) { $calMesSeguinte = 1; $calAnoSeguinte++; }

        $calDataIni = sprintf('%04d-%02d-01', $calAno, $calMes);
        $calDataFim = sprintf('%04d-%02d-%02d', $calAno, $calMes, $calDiasNoMes);

        $stmt = $db->prepare("
            SELECT r.data_reserva, r.hora_inicio, r.hora_fim, r.assunto, r.status,
                   s.nome AS sala_nome, u.nome AS usuario_nome
            FROM reservas r
            JOIN salas s ON s.id = r.sala_id
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.data_reserva >= ? AND r.data_reserva <= ?
            ORDER BY r.hora_inicio ASC
        ");
        $stmt->bind_param('ss', $calDataIni, $calDataFim);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $calReservas = [];
        foreach ($rows as $cr) {
            $calReservas[$cr['data_reserva']][] = $cr;
        }

        // ── Today's lists ─────────────────────────────────────────────
        $stmt = $db->prepare("
            SELECT r.hora_inicio, r.hora_fim, r.assunto, r.status,
                   s.nome AS sala_nome, u.nome AS usuario_nome
            FROM reservas r
            JOIN salas s ON s.id = r.sala_id
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.data_reserva = ? AND r.status != 'cancelada' AND r.recorrencia_id IS NULL
            ORDER BY r.hora_inicio ASC
        ");
        $stmt->bind_param('s', $hoje);
        $stmt->execute();
        $normais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $db->prepare("
            SELECT r.hora_inicio, r.hora_fim, r.assunto, r.status,
                   s.nome AS sala_nome, u.nome AS usuario_nome, rc.tipo
            FROM reservas r
            JOIN salas s ON s.id = r.sala_id
            JOIN usuarios u ON u.id = r.usuario_id
            JOIN recorrencias rc ON rc.id = r.recorrencia_id
            WHERE r.data_reserva = ? AND r.status != 'cancelada' AND r.recorrencia_id IS NOT NULL
            ORDER BY r.hora_inicio ASC
        ");
        $stmt->bind_param('s', $hoje);
        $stmt->execute();
        $recorrencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->render('pages.dashboard', compact(
            'user', 'hoje',
            'reservasHoje', 'salasAtivas', 'usersAtivos', 'pendentes',
            'calMes', 'calAno', 'calDiasNoMes', 'calDiaSemIni',
            'calMesAnterior', 'calAnoAnterior', 'calMesSeguinte', 'calAnoSeguinte',
            'calReservas', 'normais', 'recorrencias'
        ));
    }
}
