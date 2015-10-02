<?php
namespace Moinax\TvDb\Http;

use Moinax\TvDb\CurlException;

class CurlClient implements HttpClient
{

    /**
     * Fetch the given url contents using cURL library
     *
     * @see \Moinax\TvDb\Http\HttpClient::fetch()
     */
    public function fetch($url, array $params = array(), $method = HttpClient::GET)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($method == HttpClient::POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $data = substr($response, $headerSize);
        curl_close($ch);

        if ($httpCode != 200) {
            throw new CurlException(sprintf('Cannot fetch %s', $url), $httpCode);
        }

        return $data;
    }
}