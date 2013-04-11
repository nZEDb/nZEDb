<?php
require_once("config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/binaries.php");

$bin = new Binaries();
$articles = $bin->search("mess");

foreach ($articles as $article)
{
    $pattern = '/()(.*)(\d{2,3}\/\d{1,3})/i';
    if (!preg_match($pattern, rtrim($article["name"]), $matches))
    {
        echo "Not matched: ".$article["name"]."<br/>";
    }
    else
    {
		print_r($matches)."";
    }
}

