<?php
namespace Moinax\TvDb\Http\Cache;

class FilesystemCache implements Cache
{

    private $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     *
     * @see \Moinax\TvDb\Http\Cache\Cache::getDate()
     */
    public function getDate($resource)
    {
        $path = $this->getPath($resource);
        if(!file_exists($path)) {
            return -1;
        }
        return filemtime($path);
    }

    /**
     *
     * @see \Moinax\TvDb\Http\Cache\Cache::setDate()
     */
    public function setDate($resource, $date)
    {
        $path = $this->getPath($resource);

        if(! file_exists($path)) {
            return;
        }

        touch($path, $date);
    }

    /**
     *
     * @see \Moinax\TvDb\Http\Cache\Cache::cache()
     */
    public function cache($resource, $date, $content)
    {
        $path = $this->getPath($resource);

        $dirname = dirname($path);
        if(!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        file_put_contents($path, $content);
        $this->setDate($resource, $date);
    }

    /**
     *
     * @see \Moinax\TvDb\Http\Cache\Cache::getContent()
     */
    public function getContent($resource)
    {
        $path = $this->getPath($resource);

        if(! file_exists($path)) {
            return '';
        }

        return file_get_contents($path);
    }

    private function getPath($resource) {
        return $this->directory . $resource;
    }
}