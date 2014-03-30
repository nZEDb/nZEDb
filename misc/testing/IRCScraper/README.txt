These scripts run IRC bots to get PRE information.

You must first copy settings_example.php to settings.php and change the settings in the file (settings.php).

Next you can run scrape.php, it will tell you all the options.

scrape.sh runs the bots with text output, if you cancel the script, one of the bots will still run, you must kill it manually.

scrape_daemon.sh runs the bots with no text output and lets go of the terminal lock (if you want to restart the script later, you MUST kill the bots first).