<?php

namespace libs\Moinax\TVDB\Http\Cache;

interface Cache
{

    public function getDate($resource);

    public function setDate($resource, $date);

    public function cache($resource, $date, $content);

    public function getContent($resource);
}
