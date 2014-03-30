#!/bin/sh
cmd1="sh .hidden.sh";
cmd2="/usr/bin/php scrape.php efnet";

# If the user exits, close corrupt.
onExit () {
	`ps -ef | grep "php cz" | awk '{print $2}' | xargs kill`
}

trap 'onExit' INT QUIT

# Kill corrupt/efnet if they already open.
`ps -ef | grep "php cz" | awk '{print $2}' | xargs kill`
`ps -ef | grep "php efnet" | awk '{print $2}' | xargs kill`
sleep 5

# Run corrupt in the background.
$cmd1
# Give corrupt time to start up.
sleep 20
# Now start efnet.
$cmd2