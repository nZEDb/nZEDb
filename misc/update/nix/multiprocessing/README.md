####These multi-processing scripts require a POSIX compliant operating system and the PHP pcntl extension.


####binaries.php
This will download new headers for all active groups using your binaries threads site setting.
You can pass a argument, a number to limit the max amount of new headers to download.


####releases.php
This is identical to the python releases_threaded.py
This will create new releases/delete unwanted releases, process requestID's, categorize releases by group
using your release threads site setting.


####update_per_group.php:
This is identical to the python update_threaded.py
This will download new headers for all active groups, backfill 20k headers from all backfill enabled groups,
create new releases/delete unwanted releases, process requestID's, categorize releases, process additional and NFO
by group using your release threads site setting.