<?php

class SimpleJobSchema extends CakeSchema {
    public $simple_jobs = array(
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
        'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
    );
}
