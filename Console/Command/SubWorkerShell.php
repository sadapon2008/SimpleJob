<?php

App::uses('AppShell', 'Console/Command');
App::uses('SimpleJobUtility', 'SimpleJob.Lib');

/**
 * WorkerShellからexecで実行される子プロセス
 */
class SubWorkerShell extends AppShell {

    protected function _welcome() {
    }

    public function main() {
        // コマンドライン引数の第一引数をジョブIDとする
        $job_id = null;
        if(count($this->args) > 0) {
            $job_id = $this->args[0];
        }
        if(empty($job_id)) {
            $this->_stop(1);
        }

        $config = SimpleJobUtility::getConfig();

        // ジョブのクラスをロードする
        $model = $config['model'];
        $this->{$model} = ClassRegistry::init($model);
        $this->{$model}->Behaviors->load('SimpleJob.SimpleJob');

        // ジョブの実行情報を取得する
        $handler = $this->{$model}->getHandler($job_id);
        if(empty($handler)) {
            $this->_stop(2);
        }

        // タスクとオプションを復元する
        $task = $handler['task'];
        $options = $handler['options'];

        // 実行
        $Task = $this->Tasks->load($task);
        $Task->run($options);
    }
}
