<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Reserva de Salas') ?> — Sassi Imóveis</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= ASSETS_BASE ?>/assets/css/design-tokens.css">
<link rel="stylesheet" href="<?= ASSETS_BASE ?>/assets/css/app.css">
<?php if (!empty($extraCssCdn)): ?>
<?php foreach ((array)$extraCssCdn as $url): ?>
<link rel="stylesheet" href="<?= e($url) ?>">
<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($extraCss)): ?>
<?php foreach ((array)$extraCss as $css): ?>
<link rel="stylesheet" href="<?= ASSETS_BASE ?>/assets/css/<?= e($css) ?>">
<?php endforeach; ?>
<?php endif; ?>
