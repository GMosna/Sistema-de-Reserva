<?php
namespace App\Controllers;

use App\Controller;
use App\Database;
use App\Models\Recorrencia;

class RecorrenciaController extends Controller
{
    public function index(): void
    {
        $db   = Database::get();
        $user = $this->currentUser();

        $f_pesquisa = trim($_GET['pesquisa'] ?? '');
        $f_tipo     = $_GET['frequencia'] ?? '';
        $f_sala     = $_GET['sala'] ?? '';
        $f_ativa    = $_GET['estado'] ?? '';

        $where  = '1=1';
        $params = [];
        $types  = '';

        if ($f_pesquisa !== '') {
            $where .= ' AND (rc.assunto LIKE ? OR u.nome LIKE ? OR s.nome LIKE ?)';
            $like   = "%{$f_pesquisa}%";
            $params[] = &$like; $params[] = &$like; $params[] = &$like;
            $types .= 'sss';
        }
        if ($f_tipo !== '') {
            $where .= ' AND rc.tipo = ?';
            $params[] = &$f_tipo;
            $types .= 's';
        }
        if ($f_sala !== '') {
            $where .= ' AND rc.sala_id = ?';
            $f_sala_int = (int)$f_sala;
            $params[] = &$f_sala_int;
            $types .= 'i';
        }
        if ($f_ativa !== '') {
            $where .= ' AND rc.ativa = ?';
            $f_ativa_int = ($f_ativa === 'ativa') ? 1 : 0;
            $params[] = &$f_ativa_int;
            $types .= 'i';
        }

        $stmt = $db->prepare("SELECT rc.*, s.nome AS sala_nome, u.nome AS usuario_nome FROM recorrencias rc JOIN salas s ON s.id = rc.sala_id JOIN usuarios u ON u.id = rc.usuario_id WHERE {$where} ORDER BY rc.ativa DESC, rc.data_inicio DESC");
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $recorrencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $salasFilter = $db->query("SELECT id, nome FROM salas ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

        $this->render('pages.recorrencias', compact(
            'user', 'recorrencias', 'salasFilter',
            'f_pesquisa', 'f_tipo', 'f_sala', 'f_ativa'
        ));
    }

    public function toggle(string $id): void
    {
        $rc = $this->loadOwned((int)$id);
        if ($rc) {
            if ($rc->ativa) {
                $rc->pause();
                flash('success', 'Recorrência pausada.');
            } else {
                $rc->resume();
                flash('success', 'Recorrência retomada.');
            }
        }
        $this->redirect('/recorrencias');
    }

    public function destroy(string $id): void
    {
        $rc = $this->loadOwned((int)$id);
        if ($rc) {
            $rc->finalize();
            flash('success', 'Recorrência finalizada e reservas futuras canceladas.');
        }
        $this->redirect('/recorrencias');
    }

    private function loadOwned(int $id): ?Recorrencia
    {
        $rc   = Recorrencia::find($id);
        $user = $this->currentUser();

        if (!$rc) {
            flash('error', 'Recorrência não encontrada.');
            return null;
        }

        if ($user['perfil'] !== 'admin' && (int)$rc->usuario_id !== (int)$user['id']) {
            flash('error', 'Sem permissão para alterar esta recorrência.');
            return null;
        }

        return $rc;
    }
}
