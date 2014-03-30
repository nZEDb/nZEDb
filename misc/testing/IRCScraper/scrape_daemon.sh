#!/bin/bash

# This runs IRCScraper silently in the background.

cmd1="/usr/bin/php scrape.php corrupt true";
cmd2="/usr/bin/php scrape.php efnet true";

echo "Started IRCScraping in daemon mode."

# Kill corrupt if it's already open.
`ps -ef | grep "php corrupt" | awk '{print $2}' | xargs kill`
sleep 2
# Kill efnet if it's already open.
`ps -ef | grep "php efnet" | awk '{print $2}' | xargs kill`
sleep 2

# Run corrupt
$cmd1 &
sleep 3
# Run efnet
$cmd2 &