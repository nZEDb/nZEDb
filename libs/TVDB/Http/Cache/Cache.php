<?php
namespace Moinax\TvDb\Http\Cache;

interface Cache
{

    public function getDate($resource);

    public function setDate($resource, $date);

    public function cache($resource, $date, $content);

    public function getContent($resource);
}