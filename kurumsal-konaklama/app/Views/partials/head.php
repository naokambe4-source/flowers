<?php
/** @var string $title */
$siteName = setting('site.name', 'Kurumsal Konaklama');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#0f2240">
<meta name="format-detection" content="telephone=no">
<title><?= e(isset($title) && $title ? $title . ' · ' . $siteName : $siteName) ?></title>
<link rel="icon" href="<?= e(asset_url('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
<?php if (!empty($withMap)): ?>
<link rel="stylesheet" href="<?= e(asset_url('vendor/leaflet/leaflet.css')) ?>">
<?php endif; ?>
