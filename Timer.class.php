<?php
namespace PiOn\Event;

interface Timer {
	
	public function start(Callable $callable): void;
	public function init_schedule(): void;
		
}

?>