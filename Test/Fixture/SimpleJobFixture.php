<?php
/**
 * SimpleJobFixture
 *
 */
class SimpleJobFixture extends CakeTestFixture {

    /* public $name = 'SimpleJob'; */

    public $fields = array(
        'id' => array('type' => 'integer', 'null' => false, 'key' => 'primary'),
        'handler' => array('type' => 'text', 'null' => false),
        'queue' => array('type' => 'text', 'null' => false),
        'locked_at' => array('type' => 'timestamp', 'null' => true, 'default' => null),
        'failed_at' => array('type' => 'timestamp', 'null' => true, 'default' => null),
        'error' => array('type' => 'text', 'null' => true, 'default' => null),
        'owner_id' => array('type' => 'integer', 'null' => true, 'default' => null),
        'finished_at' => array('type' => 'timestamp', 'null' => true, 'default' => null),
        'created' => array('type' => 'timestamp', 'null' => true, 'default' => null),
        'modified' => array('type' => 'timestamp', 'null' => true, 'default' => null),
        //'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
    );

/**
 * Records
 *
 * @var array
 */
    public $records = array();

    public function __construct() {
        $this->records = array(
            array(
                'id' => 1,
                'handler' => serialize(array('task' => 'Hello', 'options' => array())),
                'queue' => 'default',
                'locked_at' => null,
                'failed_at' => null,
                'error' => null,
                'owner_id' => null,
                'finished_at' => null,
                'created' => '2013-11-01 00:00:00',
                'modified' => '2013-11-01 00:00:00',
            ),
        );
        parent::__construct();
    }
}
