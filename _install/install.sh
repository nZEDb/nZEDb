#!/bin/bash

echo "nZEDb Installer"
echo "----------------------"
echo ""

echo "Getting the nZEDb app from GitHub"
echo ""
git clone https://github.com/nZEDb/nZEDb.git
cd nZEDb
echo ""

if type -p composer >/dev/null 2>&1; then
	composer install
	else if [ -f "composer.phar" ]; then
		php composer.phar install
	else
		echo ""
		echo "Getting Composer for you..."
		curl -sS https://getcomposer.org/installer | php
		php composer.phar install
	fi
fi

echo ""
echo "Setting cache directory permissions for you..."
chmod 755 ./
chmod -R 755 resources
chmod -R 777 app/resources
chmod +x ./zed
alias zed='./zed'
echo ""

echo ""
echo "Installation complete."
echo "Now run the setup via the web site's /install page."
echo "------------------------"
