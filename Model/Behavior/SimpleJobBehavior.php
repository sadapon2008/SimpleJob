<?php

App::uses('ModelBehavior', 'Model');
App::uses('SimpleJobUtility', 'SimpleJob.Lib');
App::uses('Sanitize', 'Utility');

class SimpleJobBehavior extends ModelBehavior {

    /**
     * ジョブをキューに登録する
     *
     * @param Model $Model Model using the behavior
     * @param array $handler Settings to trigger background job
     * @param string $queue Queue name
     * @param integer @owner_id Job's owner id
     * @return mixed If failed, return false, otherwise, return Model::save()
     */
    public function enqueue(Model $Model, $handler, $queue, $owner_id = null, $trigger_flg = true) {
        $data = array(
            'handler' => serialize($handler),
            'queue' => $queue,
            'owner_id' => $owner_id,
        );
        $Model->create();
        $result = $Model->save($data);
        if(empty($result)) {
            SimpleJobUtility::log_debug('[SimpleJob] failed to enqueue new job');
            return false;
        }
        if($trigger_flg) {
            SimpleJobUtility::triggerQueue($queue);
        }
        return $result;
    }

    /**
     * 最後に登録した未完了ジョブを取得する
     *
     * @param Model $Model Model using the behavior
     * @param string $queue Queue name
     * @return boolean
     */
    public function getJob(Model $Model, $queue) {
        // 未完了のものだけが対象
        $query = array(
            'conditions' => array(
                $Model->alias . '.queue' => $queue,
                $Model->alias . '.locked_at' => null,
                $Model->alias . '.failed_at' => null,
                $Model->alias . '.finished_at' => null,
            ),
            'order' => array(
                $Model->alias . '.' . $Model->primaryKey => 'ASC',
            ),
            'limit' => 1,
        );

        $job = $Model->find('first', $query);
        if(empty($job)) {
            return false;
        }

        $result = $Model->acquireLock($job[$Model->alias][$Model->primaryKey]);
        if(empty($result)) {
            return false;
        }

        return $job[$Model->alias][$Model->primaryKey];
    }

    /**
     * ジョブを実行するためにロックする
     *
     * @param Model $Model Model using the behavior
     * @param integer $job_id Job's id
     * @return boolean
     */
    public function acquireLock(Model $Model, $job_id) {
        $timestamp_now = "'" . date('Y-m-d H:i:s') . "'";
        $data = array(
            'locked_at' => $timestamp_now,
            'modified' => $timestamp_now,
        );
        $conditions = array(
            $Model->primaryKey => $job_id,
            'locked_at' => null,
            'failed_at' => null,
        );
        return $Model->updateAll($data, $conditions);
    }

    /**
     * ジョブのロックを強制解除する
     *
     * @param Model $Model Model using the behavior
     * @param integer $job_id Job's id
     * @return boolean
     */
    public function releaseLock(Model $Model, $job_id) {
        $timestamp_now = "'" . date('Y-m-d H:i:s') . "'";
        $data = array(
            'locked_at' => null,
            'modified' => $timestamp_now,
        );
        $conditions = array(
            $Model->primaryKey => $job_id,
        );
        $Model->updateAll($data, $conditions);
    }

    /**
     * ジョブを正常終了させる
     *
     * @param Model $Model Model using the behavior
     * @param integer $job_id Job's id
     * @return boolean
     */
    public function finish(Model $Model, $job_id) {
        $timestamp_now = "'" . date('Y-m-d H:i:s') . "'";
        $data = array(
            'finished_at' => $timestamp_now,
            'modified' => $timestamp_now,
        );
        $conditions = array(
            $Model->primaryKey => $job_id,
        );
        $Model->updateAll($data, $conditions);

        SimpleJobUtility::log_debug('[SimpleJob] finished job(' . $job_id . ')');
    }

    /**
     * ジョブを異常終了させる
     *
     * @param Model $Model Model using the behavior
     * @param integer $job_id Job's id
     * @return boolean
     */
    public function finishWithError(Model $Model, $job_id, $error = null) {
        $timestamp_now = "'" . date('Y-m-d H:i:s') . "'";
        $data = array(
            'failed_at' => $timestamp_now,
            'error' => "'". Sanitize::escape($error) . "'",
            'modified' => $timestamp_now,
        );
        $conditions = array(
            $Model->primaryKey => $job_id,
        );
        CakeLog::write(LOG_DEBUG, print_r(array($data, $conditions), true));
        try {
            $result = $Model->updateAll($data, $conditions);
            CakeLog::write(LOG_DEBUG, print_r($result, true));
        } catch(Exception $e) {
            CakeLog::write(LOG_DEBUG, print_r($e, true));
        }

        SimpleJobUtility::log_debug('[SimpleJob] failure in job(' . $job_id . ')');
    }

    /**
     * ジョブの実行情報を取得する
     *
     * @param Model $Model Model using the behavior
     * @param integer $job_id Job's id
     * @return boolean
     */
    public function getHandler(Model $Model, $job_id) {
        $query = array(
            'conditions' => array(
                $Model->alias . '.' . $Model->primaryKey => $job_id,
            ),
            'fields' => array(
                $Model->alias . '.' . $Model->primaryKey,
                $Model->alias . '.handler',
            ),
            'order' => array(
                $Model->alias . '.' . $Model->primaryKey => 'ASC',
            ),
        );
        $result = $Model->find('first', $query);
        if(empty($result)) {
            return false;
        }
        return unserialize($result[$Model->alias]['handler']);
    }
}
