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
 |   Time   : 10:44:10 AM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/

set_error_handler("all_errors_handler", E_ALL);
register_shutdown_function( "check_for_fatal" );

function check_for_fatal()
{
    $error = error_get_last();
    if ( $error["type"] == E_ERROR ) {
        ob_clean();

        error_manager(addslashes("API5 unhandled exception (type=" . $error["type"] . ") \""
            . $error["message"]
            . "\" -> " . $error["file"]
            . " on " . $error["line"] . "")
            , -20999
        );
            }
}

function all_errors_handler($errno, $errstr, $errfile, $errline) {
    error_manager(addslashes("API5 unhandled exception $errno:$errstr -> $errfile on $errline"), -20998);
}

define("RelativePath", "..");
define("PathToCurrentPage", "/services/");
define("FileName", "api5.php");

require_once(RelativePath . "/Common.php");
require_once(RelativePath . "/services/dosqlClasses.php");
require_once(RelativePath . "/services/dosqlExceptions.php");
include_once(RelativePath . "/services/cryptojs-aes/cryptojs-aes.php");
include_once(RelativePath . "/services/cryptojs-aes/cryptojs-aes.php");
require_once RelativePath . '/services/JWT/Firebase/JWT.php';

global $CONFIG;
$CONFIG = file_get_contents(RelativePath ."/textdb/default.config.php");
$CONFIG = json_decode_and_validate(clsCore::getSqlParsed(clsCore::sqlSplitFromStringWithTags($CONFIG,'config'),'config'),'API5');

global $AESpassPhrase;
$AESpassPhrase  = isset($CONFIG->AESpassPhrase) ? $CONFIG->AESpassPhrase : "" ;

$tokenKey       = isset($CONFIG->tokenKey) ? $CONFIG->tokenKey : "";

$headers = apache_request_headers();

if(isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
        $_POST = array_merge($_POST, (array) json_decode(trim(file_get_contents('php://input')), true));
    $_POST = array_merge($_POST, clsCore::parse_raw_http_request());
                }

$token = CCGetFromPost("token", "");

if (!$token) {
    if (isset($headers['Authorization'])) {
        $matches = array();
        preg_match('/token=(.*)/', $headers['Authorization'], $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
        }
    }
}

header('Content-type:application/json;charset=utf-8');
ini_set('memory_limit', '-1');
set_time_limit(0); 

if (!$token and $CONFIG->tokenRequired) {
    error_manager('Auhorization : Token required ', "SYS-5");
}

if ($token != "inside") {
        if ($CONFIG->tokenRequired) {
        $decoded = JWT::decode($token, $tokenKey, array('HS256'));
        try {
                        $appData = json_decode($decoded->data);
            if (json_last_error()) {
                throw new Exception ('JSON ERROR ' . json_last_error());
            }
                                            } catch (Exception $e) {
            error_manager('Unmanaged Error ( ' . $e . ')', 20001);
        }
    }
    
    if (!isset($_SERVER["HTTP_REFERER"])) {
        error_manager(1);
    }
    if (!isset($_SERVER["HTTP_X_REQUESTED_WITH"])) {
        error_manager('non Ajax request detected from '.$_SERVER["HTTP_REFERER"].' to '.$_SERVER["HTTP_HOST"],2);
    }

    $REFER = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST);
    if (!$REFER === $_SERVER["HTTP_HOST"]) {
        error_manager(3);
    }
    $XRF = $_SERVER["HTTP_X_REQUESTED_WITH"];
    if (!strtoupper($XRF) === "XMLHTTPREQUEST") {
        error_manager('bad Ajax request detected',4);
            }
}

$resultAction   = CCGetParam("action", "all");
$action         = CCGetParam("action", "all");
$dataOnly         = (CCGetParam("dataonly", "false") === "false" ? false : true);
$headerOnly       = (CCGetParam("headeronly", "false") === "false" ? false : true);
$resultAction = $dataOnly ? 'dataonly' : ($headerOnly ? 'headeronly' : $action);
$loginType      = strtoupper(CCGetParam("logintype", "LOCAL"));

$SQL            = CCGetFromPost("SQL", "");

$BIND = CCGetFromPost("BIND","{}");
try {
    $BIND = json_decode($BIND);
    if (json_last_error()) {
        throw new Exception ('JSON ERROR '.json_last_error());
    }
} catch (Exception $e) {
    error_manager('BAD BINDED values ( '.$e.')', 20002);
}
$SorterName         = CCGetParam('sortdatafield');
$SorterDirection    = CCGetParam('sortorder');

$sourceName         = CCGetParam("sourcename", "default");
$jsonStyle          = strtoupper(CCGetParam("jsonstyle", "OBJECT"));

$includeResult      = CCGetParam("icluderesult", "1");
$includeInfo        = CCGetParam("icludeinfo", "1");
$includeHeader      = (CCGetParam("icludeheader", "false") === "false" ? false : true);
$includeError       = CCGetParam("includeerror", "1");

$transactiontype    = strtoupper(CCGetParam("transactiontype", CCGetParam("__transaction_type","QUERY")));

$_SESSION["CONNECTED"] =  array();
$_SESSION["CONNECTED"][$sourceName] = new stdClass();
if ($sourceName != 'default'
    and (!isset($_SESSION["CONNECTED"])
        or !isset($_SESSION["CONNECTED"][$sourceName])
    )
) {
    error_manager("NOT CONNECTED TO DATABASE '.$sourceName.'.",2);
}

if (!$SQL and !($transactiontype == 'LOGIN' and ($loginType == 'DATABASE' or $loginType == 'OS'))) {
    error_manager('NON SQL ','SYS-'.'10');
}

    if (!$sourceName) {
        error_manager('No datasource (or sourcename) defined ','SYS-'.'11');     }

if (!file_exists("../textdb/" . $sourceName . ".sources.json.php")) {
    error_manager('Source name (sourcename or datasource) \"'.$sourceName. '\" do not exists.', 'SYS-'.'12');
}
$datasource = file_get_contents("../textdb/" . $sourceName . ".sources.json.php");
$datasource = json_decode_and_validate($datasource, "Setting datasource $sourceName ",true);
if (json_last_error()) {
    echo json_last_error_msg()."\n";
}
$CCConnectionSettings[$sourceName] = $datasource;

$DBmetadata = new clsDBdefault();

if ($transactiontype == 'LOGIN') {
        APILoginUser($SQL, $loginType);
    die;
} else if ($transactiontype == 'QUERY') {
                $sourceSQL = clsCore::sqlBindVariables($SQL, $BIND);
    
                $Tpl = "";
    $TemplateFileName = "";
    $BlockToParse = "";
    $ComponentName = "";
    $Attributes = "";

        $CCSEvents = "";
    $CCSEventResult = "";
    $TemplateSource = "";

    $BlockToParse = "main";
    $TemplateEncoding = "UTF-8";
        $ContentType = "application/json";
    $Charset = $Charset ? $Charset : "utf-8";
    $PathToRoot = "../";

        $Result = new clsSqlResult(""
                , $sourceSQL
        , ''         , $SorterName, $SorterDirection);

        $Result->Initialize();

    if ($action == "headeronly") {

                        $header = $Result->Metadata->colsbyname;

        clsCore::returnJson(
            '{}'
            , '{"CODE":"0", "MESSAGE" : "SUCCESS"}'
            , '{"DB_TYPE":"'.$CCConnectionSettings[$sourceName]["Type"].'"}'
            , $header
        );
    }

        $Result->Show();

    if ($action == "dataonly" or $dataOnly) {
                        clsCore::returnJson($Result->Records, false, false, false, false, $Result->Records);

    }

        clsCore::returnJson(
                $Result->Records         , $includeError ? '{"CODE":"0", "MESSAGE" : "SUCCESS"}' : false         , '{"RECORDS_COUNT":"' . $Result->DataSource->RecordsCount . '", "CURRENT_PAGENUMBER":"' . $Result->PageNumber . '", "CURRENT_PAGESIZE":"' . (strtoupper($Result->PageSize) == 'ALL' ? $Result->RowNumber : $Result->PageSize) . '" }'         , $includeHeader ? $Result->Metadata->colsbyname : false     );

} else if ($transactiontype == "DML" ){

    $Result = new clsDMLResult($SQL);

    die;

} else if ($transactiontype == "TABLE" ){
    clsCore::sqlTableOperation();
} else if ($transactiontype == "HRCHY" ){
            include_once './dosqlHerachies.php';
        $c = new clsHierarchiesResult($SQL, $jsonStyle);
    } else {
    error_manager('Transaction Type "'.$transactiontype. '"" do not exists.', 21);
}

exit;

function changeFunctions(&$in_obj, &$sec, &$value_arr, &$replace_keys) {
    foreach($in_obj as $key => &$value){
                if (is_object($value) or is_array($value)) changeFunctions($value, $sec, $value_arr, $replace_keys );
        else {
                        if (strpos($value, 'function(') === 0) {
                                $value_arr[] = $value;
                                $value = '%' . $key . '-' . $sec++ . '%';
                                $replace_keys[] = '"' . $value . '"';
            }
        }
    }

}

function MetaStandardType($DBtype, $DATAtype, $DATAscale = 0) {
		switch ($DBtype) {
		case "ORACLE" : switch($DATAtype) {

			case "2":  
								if ($DATAscale > 0) return ccsFloat; else return ccsInteger;
				break;
			case "182": 
			case "183": 
				return ccsInteger;
				break;
			case "1": 
			case "8": 
			case "11": 
			case "96": 
			case "112": 
			case "180": 
			case "181": 
			case "231": 
				return ccsText;
				break;
			case "12": 
				return ccsDate;
				break;
			default : return null; break;
		}
        case "MYSQLDESC" : switch($DATAtype) {
            case "char" :
            case "varchar" :
            case "binary" :
            case "varbinary" :
            case "blob" :
            case "text" :
            case "enum" :
            case "set" :
                return ccsText;
                break;
            case "date" :
            case "time" :
            case "datetime" :
            case "timestamp" :
            case "year" :
                return ccsDate;
                break;
            case "decimal" :
            case "numeric" :
            case "float" :
            case "double" :
            case "dec" :
            case "fixed" :
            case "real" :
            case "bit" :
                return ccsFloat;
                break;
            case "tinyint" :
            case "smallint" :
            case "mediumint" :
            case "int" :
            case "bigint" :
                return ccsInteger;
                break;
            default: return ccsText;
                break;
        }
		case "MYSQL" : switch($DATAtype) {
																																										case "string": 
				return ccsText;
				break;
			case "timestamp": 
			case "year": 
			case "int": 
			case "time": 
				return ccsInteger;
				break;
			case "real": 
				return ccsFloat;
				break;
			case "date": 
				return ccsDate;
				break;
												default: return ccsText; break;
		}
		case "MYSQLI" : switch($DATAtype) {
																																																																																																			case "1" : 
			case "2" : 
			case "3" : 
			case "8" : 
			case "9" : 
			case "11" : 
			case "13" : 
				return ccsInteger;
				break;
			case "4" : 
			case "5" : 
			case "6" : 
			case "246" : 
				return ccsFloat;
				break;
			case "7" : 
			case "10" : 
			case "12" : 
				return ccsDate;
				break;
			case "252" : 
			case "253" : 
			case "254" : 
				return ccsText;
				break;
			default: return ccsText; break;
		}
	}
	return null;	
}

function mysqliMetadata(& $db) {   
	$id 	= $db->Query_ID;
	$META = new stdClass();

	$i = 0;
	$META->cols = array();
	while ($property = mysqli_fetch_field($id)) {
		$col 			= strtolower($property->name);  
		$type 			= $property->type;
		$standarType 	= MetaStandardType("MYSQLI",$type);

		$META->colsbyname[ "$col" ] = new stdClass();
	    $META->colsbyname[ "$col" ]->{"type"}  	        = $standarType ;
	    $META->colsbyname[ "$col" ]->{"type_raw"}       = $type;
	    $META->colsbyname[ "$col" ]->{"size"}   	    = intval($standarType == 3 ? $property->length / 3 : $property->length);
		$META->colsbyname[ "$col" ]->{"precision"}      = $property->decimals;
		$META->colsbyname[ "$col" ]->{"scale"}	        = $property->decimals;
		$META->colsbyname[ "$col" ]->{"is_null"}        = !(MYSQLI_NOT_NULL_FLAG & $property->flags) ;		$META->colsbyname[ "$col" ]->{"primary_key"}    = !(!(MYSQLI_PRI_KEY_FLAG & $property->flags)) ;		$META->colsbyname[ "$col" ]->{"auto_increment"} = !(!(MYSQLI_AUTO_INCREMENT_FLAG & $property->flags)) ;
                                
        $META->cols[ $i ] = new stdClass();
	    $META->cols[ $i ]->{"type"}  	    = $standarType;
	    $META->cols[ $i ]->{"type_raw"}     = $type;
	    $META->cols[ $i ]->{"size"}   	    = $property->length;
		$META->cols[ $i ]->{"precision"}    = $property->decimals;
		$META->cols[ $i ]->{"scale"}	    = $property->decimals;
		$META->cols[ $i ]->{"is_null"}      = !(MYSQLI_NOT_NULL_FLAG & $property->flags) ;        $META->cols[ $i ]->{"primary_key"}  = !(!(MYSQLI_PRI_KEY_FLAG & $property->flags)) ;        $META->cols[ $i ]->{"auto_increment"} = !(!(MYSQLI_AUTO_INCREMENT_FLAG & $property->flags)) ;		$i++;

																  	}
        	return $META;
}               

function oracleMetadata(& $db) {   
	$id 	= $db->Query_ID;
	$META = new stdClass();

	$META->cols = array();
	for($ix=1;$ix<=OCINumcols($id);$ix++) {
		$col 			= oci_field_name($id, $ix);
		$type 			= oci_field_type_raw($id,$ix); 
		$presicion      = oci_field_precision($id,$ix);
		$escala			= oci_field_scale($id,$ix);
		$standarType 	= MetaStandardType("ORACLE",$type, $escala);
		
		$META->colsbyname[ "$col" ] = new stdClass();
		$META->colsbyname[ "$col" ]->{"type"}  		= $standarType;
		$META->colsbyname[ "$col" ]->{"precision"}  = $presicion;
		$META->colsbyname[ "$col" ]->{"scale"}  	= $escala;
		$META->colsbyname[ "$col" ]->{"size"}  		= oci_field_size($id,$ix);
		$META->colsbyname[ "$col" ]->{"is_null"}  	= oci_field_is_null($id,$ix);  
		$META->colsbyname[ "$col" ]->{"type_raw"}  	= $type;  
		
		$META->cols[ $ix - 1 ] = new stdClass();
		$META->cols[ $ix - 1 ]->{"type"}  		= $standarType;
		$META->cols[ $ix - 1 ]->{"precision"} 	= $presicion;
		$META->cols[ $ix - 1 ]->{"scale"}  		= $escala;
		$META->cols[ $ix - 1 ]->{"size"}  		= oci_field_size($id,$ix);
		$META->cols[ $ix - 1 ]->{"is_null"}  	= oci_field_is_null($id,$ix);  
		$META->cols[ $ix - 1 ]->{"type_raw"}  	= $type;  
		
																			}   
	return $META;  

}                     
		
function metadata(& $db, $SQL) {
				    
    	$re = "/ORDER BY.*?(?=\\s*LIMIT|\\)|$)/mi";
		$sql = preg_replace($re, "", $SQL);
	    $tipo = $db->Type;
    if ( !(CCGetParam("action", "") == "headeronly") or strtoupper($tipo) == 'ORACLE') {
                                $db->query("select * from ($sql) any_table where 1=2");
        if ($db->Errors->ToString()) {
            die("Error ... " . $this->Errors->ToString());
        }

        $id = $db->Query_ID;
        if (!$id){
            $db->Errors->addError("Metadata query failed: No query specified.");
            return false;
        }

            }

    $META = new stdClass();

	switch (strtoupper($tipo)) {
		case "ORACLE" :
						return oracleMetadata($db); 
			break;
		case "MYSQL"  :
                        if (CCGetParam("action", "") == "headeronly" and CCGetParam("statement_type", "table") == "table") {
                                $tables = extratTablesOnSQL($SQL);
                                if (count($tables) === 1) {
                                        return mysqlDescribe($db, $tables[0]);
                } else {
                    $db->query("select * from ($sql) any_table where 1=2");
                    if ($db->Errors->ToString()) {
                        die("Error ... " . $this->Errors->ToString());
                    }

                    $id = $db->Query_ID;
                    if (!$id){
                        $db->Errors->addError("Metadata query failed: No query specified.");
                        return false;
                    }

                                        return mysqliMetadata($db);
                }
            } else {
                $db->query("select * from ($sql) any_table where 1=2");
                if ($db->Errors->ToString()) {
                    die("Error ... " . $this->Errors->ToString());
                }

                $id = $db->Query_ID;
                if (!$id){
                    $db->Errors->addError("Metadata query failed: No query specified.");
                    return false;
                }
                if ($db->DB == "MySQLi") {
                                        return mysqliMetadata($db);
                } else {
                                        return mysqlMetadata($db);
                }
            }
            break;
		default: return false;
	}
  	
  	return $META;
}

function mysqlDescribe(& $db, $table) {
        $db->query("describe ".$table);
        if (!$db->Query_ID or $db->Errors->toString()){
        echo "Hubo error, se muestra? ".$db->Errors->toString()."<br>\n";
        $db->Errors->addError("Describe query failed: No query specified.");
        return false;
    }

        $ix     = 0;     $META = new stdClass();
    while($db->next_record()) {
        $col        = strtolower($db->f("field"));
        $type       = $db->f("type");
        $precision  = 0;
        $scale =    0;

        preg_match('#\((.*?)\)#', $type, $match);
        if (isset($match[0])) {
            $precision = $match[0];
            $type = str_replace($precision,"",$type);
            $precision = str_replace('(', '', $precision);
            $precision = str_replace(')', '', $precision);
            $scale = explode(',',$precision);
            $precision = $scale[0];
            $scale = (isset($scale[1]) ? $scale[1] : 0);
        }
        $standarType 	= MetaStandardType("MYSQLDESC",$type);

        $META->colsbyname[ "$col" ] = new stdClass();
        $META->colsbyname[ "$col" ]->{"type"}  	        = $standarType;
        $META->colsbyname[ "$col" ]->{"type_raw"}       = $type;
        $META->colsbyname[ "$col" ]->{"size"}   	    = $precision;
        $META->colsbyname[ "$col" ]->{"precision"}      = $precision;
        $META->colsbyname[ "$col" ]->{"scale"}  	    = $scale;
        $META->colsbyname[ "$col" ]->{"is_null"}        = ($db->f("is_null") == "YES" ? true : false);
        $META->colsbyname[ "$col" ]->{"flags"} 	        = null;
        $META->colsbyname[ "$col" ]->{"primary_key"}    = ($db->f("key") == "PRI" ? true : false);
        $META->colsbyname[ "$col" ]->{"auto_increment"} = (strpos($db->f("extra"), 'auto_increment') === false ? false : true);

        $META->cols[ $ix ] = new stdClass();
        $META->cols[ $ix ]->{"type"}  	            = $standarType;
        $META->cols[ $ix ]->{"type_raw"}            = $type;
        $META->cols[ $ix ]->{"size"}   	            = $precision;
        $META->cols[ $ix ]->{"precision"}           = $precision;
        $META->cols[ $ix ]->{"scale"}  	            = $scale;
        $META->cols[ $ix ]->{"is_null"}               = ($db->f("is_null") == "YES" ? true : false);
        $META->cols[ $ix ]->{"flags"} 	            = null;
        $META->cols[ $ix ]->{"primary_key"}              = ($db->f("key") == "PRI" ? true : false);
        $META->cols[ $ix ]->{"auto_increment"}           = (strpos($db->f("extra"), 'auto_increment') === false ? false : true);

                $ix++;
    }
    return $META;
}

function extratTablesOnSQL($SQL) {

    function getListTable($text) {

        $text = preg_replace('/\s+/S', " ", $text);         $text = preg_replace('/\n\s*\n/', "\n", $text);
        
        if (strpos($text, ')')) {
            $text = substr($text, 0, strpos($text, ')'));
        }
        
        $t_TABLE = '~\bfrom\b\s*(.*)\s*~si';         preg_match_all($t_TABLE, strtolower($text), $matches);
        $ts = array();
        if (isset($matches[1])) {
                        foreach ($matches[1] as $r) {
                $wt = strpos($r, ' join ');
                if ($wt !== false) {
                    return array();
                }
                $wt = strpos($r, ' where ');
                if ($wt !== false) {
                    $r = substr($r, 0, $wt);
                }
                $rs = explode(",", $r);
                                                foreach ($rs as $i => $t) {
                    $wt = strpos($t, ' as ');
                                        if ($wt !== false) {
                        $rs[$i] = trim(substr($t, 0, $wt));
                        $t = trim(substr($t, 0, $wt));
                        $ts[] = trim($t);
                    } else {
                        $t = trim($t);
                        $wt = strpos($t, ' ');
                                                if ($wt !== false) {
                            $rs[$i] = trim(substr($t, 0, $wt));
                            $t = trim(substr($t, 0, $wt));
                                                        $ts[] = trim($t);
                        } else {
                            $ts[] = trim($t);
                        }
                    }
                }
            }
        }
                        return $ts;
    }

    function getLastChild($levels, & $tables) {
        if (is_array($levels)) {
            foreach($levels as $i => $level) {
                getLastChild($level, $tables);
            }
        } else if (is_object($levels)) {
            if (count($levels->levels) > 0 ) {
                getLastChild($levels->levels, $tables);
            } else {
                                $ts = getListTable($levels->text);
                                $tables = array_merge($ts,$tables);
                return;
            }
        }
    }

        $x = array();
    $x = getFromWhere(strtolower($SQL), $x, 'from', 'where', 0, '(',')');
            $tables = array();
    getLastChild($x, $tables);
                    
        return $tables;
}

function BindEvents()
{
    global $Result;
    $Result->CCSEvents["BeforeShowRow"] = "ResultBeforeShowRow";
}

function ResultBeforeShowRow(& $sender)
{
    $ResultBeforeShowRow = true;
    $Component = & $sender;
    $Container = & CCGetParentContainer($sender);
    global $Result; 
    foreach ($Component->Metadata->colsbyname as $col => $prop) {
    	if ($prop->type == ccsText) {
    		$Component->{$col}->SetValue(str_replace(array("\\", '"', "/", "\n" , "\r", "\t", "\b"), array("\\\\", '\"', '\/', '\\n', '', '\t', '\b'), $Component->{$col}->GetValue()));
    	}
    }   

    return $ResultBeforeShowRow;
}

function json_validate($string,$flag=false)
{
        $string = str_replace("\n", "", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\t", " ", $string);

        $result = json_decode($string, $flag);

        switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = '';             break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
                case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
                case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
                case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
    }

    if ($error !== '') {
                exit($error);
    }

        return $result;
}

function json_decode_and_validate($string,$in_case_error,$flag=false)
{
        $string = str_replace("\n", "", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\t", " ", $string);

            $result = json_decode($string, $flag);

        switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = '';             break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
                case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
                case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
                case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
    }

    if ($error !== '') {
                error_manager($in_case_error." : ".$error, -20301);
    }

        return $result;
}

function error_manager($msg, $code=3, $type= '', $status = 400)
{
    global $sourceName;
    global $CCConnectionSettings;
    if (!$type) $type = 'API';
    $r = new stdClass();
    if ($status==200) {
        $r->{'ERROR'} = new stdClass();
    }
        $e = $r;
    http_response_code($status);
    if ($msg == '5') {
        $e->{'CODE'} = $code;
        $e->{'MESSAGE'} = "BAD REQUEST $msg";
    } else {
        $e->{'CODE'} = $code;
        $e->{'MESSAGE'} = $msg;
        $e->{'USERID'} = CCGetSession("USERID");
    }
    $e->{'TYPE'} = $type;
    if ($sourceName and isset($CCConnectionSettings[$sourceName]["Type"])) {
        $e->{'DB_TYPE'} = strtoupper($CCConnectionSettings[$sourceName]["Type"]);
    } else {
        $e->{'DB_TYPE'} = "";
    }
    if ($type=="DB") {
        $exceptions = new sqlExceptions();
        $e->{'EXCEPTION'} = $exceptions->getException($code,$e->{'DB_TYPE'});
    }

    die(json_encode($e));
}

function buildWhereCondition() {
                    
        $filterscount = CCGetParam("filterscount", "0");
    $where = "";
            if ($filterscount) {
        $where = " (";
        $tmpdatafield = "";
        $tmpfilteroperator = "";
        $valuesPrep = "";
        $values = [];
        for ($i = 0; $i < $filterscount; $i++) {

            $filtervalue = CCGetParam("filtervalue" . $i);             $filtercondition = CCGetParam("filtercondition" . $i);             $filterdatafield = CCGetParam("filterdatafield" . $i);             $filteroperator = CCGetParam("filteroperator" . $i); 
            if ($tmpdatafield == "") {
                $tmpdatafield = $filterdatafield;
            } else if ($tmpdatafield <> $filterdatafield) {
                $where .= ")AND(";
            } else if ($tmpdatafield == $filterdatafield) {
                if ($tmpfilteroperator == 0) {
                    $where .= " AND ";
                } else $where .= " OR ";
            }
                        switch ($filtercondition) {
                case "CONTAINS":
                    $condition = " LIKE ";
                    $value = "%{$filtervalue}%";
                    break;

                case "DOES_NOT_CONTAIN":
                    $condition = " NOT LIKE ";
                    $value = "%{$filtervalue}%";
                    break;

                case "EQUAL":
                    $condition = " = ";
                    $value = $filtervalue;
                    break;

                case "NOT_EQUAL":
                    $condition = " <> ";
                    $value = $filtervalue;
                    break;

                case "GREATER_THAN":
                    $condition = " > ";
                    $value = $filtervalue;
                    break;

                case "LESS_THAN":
                    $condition = " < ";
                    $value = $filtervalue;
                    break;

                case "GREATER_THAN_OR_EQUAL":
                    $condition = " >= ";
                    $value = $filtervalue;
                    break;

                case "LESS_THAN_OR_EQUAL":
                    $condition = " <= ";
                    $value = $filtervalue;
                    break;

                case "STARTS_WITH":
                    $condition = " LIKE ";
                    $value = "{$filtervalue}%";
                    break;

                case "ENDS_WITH":
                    $condition = " LIKE ";
                    $value = "%{$filtervalue}";
                    break;

                case "NULL":
                    $condition = " IS NULL ";
                    $value = "%{$filtervalue}%";
                    break;

                case "NOT_NULL":
                    $condition = " IS NOT NULL ";
                    $value = "%{$filtervalue}%";
                    break;

                            }
            $where .= " " . $filterdatafield . $condition . CCToSQL($value, ccsText);
                                    if ($i == $filterscount - 1) {
                $where .= ")";
            }
            $tmpfilteroperator = $filteroperator;
            $tmpdatafield = $filterdatafield;
        }
                            }
    return $where;
}

function end_of_line($text, $i) {
    if ($i == strlen($text)-1) {
        return true;
    }
    return false;
}

function getFromWhere($text, & $levels, $tagOpen = '(', $tagClosed = ')', $level=0, $groupOpen = '', $groupClose = ''  ) {

    $usingGroup = ($groupOpen ? true : false);
    $inGroup    = 0;
    $max        = strlen($text);
    $start      = array();
    $end        = array();
    $start_tag  = array();
    $end_tag    = array();
    $i      = 0;
    $open   = 0;
    $close   = 0;

                    while ($i <= $max) {
        if ($usingGroup and substr($text, $i, strlen($groupOpen)) === $groupOpen) {
            $inGroup++;
                    } else if ($usingGroup and substr($text, $i, strlen($groupClose)) === $groupClose) {
                        $inGroup--;
        } else if (substr($text, $i, strlen($tagOpen)) === $tagOpen and (!$open or !$inGroup)) {
                        array_push($start, $i);
            array_push($start_tag, $tagOpen);
            $open++;
        } else if (substr($text, $i, strlen($tagClosed)) === $tagClosed and !$inGroup) {
                        array_push($end, $i);
            array_push($end_tag, $tagClosed);
            $close++;
        }

        if (end_of_line($text, $i)) {
                array_push($end, $i);
                array_push($end_tag, ")");
                $close++;
                        }
        if ($open == $close and $open) {
            while (count($start) > 0) {
                                array_push($levels, new stdClass());
                $n = count($levels) - 1;
                $levels[$n]->start = array_shift($start);                 $levels[$n]->end = array_pop($end);
                $levels[$n]->endTag = array_pop($end_tag);
                $levels[$n]->startTag = array_shift($start_tag);
                $levels[$n]->text = substr($text, $levels[$n]->start, (($levels[$n]->end + (strlen($levels[$n]->endTag) - 1)) - $levels[$n]->start) + 1);
                $levels[$n]->levels = array();
                                                                                getFromWhere(
                                        substr($levels[$n]->text, strlen($levels[$n]->startTag), ($levels[$n]->text - (strlen($levels[$n]->endTag))))
                    , $levels[$n]->levels
                    , $tagOpen
                    , $tagClosed
                    , $level + 1
                    , $groupOpen
                    , $groupClose);
            }
            $open = 0;
            $close = 0;
        }
        $i++;
    }

        return $levels;
}

function APILoginUser($SQL, $loginType = 'LOCAL')
{
    global $BIND;
    global $PARAMETERS;

        CCSetSession('USERNAME', null);
    CCSetSession('USERID', null );
    CCSetSession('ROLES', null);
    global $SYSTEM;
    $SYSTEM->{'USERNAME'} = CCGetSession('USERNAME');
    $SYSTEM->{'USERID'} = CCGetSession('USERID');
    $SYSTEM->{'ROLES'} = CCGetSession('ROLES');
        
    $bind = is_object($BIND) ? $BIND : new stdClass();

    switch(strtoupper($loginType)) {
        case "LOCAL" :
                                                                        
            if (substr($SQL, 0, 1) == ':') {
                                $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL, 1));
                                                $SQL = clsCore::getSqlParsed($sqlParsed, "LOGIN");
            }

            $db = new clsDBdefault();

                        $SQL = clsCore::getSqlParsed($sqlParsed, "LOGIN");
            $SQL = clsCore::sqlSetParameters(
                $db                                 , $SQL                              , $bind                         );

            $db->query($SQL);

                        if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

                        clsCore::getBindValues($db);

            if (isset($sqlParsed["ROLES"])) {
                                $SQL = clsCore::getSqlParsed($sqlParsed, "roles");

                                                $bind->{'username'} = $PARAMETERS->{"PARAMETERS.USERNAME"}->value;
                $bind->{'userid'}   = $PARAMETERS->{"PARAMETERS.USERID"}->value;
                $bind->{'roles'}   = $PARAMETERS->{"PARAMETERS.ROLES"}->value;

                $SQL = clsCore::sqlSetParameters(
                    $db                                     , $SQL                                  , $bind                             );

                $db->query($SQL);
                while (clsCore::simplifyNextRecord($db)) {
                    if (!is_array($PARAMETERS->{"PARAMETERS.ROLES"}->value))
                        $PARAMETERS->{"PARAMETERS.ROLES"}->value = array();

                    $PARAMETERS->{"PARAMETERS.ROLES"}->value[] = $db->Record['role'];
                }
            }

            break;

        case "DATABASE" :
                                    $db = new clsDBdefault($bind->username, $bind->password);
            $connect = $db->Provider->try_connect();
                                    if (!$connect) error_manager("Invalid username/password for DATABASE Login", "SYS-"."0001");

            $PARAMETERS->{"PARAMETERS.USERNAME"} = new stdClass();
            $PARAMETERS->{"PARAMETERS.USERNAME"}->value = $bind->username;
            $PARAMETERS->{"PARAMETERS.USERNAME"}->original_name = 'username';

            $PARAMETERS->{"PARAMETERS.ROLES"} = new stdClass();
            $PARAMETERS->{"PARAMETERS.ROLES"}->value = 'CONNECTED';
            $PARAMETERS->{"PARAMETERS.ROLES"}->original_name = 'roles';

            $PARAMETERS->{"PARAMETERS.USERID"} = new stdClass();
            $PARAMETERS->{"PARAMETERS.USERID"}->value = $bind->username;
            $PARAMETERS->{"PARAMETERS.USERID"}->original_name = 'userid';
                        break;
        case "OS" :
            $authorized = false;

                        $file = dirname(__FILE__). "/.htpasswd";

            function crypt_apr1_md5($plainpasswd, $crypted) {
                                                                                                
                $salt = substr($crypted, 6, strpos(substr($crypted,6), '$'));

                $translateTo = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
                $len = strlen($plainpasswd);
                $text = $plainpasswd.'$apr1$'.$salt;
                $bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
                $tmp="" ;
                for($i = $len; $i > 0; $i -= 16) { $text .= substr($bin, 0, min(16, $i)); }
                for($i = $len; $i > 0; $i >>= 1) { $text .= ($i & 1) ? chr(0) : $plainpasswd{0}; }
                $bin = pack("H32", md5($text));
                for($i = 0; $i < 1000; $i++) {
                    $new = ($i & 1) ? $plainpasswd : $bin;
                    if ($i % 3) $new .= $salt;
                    if ($i % 7) $new .= $plainpasswd;
                    $new .= ($i & 1) ? $bin : $plainpasswd;
                    $bin = pack("H32", md5($new));
                }
                for ($i = 0; $i < 5; $i++) {
                    $k = $i + 6;
                    $j = $i + 12;
                    if ($j == 16) $j = 5;
                    $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
                }
                $tmp = chr(0).chr(0).$bin[11].$tmp;
                $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
                    $translateTo);

                return '$apr1$'.$salt.'$'.$tmp;
            }

                                    function load_htpasswd($file)
            {
                                if ( !file_exists($file))
                    return Array();

                $res = Array();
                foreach(file($file) as $l)
                {
                    $array = explode(':',$l);
                    $user = $array[0];
                    $pass = chop($array[1]);
                    $res[$user] = $pass;
                }
                return $res;
            }

                                    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                                $pass = $_SERVER['PHP_AUTH_PW'];
                $user = $_SERVER['PHP_AUTH_USER'];
                                                $users =  load_htpasswd($file);

                if (isset($users[$user])) {
                                        $pass = crypt_apr1_md5($pass, $users[$user]);
                                        
                    if (isset($users[$user]) && ($users[$user] == $pass)) {
                        $PARAMETERS->{"PARAMETERS.USERNAME"} = new stdClass();
                        $PARAMETERS->{"PARAMETERS.USERNAME"}->value = $user;
                        $PARAMETERS->{"PARAMETERS.USERNAME"}->original_name = 'username';

                        $PARAMETERS->{"PARAMETERS.ROLES"} = new stdClass();
                        $PARAMETERS->{"PARAMETERS.ROLES"}->value = '';
                        $PARAMETERS->{"PARAMETERS.ROLES"}->original_name = 'roles';

                        $PARAMETERS->{"PARAMETERS.USERID"} = new stdClass();
                        $PARAMETERS->{"PARAMETERS.USERID"}->value = $user;
                        $PARAMETERS->{"PARAMETERS.USERID"}->original_name = 'userid';
                        $authorized = true;
                    }
                }

                if (!$authorized) {
                    header('WWW-Authenticate: Basic Realm="Login please"');
                    $_SESSION = array();
                    error_manager("Invalid username/password for OS Login", "SYS-002");
                }

            } else {
                header('WWW-Authenticate: Basic Realm="Login please"');
                $_SESSION = array();
                error_manager("Well formed Basic Authentication required", "SYS-101", 401);
            }
            break;
        default :
            error_manager(-20101, "Invalid Login type $loginType");
            break;
    }

        unset($PARAMETERS->{"PARAMETERS.PASSWORD"});

        CCSetSession('USERNAME', $PARAMETERS->{"PARAMETERS.USERNAME"}->value );
    CCSetSession('USERID', $PARAMETERS->{"PARAMETERS.USERID"}->value );
    CCSetSession('USERROLES', $PARAMETERS->{"PARAMETERS.ROLES"}->value );
    global $SYSTEM;
    $SYSTEM->{'USERNAME'} = CCGetSession('USERNAME');
    $SYSTEM->{'USERID'} = CCGetSession('USERID');
    $SYSTEM->{'USERROLES'} = CCGetSession('USERROLES');
    
    global $CONFIG;
    if ($CONFIG->autenticationmethod == 'TOKEN') {
                $token = array(
            "iss" => "API5"
        ,"sub" => "API5"
        ,"aud" => "user"
        ,"iat" => time()
        ,"exp" => time()+ (7 * 24 * 60 * 60)         ,"nbf" => 1357000000
                    ,"uid" => $SYSTEM->USERID
        ,"data" => '{"username":"'.$SYSTEM->USERNAME.'"'
                .', "userroles":'.(is_array($SYSTEM->USERROLES) ? json_encode($SYSTEM->ROLES) : '"'.$SYSTEM->USERROLES.'"' ).'}'
        );

        $jwt = JWT::encode($token, $CONFIG->tokenKey);
        $PARAMETERS->{"PARAMETERS.TOKEN"} = new stdClass();
        $PARAMETERS->{"PARAMETERS.TOKEN"}->value = $jwt;
        $PARAMETERS->{"PARAMETERS.TOKEN"}->original_name = 'token';
        CCSetSession('USERTOKEN', $PARAMETERS->{"PARAMETERS.TOKEN"}->value );
        global $SYSTEM;
        $SYSTEM->{'USERTOKEN'} = CCGetSession('USERTOKEN');
    }
    $result = clsCore::getBindResult($db);
            clsCore::returnJson(
        false        , false
        , false
        , false
        , false
        , $result
    );
    return $result;
}

?>
