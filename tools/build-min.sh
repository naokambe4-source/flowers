#!/bin/bash
# Tema CSS/JS dosyalarının küçültülmüş (.min) kopyalarını üretir. Sürüm paketlemeden önce çalıştırın.
# Gerekli: Node.js (npx terser, npx csso). Kullanım: tools/build-min.sh [node_modules/.bin dizini]
set -e
BIN=${1:-"npx --yes"}
cd "$(dirname "$0")/../derin-flowers/assets"
for f in css/*.css admin/*.css; do
  case "$f" in *.min.css) continue;; esac
  $BIN/csso "$f" -o "${f%.css}.min.css" 2>/dev/null || npx --yes csso-cli "$f" -o "${f%.css}.min.css"
done
for f in js/*.js admin/*.js; do
  case "$f" in *.min.js) continue;; esac
  $BIN/terser "$f" -c -m -o "${f%.js}.min.js" 2>/dev/null || npx --yes terser "$f" -c -m -o "${f%.js}.min.js"
done
echo "Küçültme tamam."
