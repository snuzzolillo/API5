<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of API5 RESTful SQLtoJSON                           |
 | Copyright (C) 2007-2018, Santo Nuzzolillo                             |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the LICENSE file for a full license statement.                    |
 |                                                                       |
 | Pduction                                                              |
 |   Date   : 02/12/2018                                                 |
 |   Time   : 05:30:44 PM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/



class SignatureInvalidException extends UnexpectedValueException
{

    function SignatureInvalidException($e)
    {
                $this->error_manager($e, -1);
    }

    private static function error_manager ($msg, $code=3){
        $e = '{"ERROR" : {"CODE":"' . $code . '", "MESSAGE" : "' . "BAD REQUEST $msg".'"}}';
        die($e);
    }
}
