<?php
// hai.. what this does: fetch only a header field from a
// usenet group (very fast), and apply some regex to it..
// use it for testing regex stuff

$hostname   = 'news.usenetserver.com';
$username   = 'username';
$password   = 'password';
$group      = 'alt.binaries.teevee';

$header_field = 'Subject';
$max_articles = 100000;

# /usr/share/pear/Net/NNTP/Client.php
include 'Net/NNTP/Client.php';

$nntp = new Net_NNTP_Client;

$nntp->connect($hostname);
$nntp->authenticate($username, $password);

$group      = $nntp->selectGroup('alt.binaries.teevee');
print_r($group);

$first = ($group['last'] - $max_articles);
$last  = $group['last'];

$articles   = $nntp->getHeaderField($header_field, "$first-$last");
print count($articles) . " articles indexed..\n";

foreach ($articles as $article)
{
    $pattern = '/(\((\d+)\/(\d+)\))$/i';
    if (!preg_match($pattern, rtrim($article), $matches))
    {
        echo "Not matched: $article\n";
    }
    else
    {
#        echo "$matches[2]\n";
    }
}

exit;
