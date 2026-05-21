<?php
namespace App\Models;

class Usuario extends Model
{
    protected static string $table = 'usuarios';

    // ── Auth ──────────────────────────────────────────────────────────

    /**
     * Verify credentials. Returns hydrated instance or null.
     */
    public static function authenticate(string $email, string $senha): ?static
    {
        $user = static::first('email = ? AND ativo = 1', [$email]);
        if (!$user || !password_verify($senha, $user->senha)) {
            return null;
        }
        return $user;
    }

    public function hasPermission(string $role): bool
    {
        return ($this->attributes['perfil'] ?? '') === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('admin');
    }

    // ── Photo upload ──────────────────────────────────────────────────

    /**
     * Process a photo upload. Returns new path or throws on error.
     *
     * @param  array $file  $_FILES element
     * @return string  Relative path stored in DB (e.g. public/uploads/usuarios/xyz.jpg)
     */
    public function updateFoto(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erro no upload da foto.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException('Foto deve ter no máximo 5MB.');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime    = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Tipo não permitido. Use JPG, PNG ou WebP.');
        }

        $ext      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
        $filename = uniqid('user_', true) . '.' . $ext;
        $dir      = ROOT_PATH . '/public/uploads/usuarios/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            throw new \RuntimeException('Falha ao guardar a foto.');
        }

        // Delete previous photo
        if (!empty($this->attributes['foto'])) {
            $old = ROOT_PATH . '/' . $this->attributes['foto'];
            if (file_exists($old)) {
                @unlink($old);
            }
        }

        $path = 'public/uploads/usuarios/' . $filename;
        static::update((int)$this->attributes['id'], ['foto' => $path]);
        $this->attributes['foto'] = $path;

        return $path;
    }

    // ── Validation ────────────────────────────────────────────────────

    public function validate(): array
    {
        $errors = [];
        if (empty($this->attributes['nome']))  $errors[] = 'Nome é obrigatório.';
        if (empty($this->attributes['email'])) $errors[] = 'Email é obrigatório.';
        if (!filter_var($this->attributes['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        return $errors;
    }
}
