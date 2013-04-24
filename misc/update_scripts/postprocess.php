<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");

if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processAll();
}
if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] == "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$i = 1;
	while ($i=1)
	{
		$postprocess->processAll();
		sleep(15);
	}
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] == "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processMovies();
	$postprocess->processMusic();
	$postprocess->processGames();
	$postprocess->processAnime();
	$postprocess->processTv();
	$postprocess->processAdditional();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] == "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] == "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processMovies();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] == "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processMusic();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] == "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processGames();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] == "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processAnime();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] == "tv" && $argv[1] !== "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processTV();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] == "additional" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
	{
		$postprocess = new PostProcess(true);
	}
	else if ($argv[2] == "false")
	{
		$postprocess = new PostProcess();
	}
	$postprocess->processAdditional();
}
else
{
	exit("ERROR: Wrong argument.\n\n"
		."php postprocess.php all true		...: Does all the types of post processing.\n"
		."php postprocess.php nfo true		...: Processes NFO files.\n"
		."php postprocess.php movies true		...: Processes movies.\n"
		."php postprocess.php music true		...: Processes music.\n"
		."php postprocess.php games true		...: Processes games.\n"
		."php postprocess.php anime true		...: Processes anime.\n"
		."php postprocess.php tv true		...: Processes tv.\n"
		."php postprocess.php additional true	...: Processes previews/mediainfo/etc...\n"
		."php postprocess.php allinf true		...: Does all the types of post processing on a loop, sleeping 15 seconds between.\n"
		."php postprocess.php tmux true		...: Processes all but NFO files.\n"
		."The second argument (true/false) determines wether to echo or not.\n\n");
}
