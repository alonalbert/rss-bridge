<?php
require_once(__DIR__ . '/BridgeInterface.php');
/**
 * Extension of BridgeAbstract allowing caching of files downloaded over http.
 * TODO allow file cache invalidation by touching files on access, and removing
 * files/directories which have not been touched since ... a long time
 */
abstract class HttpCachingBridgeAbstract extends BridgeAbstract {

    /**
     * Maintain locally cached versions of pages to download, to avoid multiple downloads.
     * @param url url to cache
     * @param duration duration of the cache file in seconds (default: 24h/86400s)
     * @return content of the file as string
     */
    public function get_cached($url, $duration = 86400){
        $filepath = $this->buildCacheFilePath($url);

        if(file_exists($filepath) && filectime($filepath) < time() - $duration){
            $this->debugMessage('Cache file ' . $filepath . ' exceeded duration of ' . $duration . ' seconds.');
            unlink ($filepath);
            $this->debugMessage('Cached file deleted: ' . $filepath);
        }

        if(file_exists($filepath)){
            $this->debugMessage('loading cached file from ' . $filepath . ' for page at url ' . $url);
            // TODO touch file and its parent, and try to do neighbour deletion
            touch($filepath);
            $content = file_get_contents($filepath);
        } else {
            $this->debugMessage('we have no local copy of ' . $url . ' Downloading to ' . $filepath);
            $dir = substr($filepath, 0, strrpos($filepath, '/'));

            if(!is_dir($dir)){
                $this->debugMessage('creating directories for ' . $dir);
                mkdir($dir, 0777, true);
            }

            $content = $this->getContents($url);
            if($content !== false){
                file_put_contents($filepath, $content);
            }
        }

        return str_get_html($content);
    }

    private function buildCacheFilePath($url){
        return __DIR__ . '/../cache/pages/' . sha1($url) . '.cache';
    }
}