<?php
namespace App\Services;

use App\Database;

class RecurrenceGenerator
{
    private \mysqli $db;
    private ConflictChecker $checker;

    public function __construct()
    {
        $this->db      = Database::get();
        $this->checker = new ConflictChecker();
    }

    /**
     * Generate individual reserva rows for an already-inserted recorrencia.
     * Skips dates with conflicts; never inserts past today.
     *
     * @return array{created: int, skipped: string[], recorrencia_id: int}
     */
    public function generate(int $recorrenciaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM recorrencias WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $recorrenciaId);
        $stmt->execute();
        $rc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rc) {
            throw new \InvalidArgumentException("Recorrência #{$recorrenciaId} não encontrada.");
        }

        $dates  = $this->buildDates($rc['data_inicio'], $rc['data_fim'], $rc['tipo']);
        $ins    = $this->db->prepare(
            "INSERT INTO reservas
                (sala_id, usuario_id, assunto, data_reserva, hora_inicio, hora_fim, status, recorrencia_id)
             VALUES (?, ?, ?, ?, ?, ?, 'confirmada', ?)"
        );

        $created       = 0;
        $skippedDates  = [];

        foreach ($dates as $date) {
            $conflict = $this->checker->check(
                (int)$rc['sala_id'],
                $date,
                $rc['hora_inicio'],
                $rc['hora_fim'],
                null,
                $recorrenciaId
            );

            if ($conflict['has_conflict']) {
                $skippedDates[] = date('d/m/Y', strtotime($date));
                continue;
            }

            $ins->bind_param(
                'iissssi',
                $rc['sala_id'],
                $rc['usuario_id'],
                $rc['assunto'],
                $date,
                $rc['hora_inicio'],
                $rc['hora_fim'],
                $recorrenciaId
            );

            if (!$ins->execute()) {
                throw new \RuntimeException('Erro ao inserir reserva recorrente: ' . $ins->error);
            }

            $created++;
        }

        $ins->close();

        return [
            'recorrencia_id' => $recorrenciaId,
            'created'        => $created,
            'skipped'        => $skippedDates,
        ];
    }

    // ── Date generation ───────────────────────────────────────────────

    private function buildDates(string $start, string $end, string $tipo): array
    {
        $dates   = [];
        $current = new \DateTime($start);
        $endDt   = new \DateTime($end);
        $limit   = (new \DateTime($start))->modify('+6 months');

        if ($endDt > $limit) {
            $endDt = $limit;
        }

        while ($current <= $endDt) {
            $dates[] = $current->format('Y-m-d');
            match ($tipo) {
                'semanal' => $current->modify('+1 week'),
                'mensal'  => $current->modify('+1 month'),
                default   => $current->modify('+1 week'),
            };
        }

        return $dates;
    }
}
