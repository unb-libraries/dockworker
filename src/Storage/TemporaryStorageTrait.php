<?php

namespace Dockworker\Storage;

/**
 * Provides methods to interact with persistent configuration.
 */
trait TemporaryStorageTrait
{

    public static function createTemporaryLocalStorage(string $identifier = ''): string {
        $tmp_prefix = "dockworker-$identifier";
        $tempfile=tempnam(sys_get_temp_dir(), $tmp_prefix);
        // tempnam creates file on disk
        if (file_exists($tempfile)) { unlink($tempfile); }
        mkdir($tempfile);
        if (is_dir($tempfile)) { return $tempfile; }
        return '';
    }

}
