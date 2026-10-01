# Cron ve E-posta Rehberi

## Cron (zorunlu)
Arka plan işleri (e-posta gönderimi, süresi dolan teklifler, çıkışı geçen rezervasyonların “Tamamlandı” yapılması, önbellek ve log temizliği, sağlayıcı sağlık kontrolü) veritabanı kuyruğunda tutulur ve cron ile işlenir.

cPanel → *Cron Jobs* → **Her 5 dakikada bir**:
```
/usr/local/bin/php /home/KULLANICI/public_html/oteller/bin/cron.php >/dev/null 2>&1
```
PHP yolu hostinge göre değişebilir (`/opt/cpanel/ea-php83/root/usr/bin/php` gibi). Doğru komut Yönetim → **Sistem Ayarları** sayfasının altında gösterilir.

CLI kullanılamıyorsa HTTP tetikleyici (gizli anahtarlıdır, paylaşmayın):
```
curl -s "https://alanadi.com/oteller/cron/<cron_token>" >/dev/null
```
Son cron çalışması Dashboard ve Bildirimler sayfasında görünür. Başarısız işler **Bildirimler** sayfasından yeniden kuyruğa alınabilir (artan bekleme ile otomatik en fazla 5 deneme).

## E-posta (SMTP)
Yönetim → **Sistem Ayarları → E-posta (SMTP)**:
- *E-posta gönderimi etkin*, SMTP sunucusu (ör. `mail.alanadi.com`), port (587 STARTTLS / 465 SSL), kullanıcı adı, parola (şifreli saklanır, tekrar gösterilmez), gönderen e-posta ve adı.
- cPanel → *Email Accounts* bölümünden bir gönderici hesabı oluşturun; SPF/DKIM (cPanel → *Email Deliverability*) kayıtlarını doğrulayın.
- **Bildirimler → Test e-postası gönder** ile doğrulayın.

E-posta yapılandırılmadan da sistem çalışır: bildirimler sistem içinde görünür; davet/parola bağlantıları yöneticiye ekranda bir kez gösterilir.

Şablonlar: Yönetim → **Bildirimler** (talep alındı, teklif gönderildi, rezervasyon alındı/onaylandı/değişti/iptal, üyelik, parola, destek yanıtı).
