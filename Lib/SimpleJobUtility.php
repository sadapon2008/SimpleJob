<?php

App::uses('SimpleLock', 'SimpleJob.Vendor');

class SimpleJobUtility {

    public static $config_default = array(
        'model' => 'SimpleJob',
        'debug' => false,
        'count' => 0,
        'dirMode' => 0777,
        'fileMode' => 0666,
        'triggerFile' => 'trigger',
    );

    public static function getConfig() {
        $config = Hash::merge(self::$config_default, Configure::read('SimpleJob.config'));

        if(empty($config['queue'])) {
            $config = Hash::merge(
                $config,
                array(
                    'queue' => array(
                        'default' => array(
                            'lockFile' => TMP . 'simple_job.lock',
                            'trigger' => true,
                            'triggerDir' => TMP . 'simple_job_trigger'
                        ),
                    ),
                )
            );
        }

        return $config;
    }

    public static function log_debug($msg) {
        if(!is_scalar($msg)) {
            $msg = print_r($msg, true);
        }
        $config = self::getConfig();
        if(array_key_exists('debug', $config) && $config['debug']) {
            CakeLog::write(LOG_DEBUG, $msg);
        }
    }

    public static function triggerQueue($queue) {
        $config = self::getConfig();
        if(empty($config['queue'][$queue]['trigger'])) {
            return true;
        }
        if(empty($config['queue'][$queue]['triggerDir'])) {
            return false;
        }
        $triggerDir = $config['queue'][$queue]['triggerDir'];

        $old = umask(0);

        if(!file_exists($triggerDir)) {
            if(!mkdir($triggerDir, $config['dirMode'])) {
                umask($old);
                return false;
            }
        }

        $triggerFile = $triggerDir . DS . $config['triggerFile'];
        if(file_exists($triggerFile)) {
            @unlink($triggerFile);
        }
        @touch($triggerFile);
        chmod($triggerFile, $config['fileMode']);

        umask($old);
        return true;
    }

    public static function lockQueue($queue) {
        $config = self::getConfig();
        if(empty($config['queue'][$queue]['lockFile'])) {
            return false;
        }
        $lockFile = $config['queue'][$queue]['lockFile'];
        return SimpleLock::lockEx($lockFile);
    }

    public static function unlockQueue(SimpleLock $lock) {
        $lock->unclok();
    }
}
