<?php

App::uses('SimpleJobBehavior', 'SimpleJob.Model/Behavior');
App::uses('SimpleJobUtility', 'SimpleJob.Lib');

/**
 * SimpleJobBehavior Test Case
 *
 */
class SimpleJobBehaviorTest extends CakeTestCase {

    public $fixtures = array('plugin.simple_job.simple_job');

/**
 * setUp method
 *
 * @return void
 */
    public function setUp() {
        parent::setUp();
        $config = SimpleJobUtility::getConfig();
        $model = $config['model'];
        $this->SimpleJob = ClassRegistry::init($model);
        $this->SimpleJob->Behaviors->load('SimpleJob.SimpleJob');
    }

/**
 * tearDown method
 *
 * @return void
 */
    public function tearDown() {
        unset($this->SimpleJob);

        parent::tearDown();
    }

    public function test01() {
        // ジョブの登録結果のチェック
        $handler1 = array(
            'task' => 'Hello',
            'options' => array(),
        );
        $result1 = $this->SimpleJob->enqueue($handler1, 'queue1');
        $this->assertTrue(!empty($result1));

        $job_id1 = $result1[$this->SimpleJob->alias][$this->SimpleJob->primaryKey];

        $query = array(
            'conditions' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => $job_id1,
            ),
            'contain' => false,
        );
        $job = $this->SimpleJob->find('first', $query);
        $this->assertTrue(!empty($job));
        $this->assertTrue($job[$this->SimpleJob->alias]['handler'] == serialize($handler1));
        $this->assertTrue($job[$this->SimpleJob->alias]['queue'] == 'queue1');
        $this->assertNull($job[$this->SimpleJob->alias]['locked_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['failed_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['error']);
        $this->assertNull($job[$this->SimpleJob->alias]['owner_id']);
        $this->assertNull($job[$this->SimpleJob->alias]['finished_at']);

        // ジョブを取り出した結果のチェック
        $job_id2 = $this->SimpleJob->getJob('queue1');
        $this->assertEquals($job_id1, $job_id2);

        $query = array(
            'conditions' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => $job_id1,
            ),
            'contain' => false,
        );
        $job = $this->SimpleJob->find('first', $query);
        $this->assertTrue(!empty($job));
        $this->assertTrue($job[$this->SimpleJob->alias]['handler'] == serialize($handler1));
        $this->assertTrue($job[$this->SimpleJob->alias]['queue'] == 'queue1');
        $this->assertTrue(!empty($job[$this->SimpleJob->alias]['locked_at']));
        $this->assertNull($job[$this->SimpleJob->alias]['failed_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['error']);
        $this->assertNull($job[$this->SimpleJob->alias]['owner_id']);
        $this->assertNull($job[$this->SimpleJob->alias]['finished_at']);

        // ジョブを正常終了させた結果のチェック
        $this->SimpleJob->finish($job_id1);

        $query = array(
            'conditions' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => $job_id1,
            ),
            'contain' => false,
        );
        $job = $this->SimpleJob->find('first', $query);
        $this->assertTrue(!empty($job));
        $this->assertTrue($job[$this->SimpleJob->alias]['handler'] == serialize($handler1));
        $this->assertTrue($job[$this->SimpleJob->alias]['queue'] == 'queue1');
        $this->assertTrue(!empty($job[$this->SimpleJob->alias]['locked_at']));
        $this->assertNull($job[$this->SimpleJob->alias]['failed_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['error']);
        $this->assertNull($job[$this->SimpleJob->alias]['owner_id']);
        $this->assertTrue(!empty($job[$this->SimpleJob->alias]['finished_at']));

        // ジョブを登録して異常終了させた結果のチェック
        $handler2 = array(
            'task' => 'Hello2',
            'options' => array(),
        );
        $result2 = $this->SimpleJob->enqueue($handler2, 'queue1');
        $this->assertTrue(!empty($result2));
        $job_id3 = $this->SimpleJob->getJob('queue2');
        $this->assertFalse($job_id3);
        $job_id3 = $this->SimpleJob->getJob('queue1');
        $this->assertTrue(!empty($job_id3));
        $this->assertEquals($result2[$this->SimpleJob->alias][$this->SimpleJob->primaryKey], $job_id3);

        $this->SimpleJob->finishWithError($job_id3, 'error test');

        $query = array(
            'conditions' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => $job_id3,
            ),
            'contain' => false,
        );
        $job = $this->SimpleJob->find('first', $query);
        $this->assertTrue(!empty($job));
        $this->assertTrue($job[$this->SimpleJob->alias]['handler'] == serialize($handler2));
        $this->assertTrue($job[$this->SimpleJob->alias]['queue'] == 'queue1');
        $this->assertTrue(!empty($job[$this->SimpleJob->alias]['locked_at']));
        $this->assertTrue(!empty($job[$this->SimpleJob->alias]['failed_at']));
        $this->assertEquals($job[$this->SimpleJob->alias]['error'], 'error test');
        $this->assertNull($job[$this->SimpleJob->alias]['owner_id']);
        $this->assertNull($job[$this->SimpleJob->alias]['finished_at']);

        $query = array(
            'order' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => 'ASC',
            ),
        );
        $result = $this->SimpleJob->find('all', $query);
    }

    public function test02() {
        // 複数ジョブの登録後の取出しのチェック
        $handler1 = array(
            'task' => 'Hello',
            'options' => array(),
        );
        $handler2 = array(
            'task' => 'Hello2',
            'options' => array(),
        );
        $result1 = $this->SimpleJob->enqueue($handler1, 'queue1');
        $result2 = $this->SimpleJob->enqueue($handler2, 'queue1');
        $this->assertTrue(!empty($result1));
        $this->assertTrue(!empty($result2));

        $job_id1 = $result1[$this->SimpleJob->alias][$this->SimpleJob->primaryKey];
        $query = array(
            'conditions' => array(
                $this->SimpleJob->alias . '.' . $this->SimpleJob->primaryKey => $job_id1,
            ),
            'contain' => false,
        );
        $job = $this->SimpleJob->find('first', $query);
        $this->assertTrue(!empty($job));
        $this->assertTrue($job[$this->SimpleJob->alias]['handler'] == serialize($handler1));
        $this->assertTrue($job[$this->SimpleJob->alias]['queue'] == 'queue1');
        $this->assertNull($job[$this->SimpleJob->alias]['locked_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['failed_at']);
        $this->assertNull($job[$this->SimpleJob->alias]['error']);
        $this->assertNull($job[$this->SimpleJob->alias]['owner_id']);
        $this->assertNull($job[$this->SimpleJob->alias]['finished_at']);
    }
}
