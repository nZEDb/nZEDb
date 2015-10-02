<?php
namespace Moinax\TvDb\Http;

interface HttpClient
{

    const POST = 'post';

    const GET = 'get';

    public function fetch($url, array $params = array(), $method = self::GET);
}