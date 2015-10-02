<?php
namespace Moinax\TvDb\Http;

use Moinax\TvDb\CurlException;
use Moinax\TvDb\Http\Cache\Cache;

class CacheClient implements HttpClient
{

    /**
     * Cache to store resources.
     *
     * @var Cache
     */
    private $cache;

    /**
     * Minimum resource age before doing any request.
     *
     * @var integer
     */
    private $ttl;

    /**
     * Create a new HttpClient with caching features.
     *
     * @param Cache $cache
     * @param integer $ttl
     */
    public function __construct(Cache $cache, $ttl)
    {
        $this->cache = $cache;

        $this->ttl = $ttl;
    }

    /**
     *
     * @see \Moinax\TvDb\Http\HttpClient::fetch()
     */
    public function fetch($url, array $params = array(), $method = HttpClient::GET)
    {
        $ch = $this->curlInit($url, $params, $method);
        if($method == HttpClient::POST) {
            return $this->doPost($ch, $url);
        }
        return $this->doGet($ch, $url);
    }

    /**
     * Get the cache time to live.
     *
     * @return number
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Set the cache time to live.
     *
     * @param integer $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * Do a GET request.
     *
     * @param resource $ch
     * @param string $url
     * @throws CurlException
     * @return string
     */
    private function doGet($ch, $url)
    {
        $now = time();
        $resource = $this->getResourceName($url);
        $date = $this->cache->getDate($resource);
        $limit = $now - $this->ttl;

        //Return content if ttl has not yet expired.
        if($date > 0 && $date > $limit) {
            return $this->cache->getContent($resource);
        }

        //Add the If-Modified-Since header
        if($date > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                sprintf("If-Modified-Since: %s GMT", gmdate("D, d M Y H:i:s", $date)),
            ));
        }

        curl_setopt($ch, CURLOPT_FILETIME, true);

        $data = $this->curlExec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $date = curl_getinfo($ch, CURLINFO_FILETIME);
        curl_close($ch);

        //Return content from cache.
        if($httpCode == 304) {
            $this->cache->setDate($resource, $date);
            return $this->cache->getContent($resource);
        }

        //Cache content
        if ($httpCode == 200) {
            $date = ($date >= 0) ? $date : $now;
            $this->cache->cache($resource, $date, $data);

            return $data;
        }

        throw new CurlException(sprintf('Cannot fetch %s', $url), $httpCode);
    }

    /**
     * Do a POST request.
     *
     * @param resource $ch
     * @param string $url
     * @throws CurlException
     * @return string
     */
    private function doPost($ch, $url)
    {
        $data = $this->curlExec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            throw new CurlException(sprintf('Cannot fetch %s', $url), $httpCode);
        }

        return $data;
    }

    /**
     * Exec the given cURL connection and return the content body.
     * @param resource $ch
     * @return string
     */
    private function curlExec($ch)
    {
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        return substr($response, $headerSize);
    }

    /**
     * Initialize a new cURL client.
     *
     * @param string $url
     * @param array  $params
     * @param string $method
     *
     * @return resource
     */
    private function curlInit($url, array $params, $method)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($method == HttpClient::POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        return $ch;
    }

    /**
     * Get the resource name from url removing the host part and the API key if provided.
     *
     * @param string $url
     * @return string
     */
    private function getResourceName($url)
    {
        $url = parse_url($url);
        $extension = pathinfo($url['path'], PATHINFO_EXTENSION);

        $resource = $url['path'];
        if(in_array($extension, array('xml', 'zip'))) {
            //Remove the api key from the resource name.
            $path = explode('/', $url['path']);
            unset($path[2]);
            $resource = implode('/', $path);
        }

        if (!empty($url['query'])) {
            $resource .= '?' . $url['query'];
        }

        return $resource;
    }
}