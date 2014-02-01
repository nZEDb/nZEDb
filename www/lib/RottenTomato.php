<?php

class RottenTomato
{
    const API_URL = "http://api.rottentomatoes.com/api/public/v1.0/";

    private $_apikey;

    public function __construct($apikey)
    {
        $this->setApikey($apikey);
    }

    public function getBoxOffice()
    {
        return $this->_makeCall('lists/movies/box_office.json');
    }

    public function getInTheaters()
    {
        return $this->_makeCall('lists/movies/in_theaters.json');
    }

    public function getOpening()
    {
        return $this->_makeCall('lists/movies/opening.json');
    }

    public function getUpcoming()
    {
        return $this->_makeCall('lists/movies/upcoming.json');
    }

    public function getDVDReleases()
    {
        return $this->_makeCall('lists/dvds/new_releases.json');
    }

    public function searchMovie($title)
    {
        $params = array(
            'q' => $title,
            'page_limit' => 50
        );

        return $this->_makeCall('movies.json', $params);
    }

    public function getMovie($rtid)
    {
        return $this->_makeCall('movies/' . $rtid . '.json');
    }

    public function getReviews($rtid, $type = "top_critic")
    {
        $params = array(
            'review_type' => $type,
            'page_limit' => 20
        );

        return $this->_makeCall('movies/' . $rtid . '/reviews.json');
    }

    public function getCast($rtid)
    {
        return $this->_makeCall('movies/' . $rtid . '/cast.json');
    }

    public function getURLtest()
    {
        return RottenTomato::API_URL . "movies.json?apikey=" . $this->getApikey() . "&q=inception&page_limit=50";
    }

    private function _makeCall($function, $param = "")
    {
        $params = '';
		$limit = mt_rand(15, 20);

        if (is_array($param) AND !empty($param)) {
            $params .= '&' . http_build_query($param);
        }

        $url = RottenTomato::API_URL . $function . "?limit=" . $limit . "&apikey=" . $this->getApikey() . $params;

        $results = '';

        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);

            $results = curl_exec($ch);
            $headers = curl_getinfo($ch);

            $error_number = curl_errno($ch);
            $error_message = curl_error($ch);

            curl_close($ch);
        } else {
            $results = file_get_contents($url);
        }

        return (string) trim($results);
    }

    public function setApikey($apikey)
    {
        $this->_apikey = (string) $apikey;
    }

    public function getApikey()
    {
        return $this->_apikey;
    }
}
?>
