<?php
/**
 * Can Eloksal — Veritabanı bağlantı hatası sayfası.
 * Kurulum: Kurulum & Araçlar → "Hata sayfalarını kur" bu dosyayı wp-content/db-error.php olarak kopyalar.
 */
if ( ! headers_sent() ) {
	header( 'HTTP/1.1 503 Service Unavailable' );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 300' );
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Bakım çalışması — Can Eloksal</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0A1118;color:#fff;font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;padding:24px;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:64px 64px}
main{max-width:620px}.code{font:800 clamp(72px,18vw,160px)/1 system-ui,sans-serif;letter-spacing:-.04em;background:linear-gradient(135deg,#fff,#87939D);-webkit-background-clip:text;background-clip:text;color:transparent;margin:0}
h1{font-size:clamp(24px,4vw,36px);line-height:1.2;margin:16px 0 12px}p{color:#B8C2C9;margin:0 0 28px}
a{display:inline-flex;align-items:center;gap:8px;background:#00AFC1;color:#0A1118;text-decoration:none;font-weight:700;padding:14px 22px;border-radius:999px}a:focus-visible{outline:3px solid #19C4D2;outline-offset:3px}
.brand{letter-spacing:.24em;font-size:12px;color:#19C4D2;font-weight:700;text-transform:uppercase}
</style>
</head>
<body>
<main>
<p class="brand">Can Eloksal</p>
<p class="code">503</p>
<h1>Sitemize şu anda ulaşılamıyor.</h1>
<p>Kısa süreli bir bağlantı sorunu yaşıyoruz. Lütfen birkaç dakika sonra tekrar deneyin. Acil durumlar için 0541 781 20 60 numaralı telefondan bize ulaşabilirsiniz.</p>
<a href="/">Ana Sayfaya Dön</a>
</main>
</body>
</html>
