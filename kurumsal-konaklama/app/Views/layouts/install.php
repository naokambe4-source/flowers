<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Kurulum · Kurumsal Konaklama</title>
<link rel="stylesheet" href="<?= e(App\Core\Url::basePath() . '/assets/css/app.css') ?>">
</head>
<body>
<header class="site-header"><div class="container"><span class="brand"><span class="brand-mark"><?= icon('building') ?></span><span class="brand-text"><span>Kurumsal Konaklama</span><small>Kurulum sihirbazı</small></span></span></div></header>
<main id="icerik" class="install-wrap"><?= App\Core\View::partial('partials/flash') ?><?= $content ?></main>
<script src="<?= e(App\Core\Url::basePath() . '/assets/js/app.js') ?>" defer></script>
</body>
</html>
