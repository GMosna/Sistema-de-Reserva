<?php
namespace App\Controllers;

use App\Controller;
use App\Models\Sala;

class SalaController extends Controller
{
    public function index(): void
    {
        $salas = Sala::all('nome ASC');
        $user  = $this->currentUser();
        $this->render('pages.salas', compact('salas', 'user'));
    }

    public function update(string $id): void
    {
        $this->requireAdmin();

        $rid        = (int)$id;
        $nome       = trim($_POST['roomName'] ?? '');
        $capacidade = (int)($_POST['roomCapacity'] ?? 0);

        if ($rid <= 0) {
            flash('error', 'Operação não permitida.');
        } elseif ($nome === '' || $capacidade < 1) {
            flash('error', 'Nome e capacidade são obrigatórios.');
        } else {
            Sala::update($rid, ['nome' => $nome, 'capacidade' => $capacidade]);
            flash('success', 'Sala atualizada com sucesso.');
        }

        $this->redirect('/salas');
    }
}
