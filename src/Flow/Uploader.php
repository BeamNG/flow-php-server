<?php
namespace Flow;

class Uploader {

    /**
     * @param string $destination where to save file
     * @param string|ConfigInterface $config
     * @param Request $request optional
     * @return bool
     */
    public static function save($destination, $config, Request $request = null)
    {
        if (!$config instanceof ConfigInterface) {
            $config = new Config(array(
                'tempDir' => $config
            ));
        }
        $file = new File($config, $request);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($file->checkChunk()) {
                header("HTTP/1.1 200 Ok");
            } else {
                header("HTTP/1.1 404 Not Found");
                return false;
            }
        } else {
            if ($file->validateChunk()) {
                $file->saveChunk();
            } else {
                // error, invalid chunk upload request, retry
                header("HTTP/1.1 400 Bad Request");
                return false;
            }
        }
        if ($file->validateFile() && $file->save($destination)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete chunks older than expiration time.
     * @param string $chunksFolder
     * @param int $expirationTime seconds
     * @throws Exception
     */
    public static function pruneChunks($chunksFolder, $expirationTime = 172800)
    {
        $handle = opendir($chunksFolder);
        if (!$handle) {
            throw new Exception('Failed to open folder');
        }
        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == "..") {
                continue;
            }
            $path = $chunksFolder . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                continue;
            }
            if (time() - filemtime($path) > $expirationTime) {
                unlink($path);
            }
        }
        closedir($handle);
    }
}