<?php
$pageTitle    = 'Gráficos';
$pageSubtitle = 'Análise de reservas';
$extraJsCdn   = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
];
?>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Reservas por período</span>
                <select id="periodoDays" class="form-select form-select-sm w-auto">
                    <option value="7">7 dias</option>
                    <option value="30" selected>30 dias</option>
                    <option value="60">60 dias</option>
                    <option value="90">90 dias</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="chartPeriodo"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Taxa de Ocupação (30 dias)</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartOcupacao" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Salas Mais Usadas (30 dias)</div>
            <div class="card-body">
                <canvas id="chartSalas"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Resumo</div>
            <div class="card-body" id="resumoBox">
                <p class="text-muted">A carregar…</p>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
var APP_BASE = '<?= APP_BASE ?>';
var _periodoChart = null;

function loadChart(url, canvasId, buildConfig) {
    fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var ctx = document.getElementById(canvasId);
            if (!ctx || typeof Chart === 'undefined') return;
            new Chart(ctx, buildConfig(d));
        })
        .catch(function () {});
}

function loadPeriodo(days) {
    fetch(APP_BASE + '/api/charts/periodo?days=' + days)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var ctx = document.getElementById('chartPeriodo');
            if (!ctx || typeof Chart === 'undefined') return;
            if (_periodoChart) { _periodoChart.destroy(); }
            _periodoChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Reservas',
                        data: d.data,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        fill: true, tension: 0.4, pointRadius: 3, pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {legend: {display: false}},
                    scales: {y: {beginAtZero: true, ticks: {stepSize: 1, precision: 0}}}
                }
            });
        })
        .catch(function () {});
}

document.addEventListener('DOMContentLoaded', function () {
    loadPeriodo(30);

    document.getElementById('periodoDays').addEventListener('change', function () {
        loadPeriodo(parseInt(this.value));
    });

    loadChart(APP_BASE + '/api/charts/salas', 'chartSalas', function (d) {
        var palette = ['#3B82F6','#10B981','#6366F1','#EC4899','#F97316','#14B8A6','#8B5CF6','#EF4444','#3B82F6','#10B981'];
        return {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [{
                    label: 'Reservas',
                    data: d.data,
                    backgroundColor: palette.slice(0, d.data.length)
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {x: {beginAtZero: true, ticks: {stepSize: 1, precision: 0}}}
            }
        };
    });

    loadChart(APP_BASE + '/api/charts/ocupacao', 'chartOcupacao', function (d) {
        // Fill resumo box
        var total = d.data.reduce(function (s, v) { return s + v; }, 0);
        var box = document.getElementById('resumoBox');
        if (box) {
            var html = '<dl class="row mb-0">';
            d.labels.forEach(function (label, i) {
                var pct = total > 0 ? Math.round(d.data[i] / total * 100) : 0;
                html += '<dt class="col-7">' + label + '</dt>' +
                        '<dd class="col-5 text-end"><strong>' + d.data[i] + '</strong> <span class="text-muted small">(' + pct + '%)</span></dd>';
            });
            html += '<dt class="col-7 border-top pt-2">Total</dt>' +
                    '<dd class="col-5 text-end border-top pt-2"><strong>' + total + '</strong></dd>';
            html += '</dl>';
            box.innerHTML = html;
        }
        return {
            type: 'doughnut',
            data: {
                labels: d.labels,
                datasets: [{data: d.data, backgroundColor: d.colors, borderWidth: 2}]
            },
            options: {
                responsive: true,
                plugins: {legend: {position: 'bottom'}}
            }
        };
    });
});
<?php $inlineJs = ob_get_clean(); ?>
