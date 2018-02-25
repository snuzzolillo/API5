<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of API5 RESTful SQLtoJSON                           |
 | Copyright (C) 2017-2018, Santo Nuzzolillo                             |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the LICENSE file for a full license statement.                    |
 |                                                                       |
 | Production                                                            |
 |   Date   : 02/25/2018                                                 |
 |   Time   : 10:44:11 AM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/

class BeforeValidException extends UnexpectedValueException
{

    function BeforeValidException($e)
    {
                $this->error_manager($e, -3);
    }

    private static function error_manager ($msg, $code=3){
        error_manager("BAD REQUEST $msg", $code);
                    }
}