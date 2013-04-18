Inlcuded in this folder are batch files for windows users.
You MUST set your system path to include the PHP path.

Otherwise at the top of each batch file add the line
set Path=“C:\yourpathtoPHP\”

updatebinaries.bat will execute the updatebinaries.php script.
updatereleases.bat will execute the updatereleases.php script.
updatebackill.bat will execute the backfill.php script.
Runme.bat will run a continous loop of update binaries and update releases and will optimise your db every 300 cycles.
Runme_with_scrape.bat is the same thing as Runme, however it will scrape every fifth time, if you are utilizing reqscraper.
