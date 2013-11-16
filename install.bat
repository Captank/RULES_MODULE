@echo off
title Budabot - RULES_MODULE install
if exist {php-win.ini} (
	rem correct dir found
) else (
	cd ..
)

.\win32\php -c php-win.ini -f "%~dp0\install.php"

cd "%~dp0"
PAUSE
:: This file is part of Budabot.
::
:: Budabot is free software: you can redistribute it and/org modify
:: it under the terms of the GNU General Public License as published by
:: the Free Software Foundation, either version 3 of the License, or
:: (at your option) any later version.
::
:: Budabot is distributed in the hope that it will be useful,
:: but WITHOUT ANY WARRANTY; without even the implied warranty of
:: MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
:: GNU General Public License for more details.
::
:: You should have received a copy of the GNU General Public License
:: along with Budabot. If not, see <http://www.gnu.org/licenses/>.