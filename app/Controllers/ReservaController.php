<?php
namespace App\Controllers;

use App\Controller;
use App\Database;
use App\Models\Sala;
use App\Services\ConflictChecker;
use App\Services\RecurrenceGenerator;

class ReservaController extends Controller
{
    public function index(): void
    {
        $db   = Database::get();
        $user = $this->currentUser();

        $hasFilters = array_key_exists('pesquisa', $_GET) || array_key_exists('data_ini', $_GET)
                   || array_key_exists('data_fim', $_GET) || array_key_exists('sala', $_GET)
                   || array_key_exists('estado', $_GET);

        $f_pesquisa = trim($_GET['pesquisa'] ?? '');
        $f_data_ini = $hasFilters ? ($_GET['data_ini'] ?? '') : date('Y-m-d');
        $f_data_fim = $_GET['data_fim'] ?? '';
        $f_sala     = $_GET['sala'] ?? '';
        $f_status   = $_GET['estado'] ?? '';

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $where  = '1=1';
        $params = [];
        $types  = '';

        if ($f_pesquisa !== '') {
            $where .= ' AND (r.assunto LIKE ? OR u.nome LIKE ? OR s.nome LIKE ?)';
            $like   = "%{$f_pesquisa}%";
            $params[] = &$like; $params[] = &$like; $params[] = &$like;
            $types .= 'sss';
        }
        if ($f_data_ini !== '') {
            $where .= ' AND r.data_reserva >= ?';
            $params[] = &$f_data_ini;
            $types .= 's';
        }
        if ($f_data_fim !== '') {
            $where .= ' AND r.data_reserva <= ?';
            $params[] = &$f_data_fim;
            $types .= 's';
        }
        if ($f_sala !== '') {
            $where .= ' AND r.sala_id = ?';
            $f_sala_int = (int)$f_sala;
            $params[] = &$f_sala_int;
            $types .= 'i';
        }
        if ($f_status !== '') {
            $where .= ' AND r.status = ?';
            $params[] = &$f_status;
            $types .= 's';
        }

        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM reservas r JOIN salas s ON s.id = r.sala_id JOIN usuarios u ON u.id = r.usuario_id WHERE {$where}");
        if ($types !== '') $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $total      = (int)$countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
        $totalPages = max(1, (int)ceil($total / $limit));

        $dataStmt = $db->prepare("SELECT r.*, s.nome AS sala_nome, u.nome AS usuario_nome FROM reservas r JOIN salas s ON s.id = r.sala_id JOIN usuarios u ON u.id = r.usuario_id WHERE {$where} ORDER BY r.data_reserva ASC, r.hora_inicio ASC LIMIT {$limit} OFFSET {$offset}");
        if ($types !== '') $dataStmt->bind_param($types, ...$params);
        $dataStmt->execute();
        $reservas = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $dataStmt->close();

        $salasFilter = Database::get()->query("SELECT id, nome FROM salas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

        $this->render('pages.reservas', compact(
            'user', 'reservas', 'salasFilter', 'total', 'totalPages', 'page',
            'f_pesquisa', 'f_data_ini', 'f_data_fim', 'f_sala', 'f_status'
        ));
    }

    public function create(): void
    {
        $salas = Sala::getAtivas();
        $user  = $this->currentUser();
        $this->render('pages.nova_reserva', compact('salas', 'user'));
    }

    public function store(): void
    {
        $user        = $this->currentUser();
        $assunto     = trim($_POST['titulo'] ?? '');
        $sala_id     = (int)($_POST['sala'] ?? 0);
        $data        = $_POST['data'] ?? '';
        $hora_inicio = $_POST['horaInicio'] ?? '';
        $hora_fim    = $_POST['horaFim'] ?? '';
        $recorrente  = isset($_POST['recorrente']);
        $tipo        = $_POST['frequencia'] ?? 'semanal';
        if (!in_array($tipo, ['semanal', 'mensal'], true)) $tipo = 'semanal';

        $erros = [];
        if ($assunto === '')   $erros[] = 'Assunto é obrigatório.';
        if ($sala_id <= 0)    $erros[] = 'Selecione uma sala.';
        if ($data === '')     $erros[] = 'Data é obrigatória.';
        if ($hora_inicio === '' || $hora_fim === '') $erros[] = 'Hora início e fim são obrigatórias.';
        if ($hora_inicio !== '' && $hora_fim !== '' && $hora_fim <= $hora_inicio) $erros[] = 'Hora fim deve ser maior que hora início.';
        if ($data !== '' && $data < date('Y-m-d')) $erros[] = 'Não é possível reservar no passado.';

        if (empty($erros) && $sala_id > 0) {
            $sala = Sala::find($sala_id);
            if (!$sala || $sala->status !== 'ativa') {
                $erros[] = 'A sala selecionada não está ativa.';
            }
        }

        if (empty($erros)) {
            $conflict = (new ConflictChecker())->check($sala_id, $data, $hora_inicio, $hora_fim);
            if ($conflict['has_conflict']) {
                $erros[] = $conflict['message'];
            }
        }

        if (!empty($erros)) {
            flash('error', implode(' ', $erros));
            $this->redirect('/reservas/nova');
        }

        $db = Database::get();

        if ($recorrente) {
            $dia_semana = (int)date('w', strtotime($data));
            $dia_mes    = (int)date('j', strtotime($data));
            $fim        = (new \DateTime($data))->modify('+6 months')->format('Y-m-d');

            $stmt = $db->prepare("INSERT INTO recorrencias (sala_id, usuario_id, assunto, tipo, dia_semana, dia_mes, hora_inicio, hora_fim, data_inicio, data_fim, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('iissiissss', $sala_id, $user['id'], $assunto, $tipo, $dia_semana, $dia_mes, $hora_inicio, $hora_fim, $data, $fim);
            if (!$stmt->execute()) {
                flash('error', 'Erro ao criar recorrência: ' . $stmt->error);
                $this->redirect('/reservas/nova');
            }
            $recorrencia_id = $db->insert_id;
            $stmt->close();

            $result = (new RecurrenceGenerator())->generate($recorrencia_id);

            if (!empty($result['skipped'])) {
                flash('warning', "Recorrência criada com {$result['created']} reservas. Conflitos ignorados: " . implode(', ', $result['skipped']));
            } else {
                flash('success', "Recorrência criada com {$result['created']} reservas.");
            }
            $this->redirect('/recorrencias');
        } else {
            $stmt = $db->prepare("INSERT INTO reservas (sala_id, usuario_id, assunto, data_reserva, hora_inicio, hora_fim, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmada')");
            $stmt->bind_param('iissss', $sala_id, $user['id'], $assunto, $data, $hora_inicio, $hora_fim);
            if (!$stmt->execute()) {
                flash('error', 'Erro ao criar reserva: ' . $stmt->error);
                $this->redirect('/reservas/nova');
            }
            $stmt->close();
            flash('success', 'Reserva criada com sucesso!');
            $this->redirect('/reservas');
        }
    }

    public function cancel(string $id): void
    {
        $rid  = (int)$id;
        $user = $this->currentUser();
        $db   = Database::get();

        $stmt = $db->prepare("SELECT usuario_id FROM reservas WHERE id = ?");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            flash('error', 'Reserva não encontrada.');
            $this->redirect('/reservas');
        }

        if ($user['perfil'] !== 'admin' && (int)$row['usuario_id'] !== (int)$user['id']) {
            flash('error', 'Sem permissão para cancelar esta reserva.');
            $this->redirect('/reservas');
        }

        $stmt = $db->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ? AND status != 'cancelada'");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        flash($affected > 0 ? 'success' : 'error', $affected > 0 ? 'Reserva cancelada com sucesso.' : 'Reserva já se encontra cancelada.');
        $this->redirect('/reservas');
    }

    public function confirm(string $id): void
    {
        $this->requireAdmin();
        $rid = (int)$id;
        $db  = Database::get();

        $stmt = $db->prepare("UPDATE reservas SET status = 'confirmada' WHERE id = ?");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Reserva confirmada.');
        $this->redirect('/reservas');
    }

    public function destroy(string $id): void
    {
        $rid  = (int)$id;
        $user = $this->currentUser();
        $db   = Database::get();

        $stmt = $db->prepare("SELECT usuario_id, status FROM reservas WHERE id = ?");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            flash('error', 'Reserva não encontrada.');
        } elseif ($row['status'] !== 'cancelada') {
            flash('error', 'Só é possível excluir reservas canceladas.');
        } elseif ($user['perfil'] !== 'admin' && (int)$row['usuario_id'] !== (int)$user['id']) {
            flash('error', 'Sem permissão para excluir esta reserva.');
        } else {
            $stmt = $db->prepare("DELETE FROM reservas WHERE id = ? AND status = 'cancelada'");
            $stmt->bind_param('i', $rid);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Reserva excluída permanentemente.');
        }

        $this->redirect('/reservas');
    }
}
