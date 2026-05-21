<?php
namespace App\Services;

use App\Database;

class ConflictChecker
{
    private \mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Full conflict check: materialized reservations + active recurrence patterns.
     *
     * @return array{has_conflict: bool, conflicts: int, message: string}
     */
    public function check(
        int $salaId,
        string $data,
        string $horaInicio,
        string $horaFim,
        ?int $excludeReservaId = null,
        ?int $excludeRecorrenciaId = null
    ): array {
        $c1 = $this->checkReservas($salaId, $data, $horaInicio, $horaFim, $excludeReservaId);
        $c2 = $this->checkRecorrencias($salaId, $data, $horaInicio, $horaFim, $excludeRecorrenciaId);

        $total = $c1 + $c2;
        return [
            'has_conflict' => $total > 0,
            'conflicts'    => $total,
            'message'      => $total > 0
                ? "Conflito de horário: já existe reserva ou recorrência ativa para essa sala em {$data}."
                : '',
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function checkReservas(
        int $salaId, string $data, string $horaInicio, string $horaFim,
        ?int $excludeId
    ): int {
        $sql = "SELECT COUNT(*) AS total
                FROM reservas
                WHERE sala_id     = ?
                  AND data_reserva = ?
                  AND status      != 'cancelada'
                  AND hora_inicio  < ?
                  AND hora_fim     > ?";

        if ($excludeId !== null) {
            $sql  .= " AND id != ?";
            $stmt  = $this->db->prepare($sql);
            $stmt->bind_param('isssi', $salaId, $data, $horaFim, $horaInicio, $excludeId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isss', $salaId, $data, $horaFim, $horaInicio);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }

    private function checkRecorrencias(
        int $salaId, string $data, string $horaInicio, string $horaFim,
        ?int $excludeRecorrenciaId
    ): int {
        $diaSemana = (int)date('w', strtotime($data));
        $diaMes    = (int)date('j', strtotime($data));

        $sql = "SELECT COUNT(*) AS total
                FROM recorrencias rc
                WHERE rc.sala_id     = ?
                  AND rc.ativa       = 1
                  AND ?              BETWEEN rc.data_inicio AND rc.data_fim
                  AND rc.hora_inicio < ?
                  AND rc.hora_fim    > ?
                  AND (
                      (rc.tipo = 'semanal' AND rc.dia_semana = ?)
                   OR (rc.tipo = 'mensal'  AND rc.dia_mes    = ?)
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM reservas r
                      WHERE r.recorrencia_id = rc.id
                        AND r.data_reserva   = ?
                  )";

        if ($excludeRecorrenciaId !== null) {
            $sql  .= " AND rc.id != ?";
            $stmt  = $this->db->prepare($sql);
            $stmt->bind_param('isssiisi', $salaId, $data, $horaFim, $horaInicio,
                              $diaSemana, $diaMes, $data, $excludeRecorrenciaId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isssiis', $salaId, $data, $horaFim, $horaInicio,
                              $diaSemana, $diaMes, $data);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }
}
