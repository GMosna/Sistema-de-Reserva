<?php
namespace App\Models;

use App\Services\RecurrenceGenerator;

class Recorrencia extends Model
{
    protected static string $table = 'recorrencias';

    /**
     * Generate individual reserva rows for this recorrencia.
     * Delegates to RecurrenceGenerator.
     *
     * @return array{recorrencia_id: int, created: int, skipped: string[]}
     */
    public function generateOccurrences(): array
    {
        return (new RecurrenceGenerator())->generate((int)$this->attributes['id']);
    }

    public function pause(): bool
    {
        $ok = static::update((int)$this->attributes['id'], ['ativa' => 0]);
        if ($ok) $this->attributes['ativa'] = 0;
        return $ok;
    }

    public function resume(): bool
    {
        $ok = static::update((int)$this->attributes['id'], ['ativa' => 1]);
        if ($ok) $this->attributes['ativa'] = 1;
        return $ok;
    }

    /**
     * Deactivate recorrencia + cancel all future materialized reservations.
     */
    public function finalize(): bool
    {
        $id   = (int)$this->attributes['id'];
        $hoje = date('Y-m-d');
        $db   = static::db();

        $stmt = $db->prepare(
            "UPDATE reservas SET status = 'cancelada'
             WHERE recorrencia_id = ? AND data_reserva >= ? AND status != 'cancelada'"
        );
        $stmt->bind_param('is', $id, $hoje);
        $stmt->execute();
        $stmt->close();

        $ok = static::update($id, ['ativa' => 0]);
        if ($ok) $this->attributes['ativa'] = 0;
        return $ok;
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->attributes['assunto']))    $errors[] = 'Assunto é obrigatório.';
        if (empty($this->attributes['sala_id']))    $errors[] = 'Sala é obrigatória.';
        if (!in_array($this->attributes['tipo'] ?? '', ['semanal', 'mensal'], true)) {
            $errors[] = 'Tipo deve ser semanal ou mensal.';
        }
        return $errors;
    }
}
