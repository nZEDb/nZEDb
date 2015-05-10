This script runs an IRC bot to get PRE information.

For detailed guides on how to set this up and ZNC see here:
https://github.com/nZEDb/nZEDb_Misc/tree/master/Guides/Various

You must first copy settings_example.php to settings.php and change the settings in the file (settings.php).

It is recommended to use ZNC, to avoid network issues (if you do not want to get banned from IRC).
Setting up ZNC is easy : http://wiki.znc.in/Installation

If you have trouble connecting, try another IRC server: https://www.synirc.net/servers

Run php scrape.php false to see all the options.

You can run this with screen (then detach; control + a then d) : screen php scrape.php true