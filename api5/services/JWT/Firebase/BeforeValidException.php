<?php

#namespace Firebase\JWT;

class BeforeValidException extends UnexpectedValueException
{

    function BeforeValidException($e)
    {
        //global $sourceName;
        $this->error_manager($e, -3);
    }

    private static function error_manager ($msg, $code=3){
        $e = '{"ERROR" : {"CODE":"' . $code . '", "MESSAGE" : "' . "BAD REQUEST $msg".'"}}';
        die($e);
    }
}