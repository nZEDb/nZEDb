<?php

/*
 * 	Lookup information from trakt.tv using their API.
 */
Class TraktTv
{
    function __construct()
    {
        $s = new Sites();
        $site = $s->get();
        $this->APIKEY = $site->trakttvkey;
    }

    //
    // Fetches summary from trakt.tv for the TV show using the title/season/episode.
    //
	public function traktTVSEsummary($showtitle = '', $season = '', $ep = '')
    {
        $chars = array(' ', '_', '.');
        $showtitle = str_replace($chars, '-', $showtitle);
        $season = str_replace(array('S', 's'), '', $season);
        $ep = str_replace(array('E', 'e'), '', $ep);
        $TVurl = 'http://api.trakt.tv/show/episode/summary.json/' . $this->APIKEY . '/' . $showtitle . '/' . $season . '/' . $ep;
        $TVjson = @file_get_contents($TVurl, 0, null, null);

        if ($TVjson === false) {
            // We failed getting the URL. Maybe the API key is not set, or the release is not on the site?
            return false;
        } else {
            $TVarray = json_decode($TVjson, true);

            return $TVarray;
        }
    }

    //
    // Fetches summary from trakt.tv for the movie.
    // Accept a title (the-big-lebowski-1998), a IMDB id, or a TMDB id.
    // Returns array, or IMDBid.
    //
	public function traktMoviesummary($movie = '', $type = '')
    {

        $chars = array(' ', '_', '.');
        $movie = str_replace($chars, '-', $movie);
        $movie = str_replace(array('(', ')'), '', $movie);
        $Movieurl = 'http://api.trakt.tv/movie/summary.json/' . $this->APIKEY . '/' . $movie;
        $Moviejson = @file_get_contents($Movieurl, 0, null, null);

        if ($Moviejson === false) {
            // We failed getting the URL. Maybe the API key is not set, or the release is not on the site?
            return false;
        } else {
            $Moviearray = json_decode($Moviejson, true);

            if ($type == "imdbid") {
                if (isset($Moviearray["imdb_id"])) {
                    return $Moviearray["imdb_id"];
                } else {
                    return false;
                }
            } else if ($type == "array") {
                return $Moviearray;
            }
        }
    }
}
?>
