#!/usr/bin/env bash
# Üretim paketini oluşturur: build/kurumsal-konaklama-<sürüm>.zip
# Test dosyaları, test verisi, yerel ayarlar, loglar, oturumlar ve yüklenen dosyalar pakete girmez.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(grep -o "VERSION = '[^']*'" "$ROOT/app/Core/App.php" | cut -d"'" -f2)"
OUT="$ROOT/build"
STAGE="$OUT/kurumsal-konaklama"
rm -rf "$STAGE" "$OUT/kurumsal-konaklama-$VERSION.zip"
mkdir -p "$STAGE"
(cd "$ROOT" && composer install --no-dev --optimize-autoloader --no-interaction -q)
rsync -a \
  --exclude '.git' --exclude '.gitignore' --exclude 'build' --exclude 'tests' \
  --exclude 'config/config.php' --exclude 'storage/installed.lock' \
  --exclude 'storage/logs/*' --exclude 'storage/sessions/*' --exclude 'storage/tmp/*' \
  --exclude 'storage/cache/*' --exclude 'storage/private/*' \
  "$ROOT/" "$STAGE/"
# vendor budama: testler, örnekler, dokümanlar, git verisi
find "$STAGE/vendor" -depth -type d \( -name .git -o -name tests -o -name Tests -o -name test -o -name examples -o -name docs -o -name doc -o -name .github \) -exec rm -rf {} +
find "$STAGE/vendor" -type f \( -name '*.md' ! -iname 'LICENSE*' -o -name 'phpunit*' -o -name '.gitattributes' -o -name '.editorconfig' -o -name 'phpstan*' -o -name '*.dist' \) -delete
for d in logs sessions tmp cache private; do mkdir -p "$STAGE/storage/$d"; touch "$STAGE/storage/$d/.gitkeep"; done
# Güvenlik denetimi: üretim paketinde test hesabı / demo içeriği olmamalı
if grep -rIl --exclude-dir=vendor --exclude-dir=docs --exclude=build-zip.sh -e 'test\.local' -e 'Test12345678' -e 'DEMO' "$STAGE" ; then
  echo "HATA: Üretim paketinde test/demo içeriği bulundu." >&2; exit 1
fi
(cd "$OUT" && zip -qr "kurumsal-konaklama-$VERSION.zip" kurumsal-konaklama)
echo "Oluşturuldu: $OUT/kurumsal-konaklama-$VERSION.zip ($(du -h "$OUT/kurumsal-konaklama-$VERSION.zip" | cut -f1))"
