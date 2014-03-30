#!/bin/sh

# This runs IRCScraper silently in the background, you can close your terminal and it will still run.

cmd1="/usr/bin/php scrape.php corrupt true";
cmd2="/usr/bin/php scrape.php efnet true";

echo "Started IRCScraping in daemon mode."

# Kill corrupt if it's already open.
`ps -ef | grep "php corrupt" | awk '{print $2}' | xargs kill`
# Kill efnet if it's already open.
`ps -ef | grep "php efnet" | awk '{print $2}' | xargs kill`
sleep 5

# Run corrupt
nohup $cmd1 &
# Give corrupt time to start up.
sleep 20
# Run efnet
nohup $cmd2 &