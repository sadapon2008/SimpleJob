<?php

class SimpleLock {
    private $fp = false;

    const TRY_MAX = 5;
    const TRY_USLEEP = 1000; // 1ms

    private function __construct() {
    }

    private static function lock($lockfilePath, $mode) {
        $fp = fopen($lockfilePath, 'w');
        if($fp === null) {
            return null;
        }
        $count = 0;
        for($count = 0; $count < self::TRY_MAX; $count += 1) {
            if(flock($fp, $mode)) {
                break;
            }
            usleep(self::TRY_USLEEP);
        }
        if($count == self::TRY_MAX) {
            fclose($fp);
            return null;
        }
        $c = __CLASS__;
        $instance = new $c;
        $instance->fp = $fp;
        return $instance;
    }

    public static function lockShare($lockfilePath) {
        return self::lock($lockfilePath, LOCK_SH | LOCK_NB);
    }

    public static function lockEx($lockfilePath) {
        return self::lock($lockfilePath, LOCK_EX | LOCK_NB);
    }

    public function unlock() {
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        $this->fp = false;
    }
}
