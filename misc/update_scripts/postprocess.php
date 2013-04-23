<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");

if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processAll();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] == "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processMovies();
	$postprocess->processMusic();
	$postprocess->processGames();
	$postprocess->processAnime();
	$postprocess->processTv();
	$postprocess->processAdditional();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] == "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] == "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processMovies();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] == "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processMusic();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] == "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processGames();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] == "anime" && $argv[1] !== "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processAnime();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] == "tv" && $argv[1] !== "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processTV();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] == "additional")
{
	$postprocess = new PostProcess(true);
	$postprocess->processAdditional();
}
else
{
	exit("ERROR: Wrong argument.\n\n"
		."php postprocess.php all		...: Does all the types of post processing.\n"
		."php postprocess.php nfo		...: Processes NFO files.\n"
		."php postprocess.php movies	...: Processes movies.\n"
		."php postprocess.php music	...: Processes music.\n"
		."php postprocess.php games	...: Processes games.\n"
		."php postprocess.php anime	...: Processes anime.\n"
		."php postprocess.php tv		...: Processes tv.\n"
		."php postprocess.php additional	...: Processes previews/mediainfo/etc...\n"
		."php postprocess.php tmux	...: Processes all but NFO files.\n\n");
}
