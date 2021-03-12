clean:
	rm -rf build

update:
	ppm --generate-package="src/TelegramCDN"

build:
	mkdir build
	ppm --no-intro --compile="src/TelegramCDN" --directory="build"

install:
	ppm --no-prompt --fix-conflict --branch="production" --install="build/net.intellivoid.telegram_cdn.ppm"

install_fast:
	ppm --no-prompt --skip-dependencies --fix-conflict --branch="production" --install="build/net.intellivoid.telegram_cdn.ppm"