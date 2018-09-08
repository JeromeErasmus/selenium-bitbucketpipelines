<?php
/**
 * Created by PhpStorm.
 * User: lorenzo
 * Date: 4/06/18
 * Time: 11:55 AM
 */

namespace App\Services;

use Elf\Core\Module;

class Logger extends Module
{
    private $filename;

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function info($message) {
        return $this->log('[INFO] ' . $message);
    }

    public function debug($message) {
        return $this->log('[DEBUG] ' . $message);
    }

    public function error($message) {
        return $this->log('[ERROR] ' . $message);
    }

    private function log( $message ) {
        $uniqueId = $this->generateUniqueId();

        list($usec, $sec) = explode(" ", microtime());
        $dtime = date( "Y-m-d H:i:s." . sprintf( "%03d", (int)(1000 * $usec) ), $sec );
        $entry_line = $dtime . "\t" . $uniqueId . "\t" . $message . "\r\n";
        $filename = $this->filename . "_" . date( "Ymd" ) . ".log";
        $fp = fopen( $filename, "a" );
        fputs( $fp, $entry_line );
        fclose( $fp );
    }

    private function generateUniqueId() {

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        return session_id();
    }
}