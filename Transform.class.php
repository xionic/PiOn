<?php

namespace PiOn\Transform;

use \PiOn\Item\Value;

class Transform {

    public function transform_get($data): Value{
        return new Value($data);
    }
    public function transform_set($data){
        return $data;
    }

}

