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



$SYSTEM     = new stdClass(); $GLOBALS    = new stdClass(); $PARAMETERS = new stdClass(); $BINDED     = new stdClass(); $BINDED_IN_SQL = array(); 
$SYSTEM->{"SYSDATE"}     = 'date';

$GLOBALS->{"username"}     = 'any user';

class clsCore {

    public static function parse_raw_http_request()
    {
                        
                $input = file_get_contents('php://input');

                        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        if (count($matches)) {
                        $boundary = $matches[1];
        } else {
            $boundary = "";
        }
                $a_blocks = preg_split("/--+$boundary/", $input);
        foreach ($a_blocks as $id => $block)
        {
            if (empty($block))
                continue;

            
                        if (strpos($block, 'application/octet-stream') !== FALSE)
            {
                                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
                        else
            {
                                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                            }
                        if (isset($matches[1])) $a_data[$matches[1]] = $matches[2];
        }
        return $a_data;
    }

    public static function validateSqlStatement($SQL, $type)
    {
                                                if (strtoupper($type) == 'TABLE') {
                        return true;
        }
        $tmpSQL = strtoupper(trim($SQL));
        switch (strtoupper($type)) {
            case "QUERY" :
            case "HRCHY" :
                                if (substr($tmpSQL, 0, 7) !== 'SELECT ') {
                                        error_manager("SQL statement invalid for transaction type $type", -20190);
                }

                $pos = (strpos($tmpSQL, 'SELECT ') === 0 and strpos($tmpSQL, ' INTO ') > 0);
                                if ($pos) {
                                        error_manager("SQL statement invalid for transaction type $type", -20190);
                }
                break;
            case "DML" :
                                                $pos = (strpos($tmpSQL, 'INSERT ') === 0 || strpos($tmpSQL, 'DELETE ') === 0 || strpos($tmpSQL, 'UPDATE ') === 0);
                                if ($pos) break;
                $pos = (strpos($tmpSQL, 'SELECT ') === 0 and strpos($tmpSQL, ' INTO ') > 0);
                                if ($pos) break;
                                error_manager("SQL statement invalid for transaction type $type", -20190);
                break;
            case "LOGIN" :
                break;
            default : error_manager("Invalid transaction type $type", -20189);
        }
        return true;
    }

    public static function returnJson($data=false, $error=false, $info=false, $header=false, $binded=false, $otherdata=false) {
        
                global $Charset;
        global $CCConnectionSettings;
        global $sourceName;
        global $resultAction;
        
                $ContentType    = "application/json";
        $Charset        = $Charset ? $Charset : "utf-8";

        if ($Charset) {
            header("Content-Type: " . $ContentType . "; charset=" . $Charset);
        } else {
            header("Content-Type: " . $ContentType);
        }
        
                                switch ($resultAction) {
            case 'dataonly':
                echo json_encode(((!is_object($data) and !is_array($data)) ? json_decode($data) : $data));
                die;
                break;
            case 'infoonly' :
                $r = ((!is_object($info) and !is_array($info)) ? json_decode($info) : $info);
                $r->{"DB_TYPE"} = $CCConnectionSettings[$sourceName]["Type"];
                echo json_encode($r);
                die;
                break;
            case 'headeronly' :
                $r = ((!is_object($header) and !is_array($header)) ? json_decode($header) : $header);
                echo json_encode($r);
                die;
                break;
            case 'resultonly' :
                $r = ((!is_object($binded) and !is_array($binded)) ? json_decode($binded) : $binded);
                echo json_encode($r);
                die;
                break;
            case 'erroronly' :
                $r = ((!is_object($error) and !is_array($error)) ? json_decode($error) : $error);
                echo json_encode($r);
                die;
                break;
            default :
                break;
        }

                if ($otherdata) {
                                                            $result = ((!is_object($otherdata) and !is_array($otherdata)) ? json_decode($otherdata) : $otherdata);
        } else {
                                                $result = new stdClass();
            if ($header)    $result->{'HEADER'} = ((!is_object($header) and !is_array($header)) ? json_decode($header) : $header);
            if ($error)     $result->{'ERROR'} = ((!is_object($error) and !is_array($error)) ? json_decode($error) : $error);
            if ($info){
                $result->{'INFO'} = ((!is_object($info) and !is_array($info)) ? json_decode($info) : $info);
                $result->INFO->{"DB_TYPE"} = $CCConnectionSettings[$sourceName]["Type"];
            }
            if ($data)      $result->{'DATA'} = ((!is_object($data) and !is_array($data)) ? json_decode($data) : $data);
            if ($binded)    $result->{'RESULT'} = ((!is_object($binded) and !is_array($binded)) ? json_decode($binded) : $binded);

        }

        echo json_encode($result);
        die;
    }

    public static function simplifyNextRecord(& $db, $part='string') {
                                                        if ($db->next_record()) {
            foreach ($db->Record as $key => $val)
                if (is_numeric($key) and $part == 'string')                     unset($db->Record[$key]);
                else if (!is_numeric($key) and $part !== 'string')
                    unset($db->Record[$key]);
            return true;
        } else
            return false;
            }

    public static function sqlSetParameters(& $db, $SQL, $bind){
                                                                $SQL = clsCore::sqlBindVariables($SQL, $bind);
        
                        clsCore::setBindValues($db);

                return $SQL;
    }

    public static function normalizeJSONObjectAttrName($json, $case = CASE_LOWER){
                                
        if (is_object($json) or is_array($json)) {
            $json = json_encode($json);
        }
        $object = json_decode($json, true);
        if (json_last_error()) {
            error_manager('normalizeJSONObjectAttrName JSON ERROR '.json_last_error_msg(), -20999);
        }
                if ($object) $object =  clsCore::normalizeObjectAttrName($object, $case );

                return  json_encode($object);

    }

    public static function normalizeObjectAttrName($arr, $case = CASE_LOWER){
                        return array_map(function($item){
            if(is_array($item))
                $item = clsCore::normalizeObjectAttrName($item);
            return $item;
        },array_change_key_case($arr, $case));
    }


    public static function sqlTableOperation() {
                                                                                global $CCConnectionSettings;
                                                                
                $exclude_from_data = ["boundindex", "uniqueid", "visibleindex", "uid"];
                        $table_name         = CCGetParam("__table_name", "none");
        $operation_type     = CCGetParam("__operation_type", "none");
        $pk                 = CCGetParam("__pk", "none");
        $row_id             = CCGetParam("__row_id", "");         
                
                                        
        $error = false;
        $lastkey = "";
        $lastSQL = "";
        $db = new clsDBdefault();
        
                                                        
        $DATA = array();
                                                foreach ($_POST as $v => $val) {
            if (!in_array($v, $exclude_from_data) and !(substr($v, 0, 2) == "__")) {
                $DATA[$v] = $val;
                            }
        }

                        $PK = array();
        $pk = explode(',', $pk);
        foreach ($pk as $v) {
            $PK[] = $v;
        }

        $ROW_ID = array();
        if ($row_id) {
            $row_id = explode(',', $row_id);
            foreach ($row_id as $v) {
                $ROW_ID[] = $v;
            }
        }

        $where_condition = "";
        if ($operation_type == "update" or $operation_type == "delete") {

            foreach ($ROW_ID as $v) {
                if (!isset($DATA[$v])) $DATA[$v] = CCGetParam("__" . $v);
                if (strtoupper($v) == "ROW_ID" and strtoupper($db->Type) == "ORACLE") {
                    $where_condition .= ($where_condition ? " and " : "") . "ROWID" . ' = ' . CCToSQL($DATA[$v], ccsText);
                } else {
                    $where_condition .= ($where_condition ? " and " : "") . $v . ' = ' . CCToSQL($DATA[$v], ccsText);
                }
            }

            if (count($ROW_ID) == 0) {
                foreach ($PK as $v) {
                    $val = CCGetParam("__" . $v);
                    if ($val === "") {
                        $db->Error['code'] = "20098";
                        $db->Error['message'] = "Could not build the where clause. Possible absence of association of a primary key";
                        $error = true;
                    }
                    $where_condition .= ($where_condition ? " and " : "") . $v . ' = ' . CCToSQL($val, ccsText);
                }
            }
        }

        if ($operation_type == "update") {
            $SQL = "update $table_name set {column_list} where {where_condition} ";
            $column_list = "";
            foreach ($DATA as $v => $val) {
                if (!in_array($v, $ROW_ID)) {
                    $column_list .= ($column_list ? "," : "") . $v . ' = ' . CCToSQL($val, ccsText);
                }
            }
            
                        $SQL = str_replace('{column_list}', $column_list, $SQL);
            $SQL = str_replace('{where_condition}', $where_condition, $SQL);
                    } else if ($operation_type == "insert") {
            $SQL = "insert into $table_name ({column_list}) values ({val_list})";
            $column_list = "";
            $val_list = "";
            foreach ($DATA as $v => $val) {
                if (!in_array($v, $ROW_ID)) {
                    $column_list .= ($column_list ? "," : "") . $v;
                    $val_list .= ($val_list ? "," : "") . CCToSQL($DATA[$v], ccsText);
                }
            }
            
            $SQL = str_replace('{column_list}', $column_list, $SQL);
            $SQL = str_replace('{val_list}', $val_list, $SQL);

            if ($db->Type == "Oracle") {
                $SQL = str_replace('{tablename}', $table_name, str_replace('{INSERT_STATEMENT}', $SQL,
                    "begin
            lock table  {tablename} in exclusive mode;
            {INSERT_STATEMENT};
            select max(rowid)||'' row_id into :lastkey from {tablename};
            commit;
            exception when others then raise;
            end;"));
            }

        } else if ($operation_type == "delete") {
            $SQL = "delete from $table_name where ({where_condition})";

            $where_condition = "";
            foreach ($ROW_ID as $v) {
                if (strtoupper($v) == "ROW_ID" and strtoupper($db->Type) == "ORACLE") {
                    $v = "ROWID";
                }
                $where_condition .= ($where_condition ? " and " : "") . $v . ' = ' . CCToSQL($DATA[$v], ccsText);
            }

            $SQL = str_replace('{where_condition}', $where_condition, $SQL);
        } else {
            $db->Error['code'] = "20097";
            $db->Error['message'] = "Can not execute a select in this way";
            $error = true;
        }

        if (!$error) {
                        if (strtoupper($db->Type) == "ORACLE" and $operation_type == "insert") {
                                                $db->bind('lastkey', '', 4000, SQLT_CHR);
                $db->query($SQL);
                $lastkey = $db->Provider->Record['lastkey'];
            } else {
                $db->query($SQL);
            }

            $error = $db->Errors->toString();
            $affected_rows = 0;
            if (!$error) {
                if ($operation_type == "insert" && !$error) {
                    if (strtoupper($db->Type) == "MYSQL") {
                        $lastkey = false;
                        $lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);
                    }
                    if (strtoupper($db->Type) == "ORACLE") {
                                                                    }
                }
                if (in_array($operation_type, ["insert", "update", "delete"]) && !$error) {
                    $affected_rows = $db->affected_rows();
                                                                                                    if ($affected_rows != 1) {
                                                                        $db->Error['code'] = -20098;
                        $db->Error['message'] = "warning ($affected_rows) rows affected when expected exactly 1";
                        $error = $db->Error['message'];
                    }
                }
            }

            $lastSQL = json_decode(($db->LastSQL));
            $lastSQL = json_encode($lastSQL);
        }

                if ($error) {
            $json = '{"ERROR" : {"CODE":"' . $db->Error['code'] . '", "MESSAGE" : "' . $db->Error['message'] . '", "SQL":"' . htmlentities($lastSQL) . '"}}';
        } else {
                                    $json = '{"ERROR" : {"CODE":"0", "MESSAGE" : "SUCCESS"}'
             .', "INFO: { "LAST_INSERT_ID":"' . $lastkey . '", "AFFECTED_ROWS":"' . $affected_rows . '"}}';
            }
        echo $json;
        exit;
    }
    
    public static function sqlSplitFromFile($currentfile = ""){
        
        if (!$currentfile) {
            $currentfile = RelativePath . PathToCurrentPage . FileName;
        }
        $file = $currentfile;
        $path = pathinfo($file);
        if ($path['dirname'] === '.') {
                                    $path['dirname'] = RelativePath . "/textsql/";
        }
        if ($path['extension'] === 'sql') {
                                                $path['extension'] .= '.php';
        }
        if ($path['extension'] !== 'sql.php' or $path['extension'] !== 'php') {
                                    $path['filename'] .= '.'.$path['extension'];
            $path['extension'] = 'sql.php';
        } else {
            $path['filename'] .= '.'.$path['extension'];
            $path['extension'] = 'sql.php';
        }
        $file =  $path['dirname'].$path['filename'] . '.'.$path['extension'];
                $text = file_get_contents($file);
        
                return clsCore::sqlSplitFromStringWithTags($text);
            }

    public static function sqlSplitFromString($text){
        
                $start_tag = "/*<";
        $end_tag = ">*/";
        preg_match_all('#/\*\<(.*?)\>\*/#s', $text, $matches);

        foreach ($matches[0] as $i => $code) {
                        $codes[] = substr($code,2, strlen($code) - 9);
        }

        if (count($codes) == 0) {
            error_manager('No codes found on file parsing..', -20101);
        }
        foreach($codes as $ind => $code) {
            $head = substr($code, 1, strpos($code, '>')-1);
            $body = trim(substr($code, strpos($code, ">")+1));                                                $s = strtoupper($head);
            $s = explode( ' ', $s);
                                                                                                
            $scope = array();
            $scope["lang"] = $s[0];             if (isset($s[1])) {
                $scope["type"] = $s[1];                 if ($scope["type"] == 'TRIGGER') {
                    $t = explode( ':', $s[2]);
                                        if (!isset($t[1])) {
                        $scope["name"] = 'FORM';
                        $scope[3] = $s[3];
                        $name = $s[3]  ;
                    } else {
                        $block = explode('.', $t[1] );
                        if (!isset($block[1])) {
                            $scope["name"] = 'BLOCK';
                            $scope[3]  = $block[0];
                            $scope[4]  = $s[3];
                            $name = $s[3];
                        } else {
                            $scope["name"] = 'ITEM';
                            $scope[3] = $t[1];
                            $scope[4] = $s[3];
                            $name = $s[3];
                        }
                    }
                } else if ($scope["type"] == 'ANONYMOUS') {
                    $scope["name"] = $s[2];
                                        $name = $s[2] ;
                } else if ($scope["type"] == 'QUERY') {
                    $scope["name"] = $s[2];
                                        $name = $s[2];
                } else if ($scope["type"] != 'ANONYMOUS') {
                    $scope["type"] = 'ANONYMOUS';
                    $scope["name"] = $s[1];
                    $name = $s[1]  ;
                }
            }
            $plsqlParsed[$name]= new stdClass();

            preg_match_all("/\\:\s*([a-zA-Z0-9_.]+)/ise", $body, $arr);
            if (isset($arr[1])) {
                $arr = array_unique($arr[1]);
            } else $arr = array();

            $plsqlParsed[$name]->scope = $scope;
            $plsqlParsed[$name]->scope = $scope;
            $plsqlParsed[$name]->body = $body;
            $plsqlParsed[$name]->bind = $arr;

        }
                                return $plsqlParsed;
    }

    public static function normalize_tags($str, $tag)
    {
        $start = strpos(strtoupper($str), '<'.strtoupper($tag));
        if ($start === false) return $str;
        $len = strlen($tag)+1;

                $str = strtoupper(substr($str, $start, $len)) . substr($str, $len);

        $start = strpos(strtoupper($str), '</'.strtoupper($tag));
        if ($start === false) return $str;
        $len = strlen($tag)+2;

        $str = substr($str, 0, strlen($str) - ($len+1)). strtoupper(substr($str, $start, $len+1));
                return $str;
    }

    public static function checkXmlString($string) {

        $start = strpos($string, '<');
        $end   = strrpos($string, '>',$start);

        $len = strlen($string);

        if ($end !== false) {
            $string = substr($string, $start);
        } else {
            $string = substr($string, $start, $len-$start);
        }
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($string);
                        if (count(libxml_get_errors())==0) {
            return $xml;
        } else {
            return false;
        }
    }

    public static function sqlSplitFromStringWithTags($text, $tag='sql'){
        
        
        $plsqlParsed = array();
                                $start_tag = "/*";
        $end_tag = "*/";
        preg_match_all('#/\*(.*?)\*/#s', $text, $matches);
                        
        foreach ($matches[1] as $i => $code) {
                        $string = clsCore::normalize_tags(trim($code), $tag);
            
                        $xml = clsCore::checkXmlString($string);
            
            if ($xml) {
                $json = json_encode($xml);
                                $json = clsCore::normalizeJSONObjectAttrName($json);
                                $obj = json_decode($json);
                $name = (isset($obj->{"@attributes"}->name) ? strtoupper($obj->{"@attributes"}->name) : "ANONYMOUS");
                $type = (isset($obj->{"@attributes"}->type) ? strtoupper($obj->{"@attributes"}->type) : "QUERY");
                $lang = (isset($obj->{"@attributes"}->lang) ? strtoupper($obj->{"@attributes"}->lang) : "SQL");
                $scope = (isset($obj->{"@attributes"}->scope) ? $obj->{"@attributes"}->scope : "");
                $body = $obj->{'0'};
                                $plsqlParsed[$name]= new stdClass();
                $plsqlParsed[$name]->scope = $scope;
                $plsqlParsed[$name]->body = $body;
                $plsqlParsed[$name]->type = $type;
                $plsqlParsed[$name]->lang = $lang;
                if ($type !== 'JSON') {
                    preg_match_all("/\\:\s*([a-zA-Z0-9_.]+)/ise", $body, $arr);
                    if (isset($arr[1])) {
                        $arr = array_unique($arr[1]);
                    } else $arr = array();
                    $plsqlParsed[$name]->bind = $arr;
                }
            }
                                                        }

        if (count($plsqlParsed) == 0) {
            error_manager('sqlSplitFromStringWithTags: No codes found on file parsing..', -20102);
        }

                                        return $plsqlParsed;
    }

    public static function getSqlParsed($sqlParsed, $name = '1'){
                        if ($name === '1') {
                        foreach ($sqlParsed as $name => $data) {
                $SQL = $data->body;
                return $SQL;             }
        } else {
                        $name = strtoupper($name);
            if (isset($sqlParsed[$name])) {
                return $sqlParsed[$name]->body;
            }
        }
                        error_manager("getSqlParsed requested Parsed SQL $name not found", -20101) ;
    }

    public static function sqlBindVariables($currenString = "", $bind) {
                                                
                                
                global $CCConnectionSettings;
        global $sourceName;
                global $SYSTEM;
        global $GLOBALS;
        global $PARAMETERS;
        global $BINDED;

        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];
        if (!$currenString) {
            exit;
        }

        $currenString = trim($currenString);

                if (false) {
            if (substr($currenString, strlen($currenString) - 1, 1) !== ";") {
                $currenString = $currenString . ';';
            }
        }

        preg_match_all("/\\:\s*([a-zA-Z0-9_.]+)/ise", $currenString, $arr);

                                                                                        global $BINDED_IN_SQL;
        $BINDED_IN_SQL = $arr[1];
                
        foreach ($arr[1] as $i => $var) {
            $ok = false;
            foreach($bind as $j => $n) {
                                if ($j == $var) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $bind->{$var} = null;
            }
        }

                
        foreach($bind as $j => $n) {
            $ok = false;
            foreach ($arr[1] as $i => $var) {
                                if ($j == $var) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $arr[1][] = $j;
            }
        }
                        
                
                foreach ($arr[1] as $i => $name) {
            $new_name = strtoupper($name);
            if (strpos($new_name, '.') === false) {
                $new_name = 'PARAMETERS.' . $new_name;
            }
            $currenString = trim(str_replace(':' . $name, ':' . $new_name, $currenString));
            $arr[1][$i] = $new_name;
        }

        if (isset($arr[1])) {
            $arr = array_unique($arr[1]);
        } else $arr = array();

                                
        $PARAMETERS = new stdClass();
        foreach ($bind as $i => $val) {
            $new_name = strtoupper($i);
            if (strpos($new_name, '.') === false) {
                $new_name = 'PARAMETERS.' . $new_name;
            }
            $PARAMETERS->{$new_name} = new stdClass();
            $PARAMETERS->{$new_name}->original_name = $i;
            $PARAMETERS->{$new_name}->value = $val;
            $tt = gettype($val);
            switch ($tt) {
                case 'integer' :
                    $tt = ccsInteger;
                    break;
                case 'double' :
                    $tt = ccsFloat;
                    break;
                case 'string' :
                                        if (isDateTime($val, 'Y-m-d H:i:s') or isDateTime($val, 'Y-m-d')) {
                        $tt = ccsDate;
                    } else {
                        $tt = ccsText;
                    }
                    break;
                default :
                    $tt = ccsText;
            }
            $PARAMETERS->{$new_name}->type = $tt;         }

        $BINDED = $arr;

        
        if ($DB_TYPE == "MySQL") {
            foreach ($arr as $i => $toBind) {
                                                                
                $param = trim(str_replace(',', '', $toBind));
                $mysql_param = str_replace('.', '_', $param);
                $currenString = str_replace(':' . $param, '@' . $mysql_param, $currenString);
            }
        } else if ($DB_TYPE == "Oracle") {
            foreach ($arr as $i => $toBind) {
                                                                
                $param = trim(str_replace(',', '', $toBind));
                                $oracle_param = str_replace('.','_',$param);
                                $currenString = str_replace(':' . $param, ':' . $oracle_param, $currenString);
            }
        }
        return $currenString;
    }

    public static function setBindValues(& $db) {
                                global $CCConnectionSettings;
        global $sourceName;

        global $SYSTEM;
        global $PARAMETERS;
        global $GLOBALS;
        global $BINDED;

        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];
                                                
        
                        

        if ($DB_TYPE == "Oracle") {
                                    
                                    
            
                                                            global $BINDED_IN_SQL;

            foreach ($BINDED_IN_SQL as $i => $inSQL) {
                                $toBind = false;
                foreach ($BINDED as $i => $Bind)   {
                    if (isset($PARAMETERS->{$Bind})) {
                        $varname = $PARAMETERS->{$Bind}->original_name;
                    }

                    if ($varname == $inSQL) {
                        $toBind = $Bind;
                        break;
                    };
                }


                                                                
                $param  = trim(str_replace(',', '', $toBind));
                $varname = substr($param,strpos($param,'.')+1, 1000);
                                $value =
                    (
                                        strpos($toBind, 'PARAMETERS.') !== false ? (isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value : error_manager('Unbinded PARAMETER variable "' . $varname . '"', 20005))
                        : (strpos($toBind, 'SYSTEM.') !== false ? (isset($SYSTEM->{$varname}) ? : error_manager("Unbinded SYSTEM variable \"" . $varname . "\"", 20004))
                        : (strpos($toBind, 'GLOBAL.') !== false ? $GLOBALS->{$varname}
                            : isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value
                            : error_manager('Unbinded variable "' . $PARAMETERS->{$varname}->original_name . '"', 20003)
                        )));
                $oracle_param = str_replace('.','_',$param);
                $db->bind($oracle_param, $value , 4000, SQLT_CHR);
                            }
            if ($db->Errors->toString()) {
                error_manager('Error binding values "' . $db->Errors->toString(), 20003);
            }
                        return;
                    } else if ($DB_TYPE == "MySQL"){

                                    foreach ($BINDED as $i => $toBind) {
                                                                
                $param      = trim(str_replace(',', '', $toBind));
                $varname    = substr($param,strpos($param,'.')+1, 1000);
                $value      =
                    (
                    strpos($toBind, 'PARAMETERS.') !== false ? (isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value : error_manager('Unbinded PARAMETER variable "' . $varname . '"', 20005))
                        : (strpos($toBind, 'SYSTEM.') !== false ? (isset($SYSTEM->{$varname}) ? : error_manager('Undefined SYSTEM variable "' . $varname . '"', 20004))
                        : (strpos($toBind, 'GLOBAL.') !== false ? $GLOBALS->{$varname}
                            : isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value
                                                        : error_manager('Unbinded variable "' . (isset($PARAMETERS->{$varname}) ? $PARAMETERS->{$varname}->original_name : $toBind) . '"', 20003)
                        )));
                $mysql_param = str_replace('.','_',$param);
                $db->query("set @". $mysql_param ." = " . CCToSQL($value, ccsText).';');
                                
                if ($db->Errors->toString()) {
                    error_manager('Error binding values "' . $db->Errors->toString(), 20003);
                }
            }
            return;
            
        }
        error_manager('Binding values for DataBase type "' . $DB_TYPE . ", not implemented yet.", 20003);
    }

    public static function getBindValues(& $db)
    {
        
        $DB_TYPE = $db->Type;
        global $PARAMETERS;

        if ($DB_TYPE == "Oracle") {

                                                
                        foreach ($PARAMETERS as $toBind => $obj) {
                $param = trim(str_replace(',', '', $toBind));
                $oracle_param = str_replace('.', '_', $param);
                if (isset($db->Record[$oracle_param])) {
                    if ($oracle_param = "PARAMETERS____LASTKEY") {
                        $lastkey = $db->Record["PARAMETERS____LASTKEY"];
                        unset($PARAMETERS->{$toBind});
                    } else {
                        $obj->value = $db->Record[$oracle_param];
                    }
                }
            }
        }
        else if ($DB_TYPE == "MySQL") {
                                    $SQL = '';
                        global $PARAMETERS;
            foreach ($PARAMETERS as $toBind => $obj) {

                $param = trim(str_replace(',', '', $toBind));
                $mysql_param = str_replace('.', '_', $param);
                $SQL .= (!$SQL ?  'SELECT ' : ',') . "@" . $mysql_param . ' as "'.$param.'"' ;

            }

                        $db->query($SQL);
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            $db->next_record();

                        foreach($db->Record as $toBind => $value) {
                                if (!is_numeric($toBind)) {
                                        $PARAMETERS->{strtoupper($toBind)}->value = $value;
                }
            }
        }
    }

    public static function getBindResult(& $db) {
                                                                
                                                                                                global $CCConnectionSettings;
        global $sourceName;

        global $SYSTEM;
        global $PARAMETERS;
        global $GLOBALS;
        global $BINDED;


        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];

        
        $result = new stdClass();

                                foreach($PARAMETERS as $var => $obj) {
            
            $x = pathinfo($var);
            $objName = $x['filename'];
            $varName = $x['extension'];
                                    $x = pathinfo($obj->original_name);
            $objOriginalName = isset($x['extension']) ? $x['filename'] : "";
            $varOriginalName = isset($x['extension']) ? $x['extension'] : $x['filename'];;
                                    if (!$objOriginalName) {
                $result->{$varOriginalName} = $obj->value;
            } else {
                $result->{$objOriginalName} = isset($result->{$objOriginalName}) ? $result->{$objOriginalName} : new stdClass();
                $result->{$objOriginalName}->{$varOriginalName} = $obj->value;
            }
        }
        return $result;
    }
}

class clsDBdefault extends DB_Adapter
{
    function __construct($user = false, $password = false)
    {
        $this->clsDBdefault($user, $password);
    }

    function clsDBdefault($user = false, $password = false)
    {
                        global $CCConnectionSettings;
        global $sourceName;
        $this->SetProvider($CCConnectionSettings[$sourceName]);
        if ($user) {
            $this->Provider->DBUser = $user;
            $this->DBUser = $user;
        }
        if ($password) {
            $this->Provider->DBPassword = $password;
            $this->DBPassword = $password;
                    }
        $this->Initialize();
    }

    function Initialize()
    {
        global $CCConnectionSettings;
        global $sourceName;
        parent::Initialize();
        $this->DateLeftDelimiter  = "\'";
        $this->DateRightDelimiter = "\'";
        if ($CCConnectionSettings[$sourceName]["Type"] == "Oracle") {
            $this->query("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        }
    }

    function OptimizeSQL($SQL)
    {
                if (strtoupper($this->PageSize) == 'ALL') return $SQL;
        $PageSize = (int) $this->PageSize;
        if (!$PageSize) return $SQL;
        $Page = $this->AbsolutePage ? (int) $this->AbsolutePage : 1;

        
        if ($this->Type == "Oracle") {
            $SQL = "SELECT a.*, rownum a_count_rows FROM (".$SQL.") a where rownum <= ".(($Page) * $PageSize);
            $SQL = "SELECT * from (".$SQL.") where a_count_rows > ".(($Page - 1) * $PageSize)."";

        } else if ($this->Type == "MySql" or $this->Type == "MySQL") {
            if (strcmp($this->RecordsCount, "CCS not counted")) {
                $SQL = "SELECT * FROM (".$SQL.") a ". (" LIMIT " . (($Page - 1) * $PageSize) . "," . $PageSize);
                                            } else {
                $SQL = "SELECT * FROM (".$SQL.") a ". (" LIMIT " . (($Page - 1) * $PageSize) . "," . ($PageSize + 1));
                            }
        }
        return $SQL;
    }
}


class clsResultDataSource extends clsDBdefault {

    public $Parent = "";
    public $CCSEvents = "";
    public $CCSEventResult;
    public $ErrorBlock;
    public $CmdExecution;

    public $CountSQL;
    public $wp;
    public $Query;


    

    function __construct(& $Parent)
    {
        clsResultDataSource($Parent);
    }

    function clsResultDataSource(& $Parent)
    {
        $this->Parent = & $Parent;
        $this->ErrorBlock = "Grid Result";
        $this->Initialize();

                $Parent->Metadata = metadata($this);

        foreach($Parent->Metadata->colsbyname as $col => $prop) {
            $this->{$col} = new clsField($col, $prop->type, ($prop->type == ccsDate ? $this->DateFormat : ""));
        }
    }

    function SetOrder($SorterName, $SorterDirection)
    {
        $this->Order = trim("$SorterName $SorterDirection");
        $this->Order = CCGetOrder($this->Order, $SorterName, $SorterDirection, "");
    }

    function Prepare()
    {
        global $CCSLocales;
        global $DefaultDateFormat;
    }

    function Open()
    {
        global $transactiontype;
                                        $this->SQL = $this->Parent->Query ." {SQL_Where} {SQL_OrderBy}";

        
        $this->CountSQL = "SELECT COUNT(*) from (\n\n" . $this->Parent->Query .") aszalst";
                if ($this->CountSQL)
            $this->RecordsCount = CCGetDBValue(CCBuildSQL($this->CountSQL, $this->Where, ""), $this);
        else
            $this->RecordsCount = "CCS not counted";
                        $this->query($this->OptimizeSQL(CCBuildSQL($this->SQL, $this->Where, $this->Order)));
            }

    function SetValues()
    {
        foreach($this->Parent->Metadata->colsbyname as $col => $prop) {
            $this->{$col}->SetDBValue(trim($this->f($col)));
                    }
    }
}


class clsSqlResult {


                public $Metadata;
    public $Query;
        public $Errors;
        public $ds;
    public $DataSource;
    public $PageSize;
            public $HasRecord = false;
    public $SorterName = "";
    public $SorterDirection = "";
    public $PageNumber;
    public $RowNumber;
    
        
    public $RelativePath = "";
    
            

    function __construct($RelativePath, & $Parent, $Query, $WhereCondition, $SorterName, $SorterDirection) {
                global $CCSLocales;
        global $DefaultDateFormat;

                        $this->Records = array();
        $this->Parent = & $Parent;
        $this->RelativePath = $RelativePath;
        $this->Errors = new clsErrors();
        $this->ErrorBlock = "Result";
        
                        clsCORE::validateSqlStatement($Query, 'QUERY');
        $this->Query           = $Query;
        $this->SorterName      = $SorterName;
        $this->SorterDirection = $SorterDirection;

        $this->DataSource = new clsResultDataSource($this);
        $this->ds = & $this->DataSource;

        $this->DataSource->Where = $WhereCondition;

                                                        
                                                                $this->PageSize = CCGetParam("pagesize", CCGetParam("buffersize", CCGetParam('lote','ALL')));         if (strtoupper($this->PageSize) == "ALL") {
            $this->PageNumber = intval(1);
        } else {
            $this->PageSize = intval($this->PageSize);
            $this->PageNumber = CCGetParam("pagenum", CCGetParam("buffernum", CCGetParam('numlote','1')));             $this->PageNumber = intval($this->PageNumber);
        }

        foreach ($this->Metadata->colsbyname as $col => $prop) {
            $this->{$col} = new clsControl(ccsLabel
                , str_replace(' ','_',$col)
                , $CCSLocales->GetText($col)
                , $prop->type
                , ($prop->type == ccsDate ? $DefaultDateFormat : "")
                , CCGetRequestParam(str_replace(' ','_',$col), ccsGet, NULL), $this);
            $this->{$col}->HTML = true;
        }

    }

    function clsSqlResult($RelativePath, & $Parent, $Query, $WhereCondition, $SorterName, $SorterDirection)
    {
        self::__construct($RelativePath,$Parent, $Query, $WhereCondition, $SorterName, $SorterDirection);
    }

    function Initialize()
    {
        $this->DataSource->PageSize     = & $this->PageSize;
        $this->DataSource->AbsolutePage = & $this->PageNumber;
        $this->DataSource->SetOrder($this->SorterName, $this->SorterDirection);
        clsCore::setBindValues($this->DataSource);
    }

    function Show()
    {
        global $jsonStyle;
                global $CCSLocales;
        $this->RowNumber = 0;

        $this->DataSource->Prepare();
        $this->DataSource->Open();

        if ($jsonStyle == 'OBJECT') while (clsCore::simplifyNextRecord($this->DataSource)) {
                                    $this->Records[] = $this->DataSource->Record;
            $this->RowNumber++;
                    }
        if ($jsonStyle == 'ARRAY') while (clsCore::simplifyNextRecord($this->DataSource, 'numeric')) {
                                    $this->Records[] = $this->DataSource->Record;
            $this->RowNumber++;
        }

    }

    function GetErrors()
    {
        $errors = "";
        foreach ($this->Metadata->colsbyname as $col => $prop) {
            $errors = ComposeStrings($errors, $this->{$col}->Errors->ToString());
        }

        $errors = ComposeStrings($errors, $this->Errors->ToString());
        $errors = ComposeStrings($errors, $this->DataSource->Errors->ToString());
        return $errors;
    }
}

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

        global $BIND;         global $BINDED;         $bind = is_object($BIND) ? $BIND : new stdClass();

        $db = new clsDBdefault();

        
        $DB_TYPE = $db->Type;

        $lastkey = "";
        if ($DB_TYPE == "Oracle") {

                                                                        
            preg_match_all("/\\INSERT INTO\s*([a-zA-Z0-9_.]+)/ise", $SQL, $arr);

            $insertTable = false;
            if (isset($arr[1][0])) {
                $insertTable = $arr[1][0];
            }

                        if ($insertTable) {
                                                                                                $SQL = "". $SQL . " returning rowid||'' into :___lastkey; commit";
            }

            global $PARAMETERS;
                        $SQL = clsCore::sqlSetParameters(
                $db                                 , $SQL                              , $bind                         );

                        $db->query("BEGIN " . $SQL . "; END;");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

                                                
                        clsCore::getBindValues($db);
            

                        $result = clsCore::getBindResult($db);

        } else if ($DB_TYPE == "MySQL"){

            $SQL = clsCore::sqlSetParameters(
                $db                                 , $SQL                              , $bind                         );

            $db->query($SQL);
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

                                                clsCore::getBindValues($db);

                                    $result = clsCore::getBindResult($db);

            $lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);

        }

        $affected_rows = $db->affected_rows();
                                                global $includeError;
        global $includeInfo;
        global $includeResult;

                clsCore::returnJson(
            false            , $includeError ? '{"CODE":"0", "MESSAGE" : "SUCCESS"}' : false             , $includeInfo ? '{"LAST_INSERT_ID":"'.$lastkey.'", "AFFECTED_ROWS":"'.$affected_rows.'"}' : false
            , false             , $includeResult ? $result : false         );

    }

}

function isDateTime($dateStr, $format){
    date_default_timezone_set('UTC');
    $date = DateTime::createFromFormat($format, $dateStr);
    return $date && ($date->format($format) === $dateStr);
}
