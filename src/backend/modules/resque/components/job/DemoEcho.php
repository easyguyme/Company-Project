<?php
namespace backend\modules\resque\components\job;

use backend\components\resque\SchedulerJob;

/**
* Job for ClassJob
*/
class DemoEcho extends SchedulerJob
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @args {"Description":"hello, demo"}
     * @see \backend\components\resque\SchedulerJob::perform()
     */
    public function perform()
    {
        echo 'a';
    }
}
