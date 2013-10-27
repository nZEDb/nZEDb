<?php
require("config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/site.php");

// Remove folders from tmpunrar.
$site = new Sites();
$tmpunrar = $site->get()->tmpunrarpath;
rmtree($tmpunrar);

if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] == "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc" && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processAll();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] == "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$i = 1;
	while ($i=1)
	{
		$postprocess->processAll();
		sleep(15);
	}
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] == "pre" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processPredb();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] == "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processNfos();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] == "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processMovies();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] == "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processMusic();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] == "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processGames();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] == "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processAnime();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] == "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processTV();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] == "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] !== "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processBooks();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] == "additional" && $argv[1] !== "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processAdditional();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] != "additional" && $argv[1] == "xxx" && $argv[1] !== "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();
	
	$postprocess->processXXX();
}
else if (isset($argv[1]) && !is_numeric($argv[1]) && $argv[1] !== "all" && $argv[1] !== "allinf" && $argv[1] !== "tmux" && $argv[1] !== "book" && $argv[1] !== "nfo" && $argv[1] !== "movies" && $argv[1] !== "music" && $argv[1] !== "games" && $argv[1] !== "anime" && $argv[1] !== "tv" && $argv[1] != "additional" && $argv[1] !== "xxx" && $argv[1] == "pc"  && isset($argv[2]) && ($argv[2] == "true" || $argv[2] == "false"))
{
	if ($argv[2] == "true")
		$postprocess = new PostProcess(true);
	else if ($argv[2] == "false")
		$postprocess = new PostProcess();

	$postprocess->processPC();
}
else
{
	exit("ERROR: Wrong argument.\n\n"
		."php postprocess.php all true		...: Does all the types of post processing.\n"
		."php postprocess.php pre true		...: Processes all Predb sites.\n"
		."php postprocess.php nfo true		...: Processes NFO files.\n"
		."php postprocess.php movies true	...: Processes movies.\n"
		."php postprocess.php music true	...: Processes music.\n"
		."php postprocess.php games true	...: Processes games.\n"
		."php postprocess.php book true		...: Processes books.\n"
		."php postprocess.php anime trexitue	...: Processes anime.\n"
		."php postprocess.php xxx true		...: Processes xxx.\n"
		."php postprocess.php pc true		...: Processes pc.\n"
		."php postprocess.php tv true		...: Processes tv.\n"
		."php postprocess.php additional true	...: Processes previews/mediainfo/etc...\n"
		."php postprocess.php allinf true		...: Does all the types of post processing on a loop, sleeping 15 seconds between.\n"
		."The second argument (true/false) determines wether to echo or not.\n\n");
}

/**
 * Delete a file or directory recursively.
 * @param string $path
 * found here, modded to only delete subfolders
 * https://gist.github.com/SteelPangolin/1407308
 */
function rmtree($path)
{
	if (is_dir($path))
	{
		foreach (scandir($path) as $name)
		{
			if (in_array($name, array('.', '..')))
				continue;

			$subpath = $path.DIRECTORY_SEPARATOR.$name;
			rmtree($subpath);
		}
	}
}
