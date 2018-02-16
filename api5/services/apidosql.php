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
 |   Date   : 02/16/2018                                                 |
 |   Time   : 12:47:27 PM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/



class clsDMLResult {


    


    function __construct($SQL) {
        $this->exectuteDMLStatement($SQL);
    }

    function clsDMLResult($SQL)
    {
        self::__construct($SQL);
    }

    function exectuteDMLStatement($SQL = '')
    {
                                
        if (substr($SQL, 0, 1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL, 1));
            $SQL = clsCore::getSqlParsed($sqlParsed);
        }

                                global $transactiontype;
        clsCORE::validateSqlStatement($SQL, $transactiontype);

        global $BIND;
        $bind = is_object($BIND) ? $BIND : new stdClass();
        $bind->{'___lastkey'} = ''; 
        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                             , $SQL                          , $bind                     );


        $DB_TYPE = $db->Type;

        if ($DB_TYPE == "Oracle") {

                        

            $db->query("BEGIN ' . $SQL . '; END;");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

                                                                                                $lastkey = "?";         } else if ($DB_TYPE == "MySQL"){

            $db->query("'.$SQL.'");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

                                                
                                                            
            $lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);

        }

                                                        clsCore::returnJson(
            false
            , '{"CODE":"0", "MESSAGE" : "SUCCESS"}'
            , "INFO"
            , false
            , "$result"         );
    }

}

