<?php

use dokuwiki\Extension\CLIPlugin;
use dokuwiki\Extension\Plugin;

/**
 * DokuWiki Plugin removeold (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */
class helper_plugin_removeold extends Plugin
{
    /** @var CLIPlugin|admin_plugin_removeold */
    protected $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }

    public function run($dryrun = true)
    {
        $files = $this->getDeletedFiles();
        foreach ($files as $base => $file) {
            if (file_exists($file)) {
                $list = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $this->deleteFiles($list, $base, $dryrun);
            }
        }
    }

    protected function getDeletedFiles()
    {
        $files = [
            DOKU_INC => DOKU_INC . 'data/deleted.files',
        ];

        foreach (glob(DOKU_PLUGIN . '*', GLOB_ONLYDIR) as $plugin) {
            $files[$plugin] = $plugin . 'deleted.files';
        }

        foreach (glob(DOKU_INC . 'lib/tpl/*', GLOB_ONLYDIR) as $template) {
            $files[$template] = $template . '/deleted.files';
        }

        return $files;
    }

    /**
     * Delete the given files from the base
     * @param string[] $list
     * @param string $base
     * @param bool $dryrun
     * @return void
     */
    protected function deleteFiles($list, $base, $dryrun = true)
    {
        $base = rtrim($base, '/') . '/';

        foreach ($list as $line) {
            $line = preg_replace('/\.\./', '', $line); // prevent directory traversal
            $line = preg_replace('/#.*$/', '', $line); // remove comments
            $line = str_replace('\\', '/', $line); // normalize windows paths
            $line = trim($line); // remove leading/trailing whitespace
            $line = ltrim($line, '/'); // remove leading slashes
            if (!$line) continue;

            $file = $base . $line;

            // file does not exist, that's fine
            if (!file_exists($file)) {
                $this->log('debug', $this->getLang('rm_notfound'), $file);
                continue;
            }

            // check that the given file is a case sensitive match
            if (basename(realpath($file)) !== basename($file)) {
                $this->log('info', $this->getLang('rm_mismatch'), $file);
                continue;
            }

            if (
                $dryrun ||
                (is_dir($file) && $this->recursiveDelete($file)) ||
                @unlink($file)
            ) {
                $this->log('success', $this->getLang('rm_done'), $file);
            } else {
                $this->log('error', $this->getLang('rm_fail'), $file);
            }
        }

        // clear opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Recursive delete
     *
     * @author Jon Hassall
     * @link   http://de.php.net/manual/en/function.unlink.php#87045
     */
    protected function recursiveDelete($dir)
    {
        if (!$dh = @opendir($dir)) {
            return false;
        }
        while (false !== ($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..') continue;

            if (!@unlink($dir . '/' . $obj)) {
                $this->recursiveDelete($dir . '/' . $obj);
            }
        }
        closedir($dh);
        return @rmdir($dir);
    }


    /**
     * Log a message
     *
     * @param string $level
     * @param $msg
     * @param mixed ...$args
     */
    protected function log($level, $msg, ...$args)
    {
        $msg = vsprintf($msg, $args);
        if ($this->logger) {
            $this->logger->log($level, $msg);
        } else {
            echo $msg;
        }
    }
}
