<?php
/**
 * Render a status badge.
 * Usage: include with $status set, or call statusBadge($status)
 */
function statusBadge(string $status): string {
    $map = [
        'confirmada' => ['bg-success',   'Confirmada'],
        'pendente'   => ['bg-warning text-dark', 'Pendente'],
        'cancelada'  => ['bg-danger',    'Cancelada'],
        'ativa'      => ['bg-success',   'Ativa'],
        'inativa'    => ['bg-secondary', 'Inativa'],
    ];
    [$cls, $label] = $map[$status] ?? ['bg-secondary', ucfirst($status)];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}
