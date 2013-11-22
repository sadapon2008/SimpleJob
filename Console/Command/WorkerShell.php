<?php

App::uses('AppShell', 'Console/Command');
App::uses('SimpleJobUtility', 'SimpleJob.Lib');
App::uses('SimpleLock', 'SimpleJob.Vendor');

/**
 * ジョブキューからジョブを取り出して子プロセスで実行する
 */
class WorkerShell extends AppShell {

    public $queue = 'default';

    protected function _welcome() {
    }

    public function main() {
        // コマンドライン引数の第一引数をキュー名の指定とする
        if(count($this->args) > 0) {
            $this->queue = $this->args[0];
        }

        // キューをロックする
        $lock = SimpleJobUtility::lockQueue($this->queue);
        if(empty($lock)) {
            // ロック失敗
            $this->_stop(1);
        }

        try {
            $config = SimpleJobUtility::getConfig();
            $model = $config['model'];
            $this->{$model} = ClassRegistry::init($model);
            $this->{$model}->Behaviors->load('SimpleJob.SimpleJob');

            $max_count = $config['count'];
            $count = 0;

            while(($max_count == 0) || ($count < $max_count)) {
                // 実行待ちのジョブIDを取り出す
                $job_id = $this->{$model}->getJob($this->queue);
                if(empty($job_id)) {
                    // なければ終了
                    break;
                }
                $count += 1;

                // 別プロセスでジョブを実行する
                $cmd = APP . 'Console' . DS . 'cake -root ' . ROOT . ' SimpleJob.sub_worker ' . $job_id;

                SimpleJobUtility::log_debug('[SimpleJob] cmd: ' . $cmd);

                $cmd_result = exec($cmd, $cmd_output, $cmd_return_var);

                SimpleJobUtility::log_debug('[SimpleJob] cmd_return_var: ' . $cmd_return_var);

                if($cmd_return_var == 0) {
                    $this->{$model}->finish($job_id);
                } else {
                    $this->{$model}->finishWithError($job_id, implode("\n", $cmd_output));
                }
            }
        } catch(Exception $e) {
        }

        SimpleJobUtility::unlockQueue($lock);
    }
}
