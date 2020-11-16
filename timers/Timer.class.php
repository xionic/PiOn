<?php
namespace PiOn\Timer;


interface Timer {
	
	public function start(Task $task): void;
	public function init_schedule(): void;
		
}

?>