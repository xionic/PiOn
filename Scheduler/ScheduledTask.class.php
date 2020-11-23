<?php
namespace PiOn\Scheduler;

use \PiOn\Timer\Timer;
use \PiOn\Task\Task;
use \PiOn\Session;

class ScheduledTask {

    protected $task;
    protected $timer;


    function __construct(Task $task, Timer $timer){
        $this->task = $task;
        $this->timer = $timer;
    }

    function init(){
        $this->timer->set_task($this->task);
        $this->timer->init($this->task);
    }
    
    function start(){
        plog("Starting timer for ScheduledTask (Task name: {$this->task->name})", DEBUG, Session::$INTERNAL);
        $this->timer->start();
    }
}

?>