<?php
namespace PiOn\Event;

use \PiOn\Event\Task;

interface Timer {
	
	public function start(Task $task): void;
	public function init_schedule(): void;
		
}

?>