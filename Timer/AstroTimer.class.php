<?php

namespace PiOn\Timer;

use \PiOn\Session;
use \PiOn\Config;

class AstroTimer extends Timer {

    private $type;
    private $offset;

    /**
     * Type; "sunset", "sunrise"
     * Offset in seconds. Positive for after the astro event, negative before.
     */
    function __construct($type, $offset = 0) {
        $this->type = $type;
        $this->offset = $offset;
    }

    function start(): void {
        plog("Starting AstroTimer with type '$this->type' and offset: '$this->offset secs'", DEBUG, Session::$INTERNAL);

        $this->register_next();
    }
    function register_next(): void {
        $delay_time = $this->calc_next_delta();
        $this->register_delay($delay_time);
    }

    function register_delay(int $delta): void {
        $this->schedule_id = $this->schedule_delta($delta * 1000, function () {
            $this->register_next();
        });
    }

    /**
     * Returns the time in seconds until the next firing of this timer
     */
    function calc_next_delta(): int {
        $event_time = null;
        switch ($this->type) {
            case "sunset":
                $event_time = date_sunset(self::local_time(), SUNFUNCS_RET_TIMESTAMP, Config::get("latitude"), Config::get("longitude"), 91);
                break;

            case "sunrise":
                $event_time = date_sunrise(self::local_time(), SUNFUNCS_RET_TIMESTAMP, Config::get("latitude"), Config::get("longitude"), 90);
                break;

            default:
                plog("Unknown AstroTimer type: {$this->type}", ERROR, Session::$INTERNAL);
        }
        $real_time = $event_time + $this->offset - self::local_time();
        if($real_time < 0) { //already happened today - we're looking for the next one
            $real_time += 86400; //plus one day
        }
        plog("AstroTimer: time of {$this->type} = $event_time; adjusted to $real_time", DEBUG, Session::$INTERNAL);
        return $real_time;
    }

    function init(): void {
    }

    function cancel(): void {
        $this->cancel($this->schedule_id);
    }
}
