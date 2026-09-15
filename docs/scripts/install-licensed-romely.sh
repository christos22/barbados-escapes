#!/usr/bin/env bash
# Deploy the purchased, unmodified WOFF2 without putting it in public Git.
set -euo pipefail

readonly FONT_SHA='b9d5f6197645b1df7f175dcc3e513cfff2c65207a04e7ab0e7053d8a2519b764'
readonly EULA_SHA='1aa3dab4c2c5d4918256bef3ced4b6a355b353eb1fa9e1dd10596cebe733c60a'
readonly FONT_NAME='Romely-Regular-b9d5f6197645.woff2'

fail() { printf '%s\n' "$*" >&2; exit 1; }
verify_sha() {
	local actual
	test -f "$1" && test ! -L "$1" || fail "Missing or symlinked licensed asset: $1"
	actual=$(sha256sum -- "$1")
	test "${actual%% *}" = "$2" || fail "Licensed asset checksum mismatch: $1"
}

test "$#" -ge 2 || fail 'Usage: install-licensed-romely.sh check|install PRIVATE_DIRECTORY [THEME_DIRECTORY]'
readonly operation="$1"
private_directory=$(realpath -e -- "$2")
readonly private_directory
readonly source_font="$private_directory/Romely-Regular.woff2"

# Check both source documents before any deployment changes the live theme.
verify_sha "$source_font" "$FONT_SHA"
verify_sha "$private_directory/EULA-q5smwh.pdf" "$EULA_SHA"

if [ "$operation" = 'check' ] && [ "$#" -eq 2 ]; then
	printf 'Private Romely source and EULA checksums verified.\n'
	exit 0
fi

test "$operation" = 'install' && test "$#" -eq 3 || fail 'Invalid install arguments.'
theme_directory=$(realpath -e -- "$3")
readonly theme_directory

# This helper only writes inside an existing copy of this project's theme.
case "$theme_directory" in
	/*/wp-content/themes/gutenberg-lab-vvm) ;;
	*) fail "Unexpected theme directory: $theme_directory" ;;
esac
test -f "$theme_directory/theme.json" && test -f "$theme_directory/style.css" || fail 'Theme markers missing.'
wordpress_directory="${theme_directory%/wp-content/themes/gutenberg-lab-vvm}"
case "$private_directory" in
	"$wordpress_directory"|"$wordpress_directory"/*) fail 'Private assets must be outside the WordPress document root.' ;;
esac
test -d "$theme_directory/assets/fonts" || fail 'Theme fonts directory missing.'
test ! -L "$theme_directory/assets" && test ! -L "$theme_directory/assets/fonts" || fail 'Symlinked asset directories are not supported.'

readonly font_directory="$theme_directory/assets/fonts/romely"
test ! -L "$font_directory" || fail 'Symlinked Romely directory is not supported.'
install -d -m 755 -- "$font_directory"

# Install atomically; the content-hashed URL bypasses cached copies of the old font.
temporary_font=$(mktemp "$font_directory/.romely-install.XXXXXX")
trap 'test -z "${temporary_font:-}" || rm -f -- "$temporary_font"' EXIT
install -m 644 -- "$source_font" "$temporary_font"
verify_sha "$temporary_font" "$FONT_SHA"
mv -f -- "$temporary_font" "$font_directory/$FONT_NAME"
temporary_font=''
verify_sha "$font_directory/$FONT_NAME" "$FONT_SHA"

# Retain the previous file privately for rollback, not at its old public URL.
old_font="$font_directory/Romely-Regular.woff2"
if [ -e "$old_font" ]; then
	test -f "$old_font" && test ! -L "$old_font" || fail 'Unexpected old Romely file type.'
	old_sha=$(sha256sum -- "$old_font")
	backup="$private_directory/retired-${old_sha%% *}.woff2"
	if [ -e "$backup" ]; then
		cmp -s -- "$old_font" "$backup" || fail 'Existing rollback file differs.'
	fi
	mv -f -- "$old_font" "$backup"
	chmod 600 -- "$backup"
fi

printf 'Licensed Romely Regular installed and verified: %s\n' "$font_directory/$FONT_NAME"
