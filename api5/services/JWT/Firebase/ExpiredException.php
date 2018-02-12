<?php

#namespace Firebase\JWT;

class ExpiredException extends UnexpectedValueException
{

    function ExpiredException($e)
    {
        //global $sourceName;
        $this->error_manager($e, -2);
    }

    private static function error_manager ($msg, $code=3){
        $e = '{"ERROR" : {"CODE":"' . $code . '", "MESSAGE" : "' . "BAD REQUEST $msg".'"}}';
        die($e);
    }
}