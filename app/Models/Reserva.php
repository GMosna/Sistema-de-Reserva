<?php
namespace App\Models;

use App\Services\ConflictChecker;

class Reserva extends Model
{
    protected static string $table = 'reservas';

    /**
     * Delegate to ConflictChecker.
     *
     * @return array{has_conflict: bool, conflicts: int, message: string}
     */
    public function checkConflict(
        string $data,
        string $horaInicio,
        string $horaFim,
        ?int $excludeId = null
    ): array {
        return (new ConflictChecker())->check(
            (int)$this->attributes['sala_id'],
            $data,
            $horaInicio,
            $horaFim,
            $excludeId
        );
    }

    /**
     * Reservations overlapping the given date range.
     */
    public static function getByPeriod(string $start, string $end): array
    {
        return static::where(
            "data_reserva BETWEEN ? AND ? AND status != 'cancelada'",
            [$start, $end],
            'ORDER BY data_reserva, hora_inicio'
        );
    }

    public function cancel(): bool
    {
        $id = (int)$this->attributes['id'];
        if ($this->attributes['status'] === 'cancelada') {
            return false;
        }
        $ok = static::update($id, ['status' => 'cancelada']);
        if ($ok) {
            $this->attributes['status'] = 'cancelada';
        }
        return $ok;
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->attributes['assunto']))     $errors[] = 'Assunto é obrigatório.';
        if (empty($this->attributes['sala_id']))     $errors[] = 'Sala é obrigatória.';
        if (empty($this->attributes['data_reserva'])) $errors[] = 'Data é obrigatória.';
        if (empty($this->attributes['hora_inicio'])) $errors[] = 'Hora início é obrigatória.';
        if (empty($this->attributes['hora_fim']))    $errors[] = 'Hora fim é obrigatória.';
        if (!empty($this->attributes['hora_inicio']) && !empty($this->attributes['hora_fim'])
            && $this->attributes['hora_fim'] <= $this->attributes['hora_inicio']) {
            $errors[] = 'Hora fim deve ser maior que hora início.';
        }
        return $errors;
    }
}
