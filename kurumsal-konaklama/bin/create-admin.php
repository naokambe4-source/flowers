<?php
declare(strict_types=1);

/**
 * Acil durum: Super Admin oluşturur veya parolasını sıfırlar.
 * Kullanım: php bin/create-admin.php eposta@kurum.gov.tr "Ad" "Soyad"
 * Parola etkileşimli sorulur (komut geçmişine yazılmaz).
 */
require __DIR__ . '/bootstrap.php';

[$_, $email, $first, $last] = array_pad($argv, 4, null);
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Kullanım: php bin/create-admin.php eposta Ad Soyad\n");
    exit(1);
}
echo 'Parola (en az 10 karakter, harf ve rakam): ';
system('stty -echo 2>/dev/null');
$pass = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo PHP_EOL;
if (mb_strlen($pass) < 10 || !preg_match('/\d/', $pass) || !preg_match('/\pL/u', $pass)) {
    fwrite(STDERR, "Parola kurallara uymuyor.\n");
    exit(1);
}
$db = App\Core\App::db();
$roleId = (int) $db->value("SELECT id FROM roles WHERE slug = 'super_admin'");
$existing = $db->fetch('SELECT id FROM users WHERE email = ?', [mb_strtolower($email)]);
$data = ['password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'password_changed_at' => date('Y-m-d H:i:s'), 'status' => 'active', 'role_id' => $roleId];
if ($existing) {
    $db->update('users', $data, ['id' => $existing['id']]);
    echo "Kullanıcı güncellendi.\n";
} else {
    $db->insert('users', $data + ['email' => mb_strtolower($email), 'first_name' => $first ?: 'Yönetici', 'last_name' => $last ?: 'Hesabı']);
    echo "Super Admin oluşturuldu.\n";
}
