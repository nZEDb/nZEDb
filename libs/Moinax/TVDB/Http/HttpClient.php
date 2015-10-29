<?php
namespace libs\Moinax\TVDB\Http;

interface HttpClient
{

    const POST = 'post';

    const GET = 'get';

    public function fetch($url, array $params = array(), $method = self::GET);
}
