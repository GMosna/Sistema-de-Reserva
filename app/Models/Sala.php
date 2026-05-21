<?php
namespace App\Models;

use App\Services\ConflictChecker;

class Sala extends Model
{
    protected static string $table = 'salas';

    public static function getAtivas(): array
    {
        return static::where("status = 'ativa'", [], 'ORDER BY nome');
    }

    /**
     * True if no conflict exists for the given slot.
     */
    public function isAvailable(string $data, string $horaInicio, string $horaFim): bool
    {
        $result = (new ConflictChecker())->check(
            (int)$this->attributes['id'],
            $data,
            $horaInicio,
            $horaFim
        );
        return !$result['has_conflict'];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->attributes['nome']))        $errors[] = 'Nome da sala é obrigatório.';
        if (($this->attributes['capacidade'] ?? 0) < 1) $errors[] = 'Capacidade deve ser pelo menos 1.';
        return $errors;
    }
}
