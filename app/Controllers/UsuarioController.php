<?php
namespace App\Controllers;

use App\Controller;
use App\Database;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();

        $db         = Database::get();
        $f_pesquisa = trim($_GET['pesquisa'] ?? '');
        $f_perfil   = $_GET['perfil'] ?? '';
        $f_ativo    = $_GET['ativo'] ?? '';

        $where  = '1=1';
        $params = [];
        $types  = '';

        if ($f_pesquisa !== '') {
            $where .= ' AND (nome LIKE ? OR email LIKE ?)';
            $like   = "%{$f_pesquisa}%";
            $params[] = &$like; $params[] = &$like;
            $types .= 'ss';
        }
        if ($f_perfil !== '') {
            $where .= ' AND perfil = ?';
            $params[] = &$f_perfil;
            $types .= 's';
        }
        if ($f_ativo !== '') {
            $where .= ' AND ativo = ?';
            $f_ativo_int = ($f_ativo === '1') ? 1 : 0;
            $params[] = &$f_ativo_int;
            $types .= 'i';
        }

        $stmt = $db->prepare("SELECT * FROM usuarios WHERE {$where} ORDER BY nome ASC");
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $user = $this->currentUser();
        $this->render('pages.usuarios', compact('usuarios', 'user', 'f_pesquisa', 'f_perfil', 'f_ativo'));
    }

    public function store(): void
    {
        $this->requireAdmin();

        $nome      = trim($_POST['userName'] ?? '');
        $email     = trim($_POST['userEmail'] ?? '');
        $perfil    = $_POST['userRole'] ?? 'lider';
        $ativo_val = ($_POST['userStatus'] ?? 'ativo') === 'ativo' ? 1 : 0;
        $senha_raw = $_POST['userPassword'] ?? '';

        if (!in_array($perfil, ['admin', 'lider'], true)) $perfil = 'lider';

        if ($nome === '' || $email === '') {
            flash('error', 'Nome e e-mail são obrigatórios.');
            $this->redirect('/usuarios');
        }
        if ($senha_raw === '') {
            flash('error', 'Palavra-passe é obrigatória para novos usuários.');
            $this->redirect('/usuarios');
        }

        // Check duplicate email
        $check = Usuario::first('email = ?', [$email]);
        if ($check) {
            flash('error', 'Já existe um usuário com este e-mail.');
            $this->redirect('/usuarios');
        }

        $fotoPath  = $this->handleFotoUpload();
        $senhaHash = password_hash($senha_raw, PASSWORD_DEFAULT);

        Usuario::insert([
            'nome'     => $nome,
            'email'    => $email,
            'senha'    => $senhaHash,
            'foto'     => $fotoPath,
            'perfil'   => $perfil,
            'ativo'    => $ativo_val,
        ]);

        flash('success', 'Usuário criado com sucesso.');
        $this->redirect('/usuarios');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();

        $uid       = (int)$id;
        $nome      = trim($_POST['userName'] ?? '');
        $email     = trim($_POST['userEmail'] ?? '');
        $perfil    = $_POST['userRole'] ?? 'lider';
        $ativo_val = ($_POST['userStatus'] ?? 'ativo') === 'ativo' ? 1 : 0;
        $senha_raw = $_POST['userPassword'] ?? '';

        if (!in_array($perfil, ['admin', 'lider'], true)) $perfil = 'lider';

        if ($nome === '' || $email === '') {
            flash('error', 'Nome e e-mail são obrigatórios.');
            $this->redirect('/usuarios');
        }

        $data      = ['nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'ativo' => $ativo_val];
        $fotoPath  = $this->handleFotoUpload();

        if ($fotoPath !== null) {
            // Delete previous photo
            $existing = Usuario::find($uid);
            if ($existing && !empty($existing->foto)) {
                $old = ROOT_PATH . '/' . $existing->foto;
                if (file_exists($old)) @unlink($old);
            }
            $data['foto'] = $fotoPath;
        }
        if ($senha_raw !== '') {
            $data['senha'] = password_hash($senha_raw, PASSWORD_DEFAULT);
        }

        Usuario::update($uid, $data);

        // Refresh session if editing own account
        if ($uid === (int)($_SESSION['usuario_id'] ?? 0)) {
            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;
            if ($fotoPath !== null) {
                $_SESSION['usuario_foto'] = $fotoPath;
            }
        }

        flash('success', 'Usuário atualizado com sucesso.');
        $this->redirect('/usuarios');
    }

    public function toggle(string $id): void
    {
        $this->requireAdmin();

        $uid = (int)$id;

        if ($uid === (int)($_SESSION['usuario_id'] ?? 0)) {
            flash('error', 'Não pode alterar o estado da sua própria conta.');
            $this->redirect('/usuarios');
        }

        $user = Usuario::find($uid);
        if (!$user) {
            flash('error', 'Usuário não encontrado.');
            $this->redirect('/usuarios');
        }

        $novoEstado = $user->ativo ? 0 : 1;
        Usuario::update($uid, ['ativo' => $novoEstado]);

        flash('success', $novoEstado ? 'Usuário ativado.' : 'Usuário desativado.');
        $this->redirect('/usuarios');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function handleFotoUpload(): ?string
    {
        $file = $_FILES['userFoto'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erro no upload da foto.');
            $this->redirect('/usuarios');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            flash('error', 'Foto deve ter no máximo 5MB.');
            $this->redirect('/usuarios');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime    = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            flash('error', 'Tipo não permitido. Use JPG, PNG ou WebP.');
            $this->redirect('/usuarios');
        }

        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
        $name = uniqid('user_', true) . '.' . $ext;
        $dir  = ROOT_PATH . '/public/uploads/usuarios/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            flash('error', 'Falha ao guardar a foto.');
            $this->redirect('/usuarios');
        }

        return 'public/uploads/usuarios/' . $name;
    }
}
