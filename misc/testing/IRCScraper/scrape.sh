#!/bin/bash
cmd1="/usr/bin/php scrape.php corrupt";
cmd2="/usr/bin/php scrape.php efnet";

# Kill corrupt if it's already open.
`ps -ef | grep "php corrupt" | awk '{print $2}' | xargs kill`
sleep 2

# Run corrupt in the background.
$cmd1 &
sleep 3
echo ""
echo "This started scrapeCorrupt in the background, if you cancel this script, it will still run, so you must kill it manually."
echo "scrapeEfnet, will close however, since it was not started in the background."
echo ""
echo `ps aux | grep 'php corrupt' | awk '{print $2}'`
echo "To kill it, in the message above, you see a number, in a command line, type kill theNumber  (theNumber, is the number over this line, the one to the left)"
echo ""
sleep 3
$cmd2