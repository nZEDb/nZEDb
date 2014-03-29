#!/bin/bash
cmd1="/usr/bin/php scrape.php corrupt";
cmd2="/usr/bin/php scrape.php efnet";

$cmd1 &
sleep 3
echo "This started scrapeCorrupt in the background, if you cancel this script, it will still run, so you must kill it manually."
echo `ps aux | grep 'corrupt' | head -1`
echo "To kill it, type kill and the number after your username."
echo "scrapeEfnet, will close however, since it was not started in the background."
sleep 3
$cmd2