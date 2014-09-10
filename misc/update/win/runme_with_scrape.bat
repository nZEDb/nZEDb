set optimise=1
set scrape=1

set limit=111111111111111111111111

:Top

CD..
php.exe update_binaries.php
php.exe update_releases.php 1 true
CD win

set /a scrape=%scrape%+1
if %scrape%==5 goto scrape
:ScrapeDone

set /a optimise=%optimise%+1
if %optimise%==300 goto optimise
:OptimiseDone

set /a tv=%tv%+1
if %tv%==20 goto tv
:TVDone

set /a Movies=%Movies%+1
if %Movies%==20 goto Movies
:moviesDone

Sleep 120

GOTO TOP

:Optimise
CD..
php.exe optimise_db.php space
set optimise=0
CD win
GOTO OptimiseDone

:Scrape
CD..
CD..
CD reqscraper
php scrape.php
set scrape=0
CD..
CD update
CD win
GOTO ScrapeDone

:TV
CD..
php.exe update_tvschedule.php
set tv=0
CD win
GOTO tvdone

:Movies
CD..
php.exe update_theaters.php
set Movies=0
CD win
GOTO Moviesdone
