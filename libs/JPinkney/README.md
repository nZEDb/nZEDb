# TVMaze-PHP-API-Wrapper
An easier way to interact with TVMaze's endpoints. Developed in PHP.

Goal
====
- The goal of this API Wrapper is to turn TVMaze's endpoints into something more object orientated and readable

Documentation
=============
At the current version this wrapper supports all of TVMaze's endpoints except for schedule.

Pre-reqs
--------
- Before attempting to use this wrapper make sure you require 'TVMazeIncludes.php'; at the top of your php file.

Supported Methods
-----------------
search -> Return all tv shows relating to the given input

singleSearch -> Return the most relevant tv show relating to the given input

getShowBySiteID -> Allows show lookup by using TVRage or TheTVDB ID

getPersonByName -> Return all possible actors relating to the given input

getSchedule (In progess) -> Return all the shows in the given country and/or date

getShowByShowID -> Return all information about a show given the show ID

getEpisodesByShowID -> Return all episodes for a show given the show ID

getCastByShowID -> Return the cast for a show given the show ID

getAllShowsByPage -> Return a master list of TVMaze's shows given the page number

getPersonByID -> Return an actor given their ID

getCastCreditsByID -> Return an array of all the shows a particular actor has been in given their ID

getCrewCreditsByID -> Return an array of all the positions a particular actor has been in given their ID

Open Source Projects using this
-------------------------------
nZEDb
Website Link: http://www.nzedb.com
Github Link: https://github.com/nZEDb/nZEDb
