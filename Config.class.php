<?php

namespace PiOn;

class Config {
    private static $config;
    
    public static function init(){
        if(self::$config == null){
            self::$config = new \stdclass;
        }
    }

    public static function get(string $name) {
        if (!property_exists(self::$config, $name)) {
            throw new ConfigNotFoundException("Config item not found: '{$name}'");
        }
        return self::$config->$name;
    }

    public static function set(string $name, $value) {
        self::$config->$name = $value;
    }
}

Config::init();

class ConfigNotFoundException extends \Exception {
}
