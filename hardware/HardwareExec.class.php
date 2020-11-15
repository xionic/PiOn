<?php

namespace PiOn\Hardware;

use \PiOn\Item\Value;
use \PiOn\Session;

use \Amp\Promise;

class HardwareExec extends Hardware {

	public const value_certainty = Value::CERTAIN;

	function __construct(String $name, String $node_name, array $capabilities, Object $args) {
		parent::__construct($name, $node_name, $capabilities,  $args);
		$this->type = "HardwareExec";
		$this->value_certainty = true;
	}

	function hardware_get(Session $session, Object $item_args): Promise {
		return \Amp\call(function () use ($session, $item_args) {
			return $this->do_exec($session, $item_args->get_command);
		});
	}

	function hardware_set(Session $session, Object $item_args, Value $value): Promise {
		return \Amp\call(function () use ($session, $item_args, $value) {
			return $this->do_exec($session, $item_args->set_command);
		});
	}

	function hardware_register(Session $session, Object $item_args, callable $callback): Promise {
		throw new OperationNotSupportedException("REGISTER not supported by Hardware '{$this->name}'");
	}

	private function do_exec(Session $session, String $cmd): String {
		plog("Hardware {$this->name} running shell command: {$cmd}", DEBUG, $session);
		return exec($cmd);
	}
}
