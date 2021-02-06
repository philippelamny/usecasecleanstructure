<?php

namespace Vietanywhere\UseCase\CreateCleanStructure\Helpers;


class Tools
{
    /**
     * @param string $content
     * @param string $filename
     * @param string $toDirectory
     * @return null|string
     */
    public static function createFileFromContent(
        string $content,
        string $filename,
        string $toDirectory
    )
    {
        if (!is_dir($toDirectory)) {
            static::createDirectory($toDirectory);
        }

        $to = $toDirectory . '/' . $filename;
        if (!file_put_contents($to, $content)) {
            return null;
        }

        return $to;
    }

    /**
     * @param string $dir
     * @param string $mode
     * @param bool $noError
     * @return bool
     */
    public static function createDirectory(string $dir, $mode = "0777", $noError = false): bool
    {
        if ((!is_dir($dir) && !mkdir($dir, $mode, true) && !is_dir($dir))) {
            if ($noError) {
                return false;
            }
            throw new \RuntimeException("Cannot create directory");
        }

        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function transformPathToNamespace(string $path) : string {
        return str_replace('/', '\\', $path);
    }
}