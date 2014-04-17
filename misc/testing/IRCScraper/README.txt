These scripts run IRC bots to get PRE information.

You must first copy settings_example.php to settings.php and change the settings in the file (settings.php).

Next you can run scrape.php, it will tell you all the options.

It is recomended to run scrape.php by itself, without the shell script,
the servers are sometimes full and the shell scripts can't pick up those
errors well.

Corrupt and Zenet pre the same stuff, so there's no point in running both,
corrupt however is harder to connect to(because the server is almost always full),
so use zenet if you can't connect to corrupt.

If you have trouble connecting to efnet, try a different server address:
(irc.blackened.com | irc.Qeast.net | irc.efnet.pl | efnet.demon.co.uk | irc.lightning.net)

scrape.sh runs the bots with text output, closing the terminal will exit the bots.

scrape_daemon.sh runs the bots with no text output and lets go of the
terminal lock (if you want to restart the script later, the script will close the old scripts first).

########################################################################################################################
List of Efnet channels, you can add these to the ignore setting:

#alt.binaries.cd.image
#alt.binaries.console.ps3
#alt.binaries.dvd
#alt.binaries.erotica
#alt.binaries.flac
#alt.binaries.foreign
#alt.binaries.games.nintendods
#alt.binaries.inner-sanctum
#alt.binaries.moovee
#alt.binaries.movies.divx
#alt.binaries.sony.psp
#alt.binaries.sounds.mp3.complete_cd
#alt.binaries.teevee
#alt.binaries.games.wii
#alt.binaries.warez
#alt.binaries.games.xbox360
#scnzb
#tvnzb