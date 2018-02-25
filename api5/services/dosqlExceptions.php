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
 |   Time   : 03:57:51 PM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/

class sqlExceptions {

    private $CODES;

    private $ACCESS_INTO_NULL;
    private $CASE_NOT_FOUND;
    private $COLLECTION_IS_NULL;
    private $CURSOR_ALREADY_OPEN;
    private $DUP_VAL_ON_INDEX;
    private $INVALID_CURSOR;
    private $INVALID_NUMBER;
    private $LOGIN_DENIED  ;
    private $NO_DATA_FOUND ;
    private $NOT_LOGGED_ON ;
    private $PROGRAM_ERROR ;
    private $ROWTYPE_MISMATCH ;
    private $SELF_IS_NULL     ;
    private $STORAGE_ERROR    ;
    private $SUBSCRIPT_BEYOND_COUNT ;
    private $SUBSCRIPT_OUTSIDE_LIMIT ;
    private $SYS_INVALID_ROWID      ;
    private $TIMEOUT_ON_RESOURCE    ;
    private $TOO_MANY_ROWS          ;
    private $VALUE_ERROR            ;
    private $ZERO_DIVIDE            ;
    private $OTHERS                 ;
    private $EXCEPTIONS;

    function __construct()
    {

        $this->CODES = array();
        $this->CODES['ORACLE6530'] = 'ACCESS_INTO_NULL';
        $this->CODES['ORACLE6592'] = 'CASE_NOT_FOUND';
        $this->CODES['ORACLE6531'] = 'COLLECTION_IS_NULL';
        $this->CODES['ORACLE6511'] = 'CURSOR_ALREADY_OPEN';
        $this->CODES['ORACLE1'] = 'DUP_VAL_ON_INDEX';
        $this->CODES['ORACLE1001'] = 'INVALID_CURSOR';
        $this->CODES['ORACLE1722'] = 'INVALID_NUMBER';
        $this->CODES['ORACLE1017'] = 'LOGIN_DENIED';
        $this->CODES['ORACLE1403'] = 'NO_DATA_FOUND';
        $this->CODES['ORACLE1012'] = 'NOT_LOGGED_ON';
        $this->CODES['ORACLE6501'] = 'PROGRAM_ERROR';
        $this->CODES['ORACLE6504'] = 'ROWTYPE_MISMATCH';
        $this->CODES['ORACLE30625'] = 'SELF_IS_NULL';
        $this->CODES['ORACLE6500'] = 'STORAGE_ERROR';
        $this->CODES['ORACLE6533'] = 'SUBSCRIPT_BEYOND_COUNT';
        $this->CODES['ORACLE6532'] = 'SUBSCRIPT_OUTSIDE_LIMIT';
        $this->CODES['ORACLE1410'] = 'SYS_INVALID_ROWID';
        $this->CODES['ORACLE51'] = 'TIMEOUT_ON_RESOURCE';
        $this->CODES['ORACLE1422'] = 'TOO_MANY_ROWS';
        $this->CODES['ORACLE6502'] = 'VALUE_ERROR';
        $this->CODES['ORACLE1476'] = 'ZERO_DIVIDE';
                        $this->CODES['MYSQL1339'] = 'CASE_NOT_FOUND';
                $this->CODES['MYSQL1325'] = 'CURSOR_ALREADY_OPEN';
        $this->CODES['MYSQL1022'] = 'DUP_VAL_ON_INDEX';
        $this->CODES['MYSQL1326'] = 'INVALID_CURSOR';
        $this->CODES['MYSQL1367'] = 'INVALID_NUMBER';
        $this->CODES['MYSQL1045'] = 'LOGIN_DENIED';
        $this->CODES['MYSQL1329'] = 'NO_DATA_FOUND';
                                                                        $this->CODES['MYSQL1205'] = 'TIMEOUT_ON_RESOURCE';
        $this->CODES['MYSQL1172'] = 'TOO_MANY_ROWS';
        $this->CODES['MYSQL1367'] = 'VALUE_ERROR';
        $this->CODES['MYSQL1365'] = 'ZERO_DIVIDE';
                        $this->CODES['POSTGRES20000'] = 'CASE_NOT_FOUND';
                        $this->CODES['POSTGRES23505'] = 'DUP_VAL_ON_INDEX';
                        $this->CODES['POSTGRES28000'] = 'LOGIN_DENIED';
        $this->CODES['POSTGRESP0002'] = 'NO_DATA_FOUND';
                                                                
    }

    public function getException($code, $dbtype) {
        if (isset($this->CODES[strtoupper($dbtype).$code])) {
            return $this->CODES[strtoupper($dbtype).$code];
        } else {
            return 'OTHER';
        }
    }
}
