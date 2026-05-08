<?php
/**
 * Centralized conflict-checking functions for room reservations.
 *
 * Overlap rule (strict inequalities allow "touching" intervals):
 *   existing.hora_inicio < new.hora_fim  AND  existing.hora_fim > new.hora_inicio
 *
 * Requires $conn (mysqli) to be available in the calling scope.
 */

/**
 * Check materialized reservations in the `reservas` table.
 *
 * @param int|null $exclude_reserva_id  Reservation ID to exclude (useful for edits).
 * @return int  Number of conflicting reservations.
 */
function checkReservaConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim, $exclude_reserva_id = null) {
    $sql = "SELECT COUNT(*) AS total
            FROM reservas
            WHERE sala_id = ?
              AND data_reserva = ?
              AND status != 'cancelada'
              AND hora_inicio < ?
              AND hora_fim > ?";

    if ($exclude_reserva_id !== null) {
        $sql .= " AND id != ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die('SQL error (checkReservaConflict): ' . $conn->error);
        $stmt->bind_param('isssi', $sala_id, $data, $hora_fim, $hora_inicio, $exclude_reserva_id);
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt) die('SQL error (checkReservaConflict): ' . $conn->error);
        $stmt->bind_param('isss', $sala_id, $data, $hora_fim, $hora_inicio);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['total'];
}

/**
 * Check active recurrence PATTERNS whose occurrences have NOT been
 * materialized as rows in `reservas` for the given date.
 *
 * This protects slots that "belong" to an active recurrence even when the
 * individual reservation row does not yet exist (e.g. beyond the 6-month
 * generation window, or after a pause + resume cycle).
 *
 * If a materialized row already exists for the date (regardless of status),
 * the pattern is skipped here — `checkReservaConflict` is the authority
 * for materialized rows.
 *
 * @param int|null $exclude_recorrencia_id  Recurrence ID to exclude (used when
 *                                          generating dates for a new recurrence
 *                                          so it doesn't conflict with itself).
 * @return int  Number of conflicting recurrence patterns.
 */
function checkRecurrenciaConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim, $exclude_recorrencia_id = null) {
    $dia_semana = (int)date('w', strtotime($data));
    $dia_mes    = (int)date('j', strtotime($data));

    $sql = "SELECT COUNT(*) AS total
            FROM recorrencias rc
            WHERE rc.sala_id = ?
              AND rc.ativa = 1
              AND ? BETWEEN rc.data_inicio AND rc.data_fim
              AND rc.hora_inicio < ?
              AND rc.hora_fim > ?
              AND (
                  (rc.tipo = 'semanal'  AND rc.dia_semana = ?)
               OR (rc.tipo = 'mensal'   AND rc.dia_mes    = ?)
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM reservas r
                  WHERE r.recorrencia_id = rc.id
                    AND r.data_reserva   = ?
              )";

    if ($exclude_recorrencia_id !== null) {
        $sql .= " AND rc.id != ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die('SQL error (checkRecurrenciaConflict): ' . $conn->error);
        $stmt->bind_param('isssiisi', $sala_id, $data, $hora_fim, $hora_inicio,
                          $dia_semana, $dia_mes, $data, $exclude_recorrencia_id);
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt) die('SQL error (checkRecurrenciaConflict): ' . $conn->error);
        $stmt->bind_param('isssiis', $sala_id, $data, $hora_fim, $hora_inicio,
                          $dia_semana, $dia_mes, $data);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['total'];
}

/**
 * Full conflict check: materialized reservations + unmaterialized recurrence patterns.
 *
 * @param int|null $exclude_reserva_id      Reservation to ignore (for edits).
 * @param int|null $exclude_recorrencia_id  Recurrence to ignore (for self-generation).
 * @return int  Total number of conflicts found.
 */
function checkFullConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim, $exclude_reserva_id = null, $exclude_recorrencia_id = null) {
    $c1 = checkReservaConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim, $exclude_reserva_id);
    $c2 = checkRecurrenciaConflict($conn, $sala_id, $data, $hora_inicio, $hora_fim, $exclude_recorrencia_id);
    return $c1 + $c2;
}
