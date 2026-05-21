<?php
$success = get_flash('success');
$error   = get_flash('error');
$warning = get_flash('warning');
?>
<?php if ($success): ?>
<div hidden data-flash-type="success" data-flash-message="<?= e($success) ?>"></div>
<?php endif; ?>
<?php if ($error): ?>
<div hidden data-flash-type="error" data-flash-message="<?= e($error) ?>"></div>
<?php endif; ?>
<?php if ($warning): ?>
<div hidden data-flash-type="warning" data-flash-message="<?= e($warning) ?>"></div>
<?php endif; ?>
