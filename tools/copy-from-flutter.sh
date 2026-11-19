#!/usr/bin/env bash
# Copies the parts the Laravel build shares with the Flutter repo
# (../AgosAlert), so both look and work the same. Rerun after the Flutter
# side changes them. Run from the Laravel project root, in Git Bash:
#   bash tools/copy-from-flutter.sh
set -euo pipefail
F=../AgosAlert
P=public

# 1. The landing page = this site's front page (/). Its "Open the app"
#    buttons go to Laravel's /login and /weather instead of app/.
mkdir -p "$P/img" "$P/fonts"
cp "$F/landing/index.html" "$P/landing.html"
cp "$F/landing/landing.css" "$F/landing/landing.js" "$F/landing/tugak.js" "$F/landing/icons.css" \
   "$F/landing/privacy.html" "$F/landing/terms.html" "$F/landing/takedown.html" "$F/landing/legal.css" "$P/"
cp "$F/landing/fonts/cupertino.woff" "$P/fonts/"
# Only the images the pages use (not the photo originals).
for f in "$F"/landing/img/*.webp "$F"/landing/img/*.png "$F"/landing/img/*.svg; do
  [ -e "$f" ] && cp "$f" "$P/img/"
done
mkdir -p "$P/img/tugak" && cp "$F"/landing/img/tugak/*.webp "$P/img/tugak/"
sed -i "s#function appUrl(as) { return 'app/' + (as === 'guest' ? '?as=guest' : ''); }#function appUrl(as) { return as === 'guest' ? 'weather' : 'login'; } // Laravel routes#" "$P/landing.js"
grep -q "Laravel routes" "$P/landing.js" || { echo "appUrl not patched: landing.js changed"; exit 1; }
# The live 3D Tugak loads from app/tugak/ (web/tugak in the Flutter repo).
mkdir -p "$P/app" && rm -rf "$P/app/tugak" && cp -r "$F/web/tugak" "$P/app/tugak"

# 2. The map: the app's own MapLibre files and map script, unchanged.
#    Kept under agos/, because /map is a Laravel page; the map pages set
#    <base href="/agos/"> so the script's relative paths still work.
A="$P/agos"
mkdir -p "$A/assets/assets/fonts" "$A/assets/packages/cupertino_icons/assets"
rm -rf "$A/map" && cp -r "$F/web/map" "$A/map"
cp "$F/web/agos_map.js" "$A/"
cp "$F"/assets/fonts/Inter-{Regular,SemiBold,Bold,ExtraBold}.ttf "$F/assets/fonts/AgosIcons.ttf" "$A/assets/assets/fonts/"
cp "$P/fonts/CupertinoIcons.ttf" "$A/assets/packages/cupertino_icons/assets/"
echo "Copied landing page + map from $F"
