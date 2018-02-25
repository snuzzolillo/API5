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

error_reporting(error_reporting() & (-1 ^ E_DEPRECATED));

include(RelativePath . "/Classes.php");
include(RelativePath . "/db_adapter.php");

$CCConnectionSettings = array ();

$PHPVersion = explode(".",  phpversion());
if (($PHPVersion[0] < 4) || ($PHPVersion[0] == 4  && $PHPVersion[1] < 1)) {
    echo "Sorry. This program requires PHP 4.1 and above to run. You may upgrade your php at <a href='http://www.php.net/downloads.php'>http://www.php.net/downloads.php</a>";
    exit;
}
if (session_id() == "") { session_start(); }

header('Pragma: ');
header('Cache-control: ');
header('Expires: ');
define("TemplatePath", RelativePath);
define("ServerURL", ((isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? "https://" : "http://" ). preg_replace("/:\d+$/", "", $_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"]) . ($_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : "") . substr($_SERVER["PHP_SELF"], 0, strlen($_SERVER["PHP_SELF"]) - strlen(PathToCurrentPage . FileName)) . "/");
define("SecureURL", "");

$FileEncoding = "UTF-8";
$Charset = "utf-8";
$CCSIsXHTML = false;
$CCSUseAmp = true;
$CipherBox = array();
$CipherKey = array();
$CCSLocales = new clsLocales(RelativePath);
$CCSLocales->AddLocale("en"
    , Array("en", "US"
    , array("Yes", "No", "")
    , 2, ".", ","
    , array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"), array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")
    , array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday")
    , array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")
    , array("S", "M", "T", "W", "T", "F", "S")
    , array("m", "/", "d", "/", "yyyy")
    , array("dddd", ", ", "mmmm", " ", "dd", ", ", "yyyy")
    , array("h", ":", "nn", " ", "tt"), array("h", ":", "nn", ":", "ss", " ", "tt"), "AM", "PM", 0, false, "", "utf-8", "UTF-8", array(1, 7)));
$CCSLocales->AddLocale("es", Array("es", "ES", array(1, 0, ""), 2, ",", "."
, array("enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre")
, array("ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic")
, array("domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado")
, array("dom", "lun", "mar", "mié", "jue", "vie", "sáb")
, array("D", "L", "M", "M", "J", "V", "S")
, array("yyyy", "-", "mm", "-", "dd", " ", "HH", ":", "nn", ":", "ss")
, array("dddd", ", ", "dd", " de ", "mmmm", " de ", "yyyy")
, array("H", ":", "nn"), array("H", ":", "nn", ":", "ss"), "", "", 1, false, "", "utf-8", "UTF-8", array(1, 7)));
$CCSLocales->DefaultLocale = strtolower("es");
$CCSLocales->Init();

if ($PHPLocale = $CCSLocales->GetFormatInfo("PHPLocale"))
    setlocale(LC_ALL, $PHPLocale);
CCConvertDataArrays();
$CCProjectStyle = "";
$CCProjectDesign = "";

$ShortWeekdays = $CCSLocales->GetFormatInfo("WeekdayShortNames");
$Weekdays = $CCSLocales->GetFormatInfo("WeekdayNames");
$ShortMonths =  $CCSLocales->GetFormatInfo("MonthShortNames");
$Months = $CCSLocales->GetFormatInfo("MonthNames");

define("ccsInteger", 1);
define("ccsFloat", 2);
define("ccsSingle", ccsFloat); define("ccsText", 3);
define("ccsDate", 4);
define("ccsBoolean", 5);
define("ccsMemo", 6);

define("ccsGet", 1);
define("ccsPost", 2);

define("ccsTimestamp", 0);
define("ccsYear", 1);
define("ccsMonth", 2);
define("ccsDay", 3);
define("ccsHour", 4);
define("ccsMinute", 5);
define("ccsSecond", 6);
define("ccsMilliSecond", 7);
define("ccsAmPm", 8);
define("ccsShortMonth", 9);
define("ccsFullMonth", 10);
define("ccsWeek", 11);
define("ccsGMT", 12);
define("ccsAppropriateYear", 13);
$CCSDesign = "";
define("CCS_ENCRYPTION_KEY_FOR_COOKIE", '55BOr3x0g77H2866');
define("CCS_EXPIRATION_DATE", 30 * 24 * 3600);
define("CCS_SLIDING_EXPIRATION", false);

$DefaultDateFormat = array("ShortDate");

$MainPage = new clsMainPage();

function CCToHTML($Value)
{
  return htmlspecialchars($Value);
}

function CCToURL($Value)
{
  return urlencode($Value);
}

function CCGetEvent($events, $event_name, & $sender)
{
  $result = true;
  $function_name = (is_array($events) && isset($events[$event_name])) ? $events[$event_name] : "";
  if($function_name && function_exists($function_name))
    $result = call_user_func_array($function_name, array(& $sender));
  return $result;  
}

function & CCGetParentContainer(& $object)
{
  $i = & $object;
  while ($i && !($i->ComponentType == "Page" || $i->ComponentType == "IncludablePage" || $i->ComponentType == "Directory" || $i->ComponentType == "Path" || $i->ComponentType == "EditableGrid" || $i->ComponentType == "Grid" || $i->ComponentType == "Record" || $i->ComponentType == "Report" || $i->ComponentType == "Calendar"))
    $i = & $i->Parent;
  return $i;
}

function CCGetMasterPagePath(& $object) {
  $i = & $object;
  while ($i && !(isset($i->MasterPage) && $i->MasterPage != null)) {
    $i = & $i->Parent;
  }
  return (isset($i->MasterPage)) ? $i->PathToCurrentPage : "";
}

function & CCGetParentPage(& $object)
{
  $i = & $object;
  while ($i && !($i->ComponentType == "Page" || $i->ComponentType == "IncludablePage"))
    $i = & $i->Parent;
  return $i;
}

function CCGetValueHTML(&$db, $fieldname)
{
  return CCToHTML($db->f($fieldname));
}

function CCGetValue(&$db, $fieldname)
{
  return $db->f($fieldname);
}

function CCGetSession($parameter_name, $default_value = "")
{
    return isset($_SESSION[$parameter_name]) ? $_SESSION[$parameter_name] : $default_value;
}

function CCSetSession($param_name, $param_value)
{
    $_SESSION[$param_name] = $param_value;
}

function CCGetCookie($parameter_name)
{
    return isset($_COOKIE[$parameter_name]) ? $_COOKIE[$parameter_name] : "";
}

function CCSetCookie($parameter_name, $param_value, $expired = -1, $path = "/", $domain = "", $secured = false, $http_only = false)
{
  if ($expired == -1)
    $expired = time() + 3600 * 24 * 366;
  elseif ($expired && $expired < time())
    $expired = time() + $expired;
  setcookie ($parameter_name, $param_value, $expired, $path, $domain, $secured, $http_only);
}

function CCStrip($value)
{
  if(get_magic_quotes_gpc() != 0)
  {
    if(is_array($value))  
      foreach($value as $key=>$val)
        $value[$key] = stripslashes($val);
    else
      $value = stripslashes($value);
  }
  return $value;
}

function CCGetParam($parameter_name, $default_value = "")
{
    $parameter_value = "";
    if(isset($_POST[$parameter_name]))
        $parameter_value = CCStrip($_POST[$parameter_name]);
    else if(isset($_GET[$parameter_name]))
        $parameter_value = CCStrip($_GET[$parameter_name]);
    else
        $parameter_value = $default_value;
    return $parameter_value;
}

function CCGetParamStartsWith($prefix)
{
    $parameter_name = "";
    foreach($_POST as $key => $value) {
        if(preg_match ("/^" . $prefix . "_\d+$/i", $key)) {
            $parameter_name = $key;
            break;
        }
    }
    if($parameter_name === "") {
        foreach($_GET as $key => $value) {
            if(preg_match ("/^" . $prefix . "_\d+$/i", $key)) {
                $parameter_name = $key;
                break;
            }
        }
    }
    return $parameter_name;
}

function CCGetFromPost($parameter_name, $default_value = "")
{
    return isset($_POST[$parameter_name]) ? CCStrip($_POST[$parameter_name]) : $default_value;
}

function CCGetFromGet($parameter_name, $default_value = "")
{
    return isset($_GET[$parameter_name]) ? CCStrip($_GET[$parameter_name]) : $default_value;
}

function CCToSQL($Value, $ValueType)
{
  if(!strlen($Value))
  {
    return "NULL";
  }
  else
  {
    if($ValueType == ccsInteger || $ValueType == ccsFloat)
    {
      return doubleval(str_replace(",", ".", $Value));
    }
    else
    {
      return "'" . str_replace("'", "''", $Value) . "'";
    }
  }
}

function CCDLookUp($field_name, $table_name, $where_condition, &$db)
{
  $sql = "SELECT " . $field_name . ($table_name ? " FROM " . $table_name : "") . ($where_condition ? " WHERE " . $where_condition : "");
  return CCGetDBValue($sql, $db);
}

function CCGetDBValue($sql, &$db)
{
  $db->query($sql);
  $dbvalue = $db->next_record() ? $db->f(0) : "";
  return $dbvalue;  
}

function CCGetListValues(&$db, $sql, $where = "", $order_by = "", $bound_column = "", $text_column = "", $dbformat = "", $datatype = "", $errorclass = "", $fieldname = "", $DSType = dsSQL)
{
    $errors = new clsErrors();
    $values = "";
    if(!strlen($bound_column))
        $bound_column = 0;
    if(!strlen($text_column))
        $text_column = 1;
    if ($DSType == dsProcedure && ($db->DB == "MSSQL" || $db->DB == "SQLSRV") && count($db->Binds)) 
        $db->execute($sql);
    else
        $db->query(CCBuildSQL($sql, $where, $order_by));
    if ($db->next_record())
    {
        do
        {
            $bound_column_value = $db->f($bound_column);
            if($bound_column_value === false) {$bound_column_value = "";}
            list($bound_column_value, $errors) = CCParseValue($bound_column_value, $dbformat, $datatype, $errors, $fieldname);
            $values[] = array($bound_column_value, $db->f($text_column));
        } while ($db->next_record());
    }
    if (is_string($errorclass)) {
        return $values;
    } else {
        $errorclass->AddErrors($errors);
        return array($values, $errorclass);
    }
}

  function CCParseValue($ParsingValue, $Format, $DataType, $ErrorClass, $FieldName, $isDBFormat = false)
  {
    global $CCSLocales;
    $errors = new clsErrors();
    $varResult = "";
    if(CCCheckValue($ParsingValue, $DataType))
      $varResult = $ParsingValue;
    else if(strlen($ParsingValue))
    {
      switch ($DataType)
      {
        case ccsDate:
          $DateValidation = true;
          if (CCValidateDateMask($ParsingValue, $Format)) {
            $varResult = CCParseDate($ParsingValue, $Format);
            if(!CCValidateDate($varResult)) {
              $DateValidation = false;
              $varResult = "";
            }
          } else {
            $DateValidation = false;
          }
          if(!$DateValidation && $ErrorClass->Count() == 0) {
            if (is_array($Format)) {
              $FormatString = join("", $Format);
            } else {
              $FormatString = $Format;
            }
            $errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($FieldName, $FormatString)));
          }
          break;
        case ccsBoolean:
          if (CCValidateBoolean($ParsingValue, $Format)) {
            $varResult = CCParseBoolean($ParsingValue, $Format);
          } else if($ErrorClass->Count() == 0) {
            if (is_array($Format)) {
              $FormatString = CCGetBooleanFormat($Format);
            } else {
              $FormatString = $Format;
            }
            $errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($FieldName, $FormatString)));
          }
          break;
        case ccsInteger:
          if (CCValidateNumber($ParsingValue, $Format, $isDBFormat))
            $varResult = CCParseInteger($ParsingValue, $Format, $isDBFormat);
          else if($ErrorClass->Count() == 0)
            $errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($FieldName, $Format)));
          break;
        case ccsFloat:
          if (CCValidateNumber($ParsingValue, $Format, $isDBFormat))
            $varResult = CCParseFloat($ParsingValue, $Format, $isDBFormat);
          else if($ErrorClass->Count() == 0)
            $errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($FieldName, $Format)));
          break;
        case ccsText:
        case ccsMemo:
          $varResult = strval($ParsingValue);
          break;
      }
    }
  if (is_string($ErrorClass)) {
    return $varResult;
  } else {
    $ErrorClass->AddErrors($errors);
    return array($varResult, $ErrorClass);
  }
}

  function CCFormatValue($Value, $Format, $DataType, $isDBFormat = false)
  {
    switch($DataType)
    {
      case ccsDate:
        $Value = CCFormatDate($Value, $Format);
        break;
      case ccsBoolean:
        $Value = CCFormatBoolean($Value, $Format);
        break;
      case ccsInteger:
      case ccsFloat:
      case ccsSingle:
        $Value = CCFormatNumber($Value, $Format, $DataType, $isDBFormat);
        break;
      case ccsText:
      case ccsMemo:
        $Value = strval($Value);
        break;
    }
    return $Value;
  }

function CCBuildSQL($sql, $where = "", $order_by = "")
{
    if (!$sql) return "";
    if(strlen($where)) $where = " WHERE " . $where;
    if(strlen($order_by)) $order_by = " ORDER BY " . $order_by;
    if(stristr($sql,"{SQL_Where}") || stristr($sql,"{SQL_OrderBy}")) {
        $sql = str_replace("{SQL_Where}", $where, $sql);
        $sql = str_replace("{SQL_OrderBy}", $order_by, $sql);
        return $sql;
    }
    $sql .= $where . $order_by;
    return $sql;
}

function CCBuildInsert($table, & $Fields, & $Connection)
{
    $fields = array();
    $values = array();
    foreach ($Fields as $Field) {
        if (!isset($Field["OmitIfEmpty"]) || !$Field["OmitIfEmpty"] || !is_null($Field["Value"])) {
            $fields[] = $Field["Name"];
            if ($Field["DataType"] == ccsMemo && ($Connection->DB == "Oracle" || $Connection->DB == "OracleOCI")) {
                $values[] = ":" . $Field["Name"];
                $Connection->Bind($Field["Name"], $Field["Value"], -1);
            }else{
                $values[] = $Connection->ToSQL($Field["Value"], $Field["DataType"]);
            }
        }
    }
  return count($fields) ? "INSERT INTO " . $table . " (" . implode(", ", $fields) . ") VALUES(" . implode(", ", $values) . ")" : "";

}

function CCBuildUpdate($table, & $Fields, & $Connection)
{
    $pairs = array();
    foreach ($Fields as $Field) {
        if (!isset($Field["OmitIfEmpty"]) || !$Field["OmitIfEmpty"] || !is_null($Field["Value"])) {
            if ($Field["DataType"] == ccsMemo && ($Connection->DB == "Oracle" || $Connection->DB == "OracleOCI")) {
                $value = ":" . $Field["Name"];
                $Connection->Bind($Field["Name"], $Field["Value"], -1);
            }else{
                $value = $Connection->ToSQL($Field["Value"], $Field["DataType"]);
            }
            $pairs[] = $Field["Name"] . " = " . $value;
        }
    }
  return count($pairs) ? "UPDATE " . $table . " SET " . implode(", ", $pairs) : "";

}

function CCGetRequestParam($ParameterName, $Method, $DefaultValue = "")
{
    $ParameterValue = $DefaultValue;
    if($Method == ccsGet && isset($_GET[$ParameterName]))
        $ParameterValue = CCStrip($_GET[$ParameterName]);
    else if($Method == ccsPost && isset($_POST[$ParameterName]))
        $ParameterValue = CCStrip($_POST[$ParameterName]);
    return $ParameterValue;
}

function CCGetQueryString($CollectionName, $RemoveParameters)
{
    $querystring = "";
    $postdata = "";
    if($CollectionName == "Form")
        $querystring = CCCollectionToString($_POST, $RemoveParameters);
    else if($CollectionName == "QueryString")
        $querystring = CCCollectionToString($_GET, $RemoveParameters);
    else if($CollectionName == "All")
    {
        $querystring = CCCollectionToString($_GET, $RemoveParameters);
        $postdata = CCCollectionToString($_POST, $RemoveParameters);
        if(strlen($postdata) > 0 && strlen($querystring) > 0)
            $querystring .= "&" . $postdata;
        else
            $querystring .= $postdata;
    }
    else
        die("1050: Common Functions. CCGetQueryString Function. " .
            "The CollectionName contains an illegal value.");
    return $querystring;
}

function CCCollectionToString($ParametersCollection, $RemoveParameters)
{
  $Result = ""; 
  if(is_array($ParametersCollection))
  {
    reset($ParametersCollection);
    foreach($ParametersCollection as $ItemName => $ItemValues)
    {
      $Remove = false;
      if(is_array($RemoveParameters))
      {
        foreach($RemoveParameters as $key => $val)
        {
          if($val == $ItemName)
          {
            $Remove = true;
            break;
          }
        }
      }
      if(!$Remove)
      {
        if(is_array($ItemValues))
          for($J = 0; $J < sizeof($ItemValues); $J++)
            $Result .= "&" . urlencode(CCStrip($ItemName)) . "[]=" . urlencode(CCStrip($ItemValues[$J]));
        else
           $Result .= "&" . urlencode(CCStrip($ItemName)) . "=" . urlencode(CCStrip($ItemValues));
      }
    }
  }

  if(strlen($Result) > 0)
    $Result = substr($Result, 1);
  return $Result;
}

function CCMergeQueryStrings($LeftQueryString, $RightQueryString = "")
{
  $QueryString = $LeftQueryString; 
  if($QueryString === "")
    $QueryString = $RightQueryString;
  else if($RightQueryString !== "")
    $QueryString .= '&' . $RightQueryString;
  
  return $QueryString;
}

function CCAddParam($querystring, $ParameterName, $ParameterValue)
{
    $queryStr = null; $paramStr = null;
    if (strpos($querystring, '?') !== false)
        list($queryStr, $paramStr) = explode('?', $querystring);
    else if (strpos($querystring, '=') !== false)
        $paramStr = $querystring;
    else
        $queryStr = $querystring;
    $paramStr = $paramStr ? '&' . $paramStr : '';
    $paramStr = preg_replace ('/&' . $ParameterName . '(\[\])?=[^&]*/', '', $paramStr);
    if(is_array($ParameterValue)) {
        foreach($ParameterValue as $key => $val) {
            $paramStr .= "&" . urlencode($ParameterName) . "[]=" . urlencode($val);
        }
    } else {
        $paramStr .= "&" . urlencode($ParameterName) . "=" . urlencode($ParameterValue);
    }
    $paramStr = ltrim($paramStr, '&');
    return $queryStr ? $queryStr . '?' . $paramStr : $paramStr;
}

function CCRemoveParam($querystring, $ParameterName)
{
$queryStr = null; $paramStr = null;
    if (strpos($querystring, '?') !== false)
        list($queryStr, $paramStr) = explode('?', $querystring);
    else if (strpos($querystring, '=') !== false)
        $paramStr = $querystring;
    else
        $queryStr = $querystring;
    $paramStr = $paramStr ? '&' . $paramStr : '';
    $paramStr = preg_replace ('/&' . $ParameterName . '(\[\])?=[^&]*/', '', $paramStr);
    $paramStr = ltrim($paramStr, '&');
    return $queryStr ? $queryStr . '?' . $paramStr : $paramStr;
}

function CCGetOrder($DefaultSorting, $SorterName, $SorterDirection, $MapArray)
{
  if(is_array($MapArray) && isset($MapArray[$SorterName]))
    if(strtoupper($SorterDirection) == "DESC")
      $OrderValue = ($MapArray[$SorterName][1] != "") ? $MapArray[$SorterName][1] : $MapArray[$SorterName][0] . " DESC";
    else
      $OrderValue = $MapArray[$SorterName][0];
  else
    $OrderValue = $DefaultSorting;

  return $OrderValue;
}

function CCGetDateArray($timestamp = "")
{
  $DateArray = array(0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
  if(!strlen($timestamp) && !is_int($timestamp)) {
    $timestamp = time();
  }
  $DateArray[ccsTimestamp] = $timestamp;
  $DateArray[ccsYear] = @date("Y", $timestamp);
  $DateArray[ccsMonth] = @date("n", $timestamp);
  $DateArray[ccsDay] = @date("j", $timestamp);
  $DateArray[ccsHour] = @date("G", $timestamp);
  $DateArray[ccsMinute] = @date("i", $timestamp);
  $DateArray[ccsSecond] = @date("s", $timestamp);

  return $DateArray;
}

function CCFormatDate($DateToFormat, $FormatMask)
{

  global $CCSLocales;

  if(!is_array($DateToFormat) && strlen($DateToFormat))
    $DateToFormat = CCGetDateArray($DateToFormat);
  if(is_array($FormatMask) && is_array($DateToFormat))
  {
    $WeekdayNames = $CCSLocales->GetFormatInfo("WeekdayNames");
    $WeekdayShortNames = $CCSLocales->GetFormatInfo("WeekdayShortNames");
    $WeekdayNarrowNames = $CCSLocales->GetFormatInfo("WeekdayNarrowNames");
    $MonthNames = $CCSLocales->GetFormatInfo("MonthNames");
    $MonthShortNames = $CCSLocales->GetFormatInfo("MonthShortNames");

    $FormattedDate = "";
    for($i = 0; $i < sizeof($FormatMask); $i++)
    {
      switch ($FormatMask[$i])
      {
        case "GeneralDate": 
          $FormattedDate .= CCFormatDate($DateToFormat, $CCSLocales->GetFormatInfo("GeneralDate"));
          break;
        case "LongDate": 
          $FormattedDate .= CCFormatDate($DateToFormat, $CCSLocales->GetFormatInfo("LongDate"));
          break;
        case "ShortDate": 
          $FormattedDate .= CCFormatDate($DateToFormat, $CCSLocales->GetFormatInfo("ShortDate"));
          break;
        case "LongTime":
          $FormattedDate .= CCFormatDate($DateToFormat, $CCSLocales->GetFormatInfo("LongTime"));
          break;
        case "ShortTime":
          $FormattedDate .= CCFormatDate($DateToFormat, $CCSLocales->GetFormatInfo("ShortTime"));
          break;
        case "d":
          $FormattedDate .= $DateToFormat[ccsDay];
          break;
        case "dd":
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsDay]);
          break;
        case "ddd": 
          $FormattedDate .= $WeekdayShortNames[CCDayOfWeek($DateToFormat) - 1];
          break;
        case "dddd": 
          $FormattedDate .= $WeekdayNames[CCDayOfWeek($DateToFormat) - 1];
          break;
        case "wi": 
          $FormattedDate .= $WeekdayNarrowNames[CCDayOfWeek($DateToFormat) - 1];
          break;
        case "w": 
          $FormattedDate .= CCDayOfWeek($DateToFormat);
          break;
        case "ww": 
          $FormattedDate .= ceil((7 + date("z", $DateToFormat[ccsTimestamp]) - date("w", $DateToFormat[ccsTimestamp])) / 7);
          break;
        case "m":
          $FormattedDate .= $DateToFormat[ccsMonth];
          break;
        case "mm":
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsMonth]);
          break;
        case "mmm": 
          $FormattedDate .= $MonthShortNames[$DateToFormat[ccsMonth] - 1];
          break;
        case "mmmm":
          $FormattedDate .= $MonthNames[$DateToFormat[ccsMonth] - 1];
          break;
        case "q":
          $FormattedDate .= ceil($DateToFormat[ccsMonth] / 3);
          break;
        case "y":
          $FormattedDate .= CCDayOfYear($DateToFormat);
          break;
        case "yy": 
          $FormattedDate .= substr($DateToFormat[ccsYear], 2);
          break;
        case "yyyy": 
          $FormattedDate .= sprintf("%04d", $DateToFormat[ccsYear]);
          break;
        case "h":
          $FormattedDate .= ($DateToFormat[ccsHour] % 12 == 0 ) ? 12 : $DateToFormat[ccsHour] % 12;
          break;
        case "hh":
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsHour] % 12 == 0 ? 12 : $DateToFormat[ccsHour] % 12);
          break;
        case "H":
          $FormattedDate .= $DateToFormat[ccsHour];
          break;
        case "HH":
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsHour]);
          break;
        case "n": 
          $FormattedDate .= $DateToFormat[ccsMinute];
          break;
        case "nn": 
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsMinute]);
          break;
        case "s":
          $FormattedDate .= $DateToFormat[ccsSecond];
          break;
        case "ss":
          $FormattedDate .= sprintf("%02d", $DateToFormat[ccsSecond]);
          break;
        case "S": 
          $FormattedDate .= $DateToFormat[ccsMilliSecond] + 0;
          break;
        case "AM/PM":
        case "A/P":
          $FormattedDate .=  $DateToFormat[ccsHour] < 12 ? "AM" : "PM";
          break;
        case "am/pm":
        case "a/p":
          $FormattedDate .=  $DateToFormat[ccsHour] < 12 ? "am" : "pm";
          break;
        case "tt":
          $FormattedDate .=  $DateToFormat[ccsHour] < 12 ? $CCSLocales->GetFormatInfo("AMDesignator") : $CCSLocales->GetFormatInfo("PMDesignator");
          break;
        case "GMT":
          if (strlen($DateToFormat[ccsGMT])) 
            $GMT = intval($DateToFormat[ccsGMT]); 
          else
            $GMT = intval(date("Z", $DateToFormat[ccsTimestamp]) / (60 * 60));
          $GMT = sprintf("%02d", $GMT);
          $GMT = $GMT > 0 ? "+" . $GMT : $GMT;
          $FormattedDate .= $GMT;
          break;
        default:
          $FormattedDate .= $FormatMask[$i];
          break;
      }
    }
  }
  else
  {
    $FormattedDate = "";
  }
  return $FormattedDate;
}

function CCValidateDate($ValidatingDate)
{
  $IsValid = true;
  if(is_array($ValidatingDate)) 
    if (count($ValidatingDate) != 14)
      $IsValid = false;
    elseif ($ValidatingDate[ccsMonth] != 0 && 
      $ValidatingDate[ccsDay] != 0 && 
      $ValidatingDate[ccsYear] != 0) 
      $IsValid = checkdate($ValidatingDate[ccsMonth], $ValidatingDate[ccsDay], $ValidatingDate[ccsYear]);

  return $IsValid;
}

function CCValidateDateMask($ValidatingDate, $FormatMask)
{
  $IsValid = true;
  if(is_array($FormatMask) && strlen($ValidatingDate))
  {
    $RegExp = CCGetDateRegExp($FormatMask);
    $IsValid = preg_match($RegExp[0], $ValidatingDate, $matches);
  }

  return $IsValid;
}

function CCParseDate($ParsingDate, $FormatMask)
{
  global $CCSLocales;
  if(is_array($FormatMask) && strlen($ParsingDate))
  {
    $DateArray = array(0, "1", "", "1", "", "", "", "", "", "", "", "", "", "");
    $RegExp = CCGetDateRegExp($FormatMask);
    $IsValid = preg_match($RegExp[0], $ParsingDate, $matches);
    for($i = 1; $i < sizeof($matches); $i++)
      $DateArray[$RegExp[$i]] = $matches[$i];

    if(!$DateArray[ccsMonth] && ($DateArray[ccsFullMonth] || $DateArray[ccsShortMonth]))
    {
      if($DateArray[ccsFullMonth])
        $DateArray[ccsMonth] = CCGetIndex($CCSLocales->GetFormatInfo("MonthNames"), $DateArray[ccsFullMonth], true) + 1;
      else if($DateArray[ccsShortMonth])
        $DateArray[ccsMonth] = CCGetIndex($CCSLocales->GetFormatInfo("MonthShortNames"), $DateArray[ccsShortMonth], true) + 1;
    } else {
      $DateArray[ccsMonth] = intval($DateArray[ccsMonth]);
    }

    if (!$DateArray[ccsMonth])
      $DateArray[ccsMonth] = 1;

    if(intval($DateArray[ccsDay]) == 0) { 
      $DateArray[ccsDay] = 1; 
    } else {
      $DateArray[ccsDay] = intval($DateArray[ccsDay]); 
    }

    if ($DateArray[ccsAmPm])
      if (strtoupper(substr($DateArray[ccsAmPm], 0, 1)) == "A" || $DateArray[ccsAmPm] == $CCSLocales->GetFormatInfo("AMDesignator"))
        $DateArray[ccsHour] = $DateArray[ccsHour] == 12 ? 0 : $DateArray[ccsHour];
      elseif ($DateArray[ccsHour] < 12)
        $DateArray[ccsHour] += 12;

    if(strlen($DateArray[ccsYear]) == 2)
      if($DateArray[ccsYear] < 70)
        $DateArray[ccsYear] = "20" . $DateArray[ccsYear];
      else
        $DateArray[ccsYear] = "19" . $DateArray[ccsYear];
      
    if($DateArray[ccsYear] < 1971 && $DateArray[ccsYear] > 0)
      $DateArray[ccsAppropriateYear] = $DateArray[ccsYear] + intval((2000 - $DateArray[ccsYear]) / 28) * 28;
    else if($DateArray[ccsYear] > 2030)
      $DateArray[ccsAppropriateYear] = $DateArray[ccsYear] - intval(($DateArray[ccsYear] - 2000) / 28) * 28;
    else      
      $DateArray[ccsAppropriateYear] = $DateArray[ccsYear];

    $DateArray[ccsHour] = intval($DateArray[ccsHour]);
    $DateArray[ccsMinute] = intval($DateArray[ccsMinute]);
    $DateArray[ccsSecond] = intval($DateArray[ccsSecond]);

    $DateArray[ccsTimestamp] = @mktime ($DateArray[ccsHour], $DateArray[ccsMinute], $DateArray[ccsSecond], $DateArray[ccsMonth], $DateArray[ccsDay], $DateArray[ccsAppropriateYear]);
    if(!CCValidateDate($DateArray)) $ParsingDate = "";
    else $ParsingDate = $DateArray;
    
  }

  return $ParsingDate;
}

function CCGetDateRegExp($FormatMask)
{
  global $CCSLocales;
  $RegExp = false;
  if(is_array($FormatMask))
  {
    $masks = array(
      "d" => array("(\d{1,2})", ccsDay), 
      "dd" => array("(\d{2})", ccsDay), 
      "ddd" => array("(" . join("|", $CCSLocales->GetFormatInfo("WeekdayShortNames")) . ")", ccsWeek), 
      "dddd" => array("(" . join("|", $CCSLocales->GetFormatInfo("WeekdayNames")) . ")", ccsWeek), 
      "w" => array("\d"), "ww" => array("\d{1,2}"),
      "m" => array("(\d{1,2})", ccsMonth), "mm" => array("(\d{2})", ccsMonth), 
      "mmm" => array("(" . join("|", $CCSLocales->GetFormatInfo("MonthShortNames")) . ")", ccsShortMonth), 
      "mmmm" => array("(" . join("|", $CCSLocales->GetFormatInfo("MonthNames")) . ")", ccsFullMonth),
      "y" => array("\d{1,3}"), "yy" => array("(\d{2})", ccsYear), 
      "yyyy" => array("(\d{4})", ccsYear), "q" => array("\d"),
      "h" => array("(\d{1,2})", ccsHour), "hh" => array("(\d{2})", ccsHour), 
      "H" => array("(\d{1,2})", ccsHour), "HH" => array("(\d{2})", ccsHour),
      "n" => array("(\d{1,2})", ccsMinute), "nn" => array("(\d{2})", ccsMinute), 
      "s" => array("(\d{1,2})", ccsSecond), "ss" => array("(\d{2})", ccsSecond), 
      "AM/PM" => array("(AM|PM)", ccsAmPm), "am/pm" => array("(am|pm)", ccsAmPm), 
      "A/P" => array("(A|P)", ccsAmPm), "a/p" => array("(a|p)", ccsAmPm),
      "a/p" => array("(a|p)", ccsAmPm),
      "tt" => array("(" . $CCSLocales->GetFormatInfo("AMDesignator") . "|" . $CCSLocales->GetFormatInfo("PMDesignator") . ")", ccsAmPm), 
      "GMT" => array("([\+\-]\d{1,2})", ccsGMT), 
      "S" => array("(\d{1,6})", ccsMilliSecond)
    );
    $RegExp[0] = "";
    $RegExpIndex = 1;
    $is_date = false; $is_datetime = false;
    for($i = 0; $i < sizeof($FormatMask); $i++)
    {
      if ($FormatMask[$i] == "GeneralDate") 
      {
        $reg = CCGetDateRegExp($CCSLocales->GetFormatInfo("GeneralDate"));
        $RegExp[0] .= substr($reg[0], 2, strlen($reg[0]) - 5);
        $is_datetime = true;
        for ($j=1; $j < sizeof($reg); $j++) {
          $RegExp[$RegExpIndex++] = $reg[$j];
        }
      }
      else if ($FormatMask[$i] == "LongDate" || $FormatMask[$i] == "ShortDate") 
      {
        $reg = CCGetDateRegExp($CCSLocales->GetFormatInfo($FormatMask[$i]));
        $RegExp[0] .= substr($reg[0], 2, strlen($reg[0]) - 5);
        $is_date = true;
        for ($j=1; $j < sizeof($reg); $j++) {
          $RegExp[$RegExpIndex++] = $reg[$j];
        }
      }
      else if ($FormatMask[$i] == "LongTime" || $FormatMask[$i] == "ShortTime") 
      {
        $reg = CCGetDateRegExp($CCSLocales->GetFormatInfo($FormatMask[$i]));
        $RegExp[0] .= substr($reg[0], 2, strlen($reg[0]) - 5);
        for ($j=1; $j < sizeof($reg); $j++) {
          $RegExp[$RegExpIndex++] = $reg[$j];
        }
      }
      else if(isset($masks[$FormatMask[$i]]))
      {
        $MaskArray = $masks[$FormatMask[$i]];
        if($i == 0 && ($MaskArray[1] == ccsYear || $MaskArray[1] == ccsMonth 
          || $MaskArray[1] == ccsFullMonth || $MaskArray[1] == ccsWeek || $MaskArray[1] == ccsDay))
          $is_date = true;
        else if($is_date && !$is_datetime && $MaskArray[1] == ccsHour)
          $is_datetime = true;
        $RegExp[0] .= $MaskArray[0];
        if($is_datetime) $RegExp[0] .= "?";
        for($j = 1; $j < sizeof($MaskArray); $j++)
          $RegExp[$RegExpIndex++] = $MaskArray[$j];
      }
      else
      {
        if($is_date && !$is_datetime && $i < sizeof($FormatMask) && $masks[$FormatMask[$i + 1]][1] == ccsHour)
          $is_datetime = true;
        $RegExp[0] .= CCAddEscape($FormatMask[$i]);
        if($is_datetime) $RegExp[0] .= "?";
      }
    }
    $RegExp[0] = str_replace(" ", "\s*", $RegExp[0]);
    $RegExp[0] = "/^" . $RegExp[0] . "$/i";
  }

  return $RegExp;
}

function CCAddEscape($FormatMask)
{
  $meta_characters = array("\\", "^", "\$", ".", "[", "|", "(", ")", "?", "*", "+", "{", "-", "]", "/");
  for($i = 0; $i < sizeof($meta_characters); $i++)
    $FormatMask = str_replace($meta_characters[$i], "\\" . $meta_characters[$i], $FormatMask);
  return $FormatMask;
}

function CCGetIndex($ArrayValues, $Value, $IgnoreCase = true)
{
  $index = false;
  for($i = 0; $i < sizeof($ArrayValues); $i++)
  {
    if(($IgnoreCase && strtoupper($ArrayValues[$i]) == strtoupper($Value)) || ($ArrayValues[$i] == $Value))
    {
      $index = $i;
      break;
    }
  }
  return $index;
}

function CCFormatNumber($NumberToFormat, $FormatArray, $DataType = ccsFloat, $isDBFormat = false)
{
  global $CCSLocales;
  global $CCSIsXHTML;
  $Result = "";
  if(is_array($FormatArray) && strlen($NumberToFormat))
  {
    $IsExtendedFormat = $FormatArray[0];
    $IsNegative = ($NumberToFormat < 0);
    $NumberToFormat = abs($NumberToFormat);
    $NumberToFormat *= $FormatArray[7];
  
    if($IsExtendedFormat)     {
      $DecimalSeparator        = !is_null($FormatArray[2]) ? $FormatArray[2] : ($isDBFormat ? "." : $CCSLocales->GetFormatInfo("DecimalSeparator"));
      $PeriodSeparator         = !is_null($FormatArray[3]) ? $FormatArray[3] : ($isDBFormat ? "" : $CCSLocales->GetFormatInfo("GroupSeparator"));

      $ObligatoryBeforeDecimal = 0;
      $DigitsBeforeDecimal = 0;
      $BeforeDecimal = $FormatArray[5];
      $AfterDecimal = !is_null($FormatArray[6]) ? $FormatArray[6] : ($DataType != ccsInteger ? ($isDBFormat ? 100 : $CCSLocales->GetFormatInfo("DecimalDigits")) : 0);
      if(is_array($BeforeDecimal)) {
        for($i = 0; $i < sizeof($BeforeDecimal); $i++) {
          if($BeforeDecimal[$i] == "0") {
            $ObligatoryBeforeDecimal++;
            $DigitsBeforeDecimal++;
          } else if($BeforeDecimal[$i] == "#") 
            $DigitsBeforeDecimal++;
        }
      }
      $ObligatoryAfterDecimal = 0;
      $DigitsAfterDecimal = 0;
      if(is_array($AfterDecimal)) {
        for($i = 0; $i < sizeof($AfterDecimal); $i++) {
          if($AfterDecimal[$i] == "0") {
            $ObligatoryAfterDecimal++;
            $DigitsAfterDecimal++;
          } else if($AfterDecimal[$i] == "#")
            $DigitsAfterDecimal++;
        }
      }
  
      $NumberToFormat = number_format($NumberToFormat, $DigitsAfterDecimal, ".", "");
      $NumberParts = explode(".", $NumberToFormat);

      $LeftPart = $NumberParts[0];
      if($LeftPart == "0") $LeftPart = "";
      $RightPart = isset($NumberParts[1]) ? $NumberParts[1] : "";
      $j = strlen($LeftPart);
    
      if(is_array($BeforeDecimal))
      {
        $RankNumber = 0;
        $i = sizeof($BeforeDecimal);
        while ($i > 0 || $j > 0)
        {
          if(($i > 0 && ($BeforeDecimal[$i - 1] == "#" || $BeforeDecimal[$i - 1] == "0")) || ($j > 0 && $i < 1)) {
            $RankNumber++;
            $CurrentSeparator = ($RankNumber % 3 == 1 && $RankNumber > 3 && $j > 0) ? $PeriodSeparator : "";
            if($ObligatoryBeforeDecimal > 0 && $j < 1)
              $Result = "0" . $CurrentSeparator . $Result;
            else if($j > 0)
              $Result = $LeftPart[$j - 1] . $CurrentSeparator . $Result;
            $j--;
            $ObligatoryBeforeDecimal--;
            $DigitsBeforeDecimal--;
            if($DigitsBeforeDecimal == 0 && $j > 0)
              for(;$j > 0; $j--)
              {
                $RankNumber++;
                $CurrentSeparator = ($RankNumber % 3 == 1 && $RankNumber > 3 && $j > 0) ? $PeriodSeparator : "";
                $Result = $LeftPart[$j - 1] . $CurrentSeparator . $Result;
              }
          }
          else if ($i > 0) {
            $BeforeDecimal[$i - 1] = str_replace("##", "#", $BeforeDecimal[$i - 1]);
            $BeforeDecimal[$i - 1] = str_replace("00", "0", $BeforeDecimal[$i - 1]);
            $Result = $BeforeDecimal[$i - 1] . $Result;
          }
          $i--;
        }
      }

            $RightResult = "";
      $IsRightNumber = false;
      if(is_array($AfterDecimal))
      {
        $IsZero = true;
        for($i = sizeof($AfterDecimal); $i > 0; $i--) {
          if($AfterDecimal[$i - 1] == "#" || $AfterDecimal[$i - 1] == "0") {
            if($DigitsAfterDecimal > $ObligatoryAfterDecimal) {
              if($RightPart[$DigitsAfterDecimal - 1] != "0") 
                $IsZero = false;
              if(!$IsZero)
              {
                $RightResult = $RightPart[$DigitsAfterDecimal - 1] . $RightResult;
                $IsRightNumber = true;
              }
            } else {
              $RightResult = $RightPart[$DigitsAfterDecimal - 1] . $RightResult;
              $IsRightNumber = true;
            }
            $DigitsAfterDecimal--;
          } else {
            $AfterDecimal[$i - 1] = str_replace("##", "#", $AfterDecimal[$i - 1]);
            $AfterDecimal[$i - 1] = str_replace("00", "0", $AfterDecimal[$i - 1]);
            $RightResult = $AfterDecimal[$i - 1] . $RightResult;
          }
        }
      }
    
      if($IsRightNumber)
        $Result .= $DecimalSeparator ;

      $Result .= $RightResult;

      if(!$FormatArray[4] && $IsNegative && $Result)
        $Result = "-" . $Result;
    }
    else     {
      $DecimalSeparator = !is_null($FormatArray[2]) ? $FormatArray[2] : ($isDBFormat ? "." : $CCSLocales->GetFormatInfo("DecimalSeparator"));
      $PeriodSeparator = !is_null($FormatArray[3]) ? $FormatArray[3] : ($isDBFormat ? "" : $CCSLocales->GetFormatInfo("GroupSeparator"));
      $AfterDecimal = !is_null($FormatArray[1]) ? $FormatArray[1] : ($DataType != ccsInteger ? ($isDBFormat ? 100 : $CCSLocales->GetFormatInfo("DecimalDigits")) : 0);

      $Result = number_format($NumberToFormat, $AfterDecimal, '.', ',');
      $Result = str_replace(".", '---', $Result);
      $Result = str_replace(",", '+++', $Result);
      $Result = str_replace("---", $DecimalSeparator, $Result);
      $Result = str_replace("+++", $PeriodSeparator, $Result);
      $Result = $FormatArray[5] . $Result . $FormatArray[6];
      if(!$FormatArray[4] && $IsNegative)
        $Result = "-" . $Result;
     
    }

    if(!$FormatArray[8])
      $Result = CCToHTML($Result);

  }
  elseif (strlen($NumberToFormat))
  { 
    if ($DataType != ccsInteger) {
      $DecimalSeparator        = $isDBFormat ? "." : $CCSLocales->GetFormatInfo("DecimalSeparator");
      $Result = str_replace(",", $DecimalSeparator, $NumberToFormat);
      $Result = str_replace(".", $DecimalSeparator, $Result);
    } else {
      $Result = $NumberToFormat;
    }
  }

  if(is_array($FormatArray) && strlen($FormatArray[9])) {
    if($CCSIsXHTML) {
      $Result = "<span style=\"color: " . $FormatArray[9] . "\">" . $Result . "</span>";
    } else {
    $Result = "<FONT COLOR=\"" . $FormatArray[9] . "\">" . $Result . "</FONT>";
    }
  }

  return $Result;
}

function CCValidateNumber($NumberValue, $FormatArray, $isDBFormat = false)
{
  $is_valid = true;
  if(strlen($NumberValue))
  {
    $NumberValue = CCCleanNumber($NumberValue, $FormatArray, $isDBFormat);
    $is_valid = is_numeric($NumberValue);
  }
  return $is_valid;
}

function CCParseNumber($NumberValue, $FormatArray, $DataType, $isDBFormat = false)
{
  $NumberValue = CCCleanNumber($NumberValue, $FormatArray, $isDBFormat);
  if(is_array($FormatArray) && strlen($NumberValue))
  {

    if($FormatArray[4])       $NumberValue = - abs(doubleval($NumberValue));

    $NumberValue /= $FormatArray[7];
  }

  if(strlen($NumberValue))
  {
    if($DataType == ccsFloat)
      $NumberValue = doubleval($NumberValue);
    else
      $NumberValue = round($NumberValue, 0);
  }

  return $NumberValue;
}

function CCCleanNumber($NumberValue, $FormatArray, $isDBFormat = false)
{
  global $CCSLocales;
  if(is_array($FormatArray))
  {
    $IsExtendedFormat = $FormatArray[0];

    if($IsExtendedFormat)     {
      $BeforeDecimal = $FormatArray[5];
      $AfterDecimal = $FormatArray[6];
    
      if(is_array($BeforeDecimal))
      {
        for($i = sizeof($BeforeDecimal); $i > 0; $i--) {
          if($BeforeDecimal[$i - 1] != "#" && $BeforeDecimal[$i - 1] != "0") 
          {
            $search = $BeforeDecimal[$i - 1];
            $search = ($search == "##" || $search == "00") ? $search[0] : $search;
            $NumberValue = str_replace($search, "", $NumberValue);
          }
        }
      }

      if(is_array($AfterDecimal))
      {
        for($i = sizeof($AfterDecimal); $i > 0; $i--) {
          if($AfterDecimal[$i - 1] != "#" && $AfterDecimal[$i - 1] != "0") 
          {
            $search = $AfterDecimal[$i - 1];
            $search = ($search == "##" || $search == "00") ? $search[0] : $search;
            $NumberValue = str_replace($search, "", $NumberValue);
          }
        }
      }
    }
    else     {
      if(strlen($FormatArray[5]))
        $NumberValue = str_replace($FormatArray[5], "", $NumberValue);
      if(strlen($FormatArray[6]))
        $NumberValue = str_replace($FormatArray[6], "", $NumberValue);
    }
    $DecimalSeparator = !is_null($FormatArray[2]) ? $FormatArray[2] : ($isDBFormat ? "." : $CCSLocales->GetFormatInfo("DecimalSeparator"));
    $PeriodSeparator = !is_null($FormatArray[3]) ? $FormatArray[3] : ($isDBFormat ? "," : $CCSLocales->GetFormatInfo("GroupSeparator"));

    $NumberValue = str_replace($PeriodSeparator, "", $NumberValue);     $NumberValue = str_replace($DecimalSeparator, ".", $NumberValue); 
    if(strlen($FormatArray[9]))
    {
      if($CCSIsXHTML) {
        $NumberValue = str_replace("<span style=\"color: " . $FormatArray[9] . "\">", "", $NumberValue);
        $NumberValue = str_replace("</span>", "", $NumberValue);
      } else {
      $NumberValue = str_replace("<FONT COLOR=\"" . $FormatArray[9] . "\">", "", $NumberValue);
      $NumberValue = str_replace("</FONT>", "", $NumberValue);
    }
    }
    return $NumberValue;
  }
  if ($isDBFormat) {
    $NumberValue = str_replace(",", ".", $NumberValue);
  } else {
    $NumberValue = str_replace($CCSLocales->GetFormatInfo("GroupSeparator"), "", $NumberValue);
    $NumberValue = str_replace($CCSLocales->GetFormatInfo("DecimalSeparator"), ".", $NumberValue);
  }
  
  $NumberValue = preg_replace("/^(-?)(\\.\\d+)$/", "\${1}0\${2}", $NumberValue);

  return $NumberValue;
}

function CCParseInteger($NumberValue, $FormatArray, $isDBFormat = false)
{
  return CCParseNumber($NumberValue, $FormatArray, ccsInteger, $isDBFormat);
}

function CCParseFloat($NumberValue, $FormatArray, $isDBFormat = false)
{
  return CCParseNumber($NumberValue, $FormatArray, ccsFloat, $isDBFormat);
}

function CCValidateBoolean($BooleanValue, $Format)
{
  return $BooleanValue == ""
     || strtolower($BooleanValue) == "true"
     || strtolower($BooleanValue) == "false"
     || strval($BooleanValue) == "0"
     || strval($BooleanValue) == "1"
     || (is_array($Format) 
        && (strtolower($BooleanValue) == strtolower($Format[0])
            || strtolower($BooleanValue) == strtolower($Format[1])
            || strtolower($BooleanValue) == strtolower($Format[2]))); 
}

function CCFormatBoolean($BooleanValue, $Format)
{
  $Result = $BooleanValue;

  if(is_array($Format)) {
    if($BooleanValue == 1)
      $Result = $Format[0];
    else if(strval($BooleanValue) == "0" || $BooleanValue === false)
      $Result = $Format[1];
    else
      $Result = $Format[2];
  }

  return $Result;
}

function CCParseBoolean($Value, $Format)
{
  if (is_array($Format)) {
    if (strtolower(strval($Value)) == strtolower(strval($Format[0])))
      return true;
    if (strtolower(strval($Value)) == strtolower(strval($Format[1])))
      return false;
    if (strtolower(strval($Value)) == strtolower(strval($Format[2])))
      return "";
  }
  if (strval($Value) == "0" || strtolower(strval($Value)) == "false")
    return false;
  if (strval($Value) == "1" || strtolower(strval($Value)) == "true")
    return true;
  return "";
}

function CCGetBooleanFormat($Format)
{
  $FormatString = "";
  if(is_array($Format))
  {
    for($i = 0; $i < sizeof($Format); $i++) {
      if(strlen($Format[$i])) {
        if(strlen($FormatString)) $FormatString .= ";";
        $FormatString .= $Format[$i];
      }
    }
  }
  return $FormatString;
}

function CCCompareValues($Value1,$Value2,$DataType = ccsText, $Format = "")
{
  switch ($DataType) {
    case ccsInteger:
    case ccsFloat:
      if(strcmp(trim($Value1),"") == 0 || strcmp(trim($Value2),"") == 0)
        return strcmp($Value1, $Value2);
      else if($Value1 > $Value2)
        return 1;
      else if($Value1 < $Value2)
        return -1;
      else
        return 0;
  
    case ccsText:
    case ccsMemo:
      return strcmp($Value1,$Value2);

    case ccsBoolean:
      if (is_bool($Value1))
        $val1=$Value1;
      else if (strlen($Value1)!= 0 && CCValidateBoolean($Value1,$Format))
        $val1=CCParseBoolean($Value1,$Format);
      else 
        return 1;

      if (is_bool($Value2))
        $val2=$Value2;
      else if (strlen($Value2)!= 0 && CCValidateBoolean($Value2,$Format))
        $val2=CCParseBoolean($Value2,$Format);
      else 
        return 1;

      return $val1 xor $val2;
  
    case ccsDate:
      if (is_array($Value1) && is_array($Value2)) {
        $compare = array(ccsYear, ccsMonth, ccsDay, ccsHour, ccsMinute, ccsSecond);
        foreach ($compare as $ind => $val) {
          if ($Value1[$val] < $Value2[$val])
            return -1;
          elseif ($Value1[$val] > $Value2[$val])          
            return 1;
        }
        return 0;
      } else if(is_array($Value1)) {
        $FormattedValue = CCFormatValue($Value1, $Format, $DataType);
        return CCCompareValues($FormattedValue, $Value2);
      } else if(is_array($Value2)) {
        $FormattedValue = CCFormatValue($Value2, $Format, $DataType);
        return CCCompareValues($Value1,$FormattedValue);
      } else {
        return CCCompareValues($Value1,$Value2);
      }
    
  }
}

function CCDateAdd($date, $value) {
  if (CCValidateDate($date)) {
    $FormatArray = array("yyyy", "-", "mm", "-", "dd", " ", "HH", ":", "nn", ":", "ss");
    $value = strtolower($value);
    preg_match_all("/([-+]?)(\\d+)\\s*(year(s?)|month(s?)|day(s?)|hour(s?)|minute(s?)|second(s?)|week(s?)|[ymdwhns])/", $value, $pieces);
    for($i=0; $i<count($pieces[0]); $i++) {
      $rel = $pieces[1][$i] == "-" ? -$pieces[2][$i] : $pieces[2][$i];
      $BackMonth = false;
      switch($pieces[3][$i]) {
        case "years":
        case "year":
        case "y": 
          $date[ccsYear] += $rel;
          $BackMonth = true;
          break;
        case "months":
        case "month":
        case "m":
          $date[ccsMonth] += $rel;
          $BackMonth = true;
          break;
        case "weeks":
        case "week":
        case "w":
          $date[ccsDay] += $rel * 7;
          break;
        case "days":
        case "day":
        case "d":
          $date[ccsDay] += $rel;
          break;
        case "hours":
        case "hour":
        case "h":
          $date[ccsHour] += $rel;
          break;
        case "minutes":
        case "minute":
        case "min":
        case "n":
          $date[ccsMinute] += $rel;
          break;
        case "seconds":
        case "second":
        case "sec":
        case "s":
          $date[ccsSecond] += $rel;
          break;
      }
      if ($date[ccsSecond] >= 60) {
        $date[ccsMinute] += floor($date[ccsSecond] / 60);
        $date[ccsSecond] = $date[ccsSecond] % 60;
      } elseif ($date[ccsSecond] < 0) {
        $date[ccsMinute] += floor($date[ccsSecond] / 60);
        $date[ccsSecond] = (($date[ccsSecond]) % 60 + 60) % 60;
      }
      if ($date[ccsMinute] >= 60) {
        $date[ccsHour] += floor($date[ccsMinute] / 60);
        $date[ccsMinute] = $date[ccsMinute] % 60;
      } elseif ($date[ccsMinute] < 0) {
        $date[ccsHour] += floor($date[ccsMinute] / 60);
        $date[ccsMinute] = ($date[ccsMinute] % 60 + 60) % 60;
      }
      if ($date[ccsHour] >= 24) {
        $date[ccsDay] += floor($date[ccsHour] / 24);
        $date[ccsHour] = $date[ccsHour] % 24;
      } elseif ($date[ccsHour] < 0) {
        $date[ccsDay] += floor($date[ccsHour] / 24);
        $date[ccsHour] = ($date[ccsHour] % 24 + 24) % 24;
      }
      if ($date[ccsMonth] > 12) {
        $date[ccsYear] += floor(($date[ccsMonth] - 1) / 12);
        $date[ccsMonth] = ($date[ccsMonth] - 1) % 12 + 1;
      } elseif ($date[ccsMonth] < 1) {
        $date[ccsYear] += floor(($date[ccsMonth] - 1) / 12);
        $date[ccsMonth] = (($date[ccsMonth] - 1) % 12 + 12) % 12 + 1;
      }
      $days = CCDaysInMonth($date[ccsYear], $date[ccsMonth]);
      if($BackMonth && $date[ccsDay] > $days) {
  $date[ccsDay] = $days;
      } else {
        while ($date[ccsDay] > $days) {
          $date[ccsMonth] += 1;
          if ($date[ccsMonth] > 12) {
            $date[ccsYear] += 1;
            $date[ccsMonth] = 1;
          }
          $date[ccsDay] = $date[ccsDay] - $days;
          $days = CCDaysInMonth($date[ccsYear], $date[ccsMonth]);
        }
      }
      if($BackMonth && $date[ccsDay] < 1) {
        $date[ccsDay] = 1;
      } else {
        $tmpDate = "";
        while ($date[ccsDay] < 1) {
          if ($tmpDate == "")
            $tmpDate = CCParseDate(CCFormatDate($date, array("yyyy","-","mm","-01")), array("yyyy","-","mm","-","dd"));
          $tmpDate = CCDateAdd($tmpDate, "-1month");
          $days = CCDaysInMonth($tmpDate[ccsYear], $tmpDate[ccsMonth]);
          $date[ccsMonth] -= 1;
          if ($date[ccsMonth] == 0) {
            $date[ccsYear] -= 1;
            $date[ccsMonth] = 12;
          }
          $date[ccsDay] = $date[ccsDay] + $days;
        }
      }
    }
    $date[ccsTimestamp] = @mktime ($date[ccsHour], $date[ccsMinute], $date[ccsSecond], $date[ccsMonth], $date[ccsDay], $date[ccsYear]);
    return $date;
  }
  return false;
}

function CCDaysInMonth($year, $month) {
  switch ($month) {
    case 4:
    case 6:
    case 9:
    case 11:
      return 30;
    case 2:
       if ($year % 4)
         return 28;
       elseif ($year % 100)
         return 29;
       elseif ($year % 400)
         return 28;
       else return 29;
    default:
      return 31;
  }

}

function CCDayOfWeek($date) {
    $year = $date[ccsYear];
  $month = $date[ccsMonth];
  $day = $date[ccsDay];
  $century = $year - ( $year % 100 );
  $base = array( 3, 2, 0, 5 );
  $base = $base[(($century - 1500) / 100 + 16) % 4];
  $twelves = intval(($year - $century )/12);
  $rem = ($year - $century) % 12;
  $fours = intval($rem / 4);
  $doomsday = $base + ($twelves + $rem + $fours) % 7;
  $doomsday = $doomsday % 7;

  $base = array( 0, 0, 7, 4, 9, 6, 11, 8, 5, 10, 7, 12 );
  if (CCDaysInMonth($year, 2) == 29) {
    $base[0] = 32;
    $base[1] = 29;
  } else {
    $base[0] = 31;
    $base[1] = 28;
  }
  $on = $day - $base[$month - 1];
  $on = $on % 7;
  return ($doomsday + $on + 7) % 7 + 1;
}

function CCDayOfYear($date) {
  $days = 0;
  for ($month = 1; $month < $date[ccsMonth]; $month++)
    $days += CCDaysInMonth($date[ccsYear], $month);
  return $days + $date[ccsDay];
}

function CCConvertEncoding($text, $from, $to)
{
    if (strlen($from) && strlen($to) && strcasecmp($from, $to)) {
        return iconv($from, $to, $text);
    } else
        return $text;
}

function CCConvertEncodingArray($array, $from="", $to="")
{
    if (strlen($from) && strlen($to) && strcasecmp($from, $to)) {
        foreach ($array as $key => $value)
            $array[$key] = is_array($value) ? CCConvertEncodingArray($value, $from, $to) : CCConvertEncoding($value, $from, $to);
    }
    return $array;
}

function CCConvertDataArrays($from="", $to="")
{
    global $FileEncoding;
    global $TemplateEncoding;
    global $CCSLocales;
    if ($from == "")
        $from = $CCSLocales->GetFormatInfo("PHPEncoding");
    if ($from == "")
        $from = $TemplateEncoding;
    if ($to == "")
        $to = $FileEncoding;
    if (strlen($from) && strlen($to) && strcmp($from, $to)) {
        $_POST = CCConvertEncodingArray($_POST, $from, $to);
        $_GET = CCConvertEncodingArray($_GET, $from, $to);
    }
}

function CCGetOriginalFileName($value)
{
    return preg_match("/^\d{14,}\./", $value) ? substr($value, strpos($value, ".") + 1) : $value;
}

function ComposeStrings($str1, $str2, $delimiter = null)
{
    global $CCSIsXHTML;
    if(is_null($delimiter)) {
        $delimiter = $CCSIsXHTML ? "<br />" : "<BR>";
    }
    return $str1 . (strlen($str1) && strlen($str2) ? $delimiter : "") . $str2;
}

function CCSelectProjectStyle() {
    global $CCProjectStyle;
    $QueryStyle = CCGetFromGet("style");
    if ($QueryStyle) {
        CCSetProjectStyle($QueryStyle);
        CCSetSession("style", $CCProjectStyle);
        return;;
      
    }
    if (CCSetProjectStyle(CCGetSession("style")))
        return;
}

function CCSetProjectStyle($NewStyle) {
    global $CCProjectStyle;
    $NewStyle = trim($NewStyle);
    if ($NewStyle && file_exists(RelativePath . "/Styles/" . $NewStyle . "/Style.css")) {
        $CCProjectStyle = $NewStyle;
        return true;
    }
    return false;
}

function CCSelectProjectDesign() {
    global $CCProjectDesign;
    $QueryDesign = CCGetFromGet("design");
    if ($QueryDesign) {
        CCSetProjectDesign($QueryDesign);
        CCSetSession("design", $CCProjectDesign);
        return;
    }
    if (CCSetProjectDesign(CCGetSession("design")))
        return;
}

function CCSetProjectDesign($NewDesign) {
    global $CCProjectDesign;
    $NewDesign = trim($NewDesign);
    if ($NewDesign && is_dir(RelativePath . "/Designs/" . $NewDesign . "/")) {
        $CCProjectDesign = $NewDesign;
        return true;
    }
    return false;
}

function CCStrLen($str, $encoding = false) {
    global $FileEncoding;
    global $PHPVersion;
    if (false === $encoding) $encoding = $FileEncoding;
    return $encoding && $PHPVersion[0] >= 5 ? iconv_strlen($str, $encoding) : strlen($str);
}

function CCGetTemplate() {
    global $Tpl;
    $numargs = func_num_args();
    if ($numargs == 0) return $Tpl;
    $args = func_get_args();
    $Component = $args[0];
    $TplClassName = "clsTemplate";
    $parent_page = & CCGetParentPage($Component);
    if (!isset($parent_page->Tpl) || !($parent_page->Tpl instanceof $TplClassName)) { return $Tpl; } 
    else { return $parent_page->Tpl; }
}

function CCInitializeDetails(& $Component, $ComponentType)
{
    global $MainPage;
    $json       = $_POST["CCSDetailControls"];
    $JsonParser = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
    $controls   = $JsonParser->decode(stripslashes($json));
    $_POST      = array_merge($_POST, $controls);
    $_GET["ccsForm"] = $Component->ComponentName;
    $ComponentClassName = get_class($Component);
    $parent_page = & CCGetParentPage($Component);
    if ($parent_page->ComponentType == "IncludablePage") {
        $Component = new $ComponentClassName("", $parent_page);
        $Component->Initialize();
        $parent_page->BindEvents();
    } else {
        $Component = new $ComponentClassName("", $MainPage);
        $Component->Initialize();
        BindEvents();
    }
    if ($ComponentType == "EditableGrid") {
        $Component->GetFormParameters();
    }
    return $Component->Validate();
}

function CCSubStr($str, $offset, $length = null, $encoding = false) {
    global $FileEncoding;
    global $PHPVersion;
    if (false === $encoding) $encoding = $FileEncoding;
    return  $encoding && $PHPVersion[0] >= 5 ? (strlen($str) ? @iconv_substr($str, $offset, $length, $encoding) : "") : (is_null($length) ? substr($str, $offset) : substr($str, $offset, $length));
}

function CCStrPos($haystack, $needle, $offset = 0, $encoding = false) {
    global $FileEncoding;
    global $PHPVersion;
    if (false === $encoding) $encoding = $FileEncoding;
    return  $encoding && $PHPVersion[0] >= 5 ? iconv_strpos($haystack, $needle, $offset, $encoding) : strpos($haystack, $needle, $offset);
}

function CCCheckSSL()
{
    $HTTPS = isset($_SERVER["HTTPS"]) ? strtolower($_SERVER["HTTPS"]) : "";
    if($HTTPS != "on")
    {
        echo "SSL connection error. This page can be accessed only via secured connection.";
        exit;
    }
}

function GenerateCaptchaCode($letters, $sesVariableName, $width, $height, $length, $rot, $br, $w1, $w2, $noise) {
    $restricted = "|cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|";
    $res = new clsQuadraticPaths();
    $t = ""; $code = ""; $r = "";
    for ($i = 0; $i < $length; $i++) {
        $r = intval((count($letters)) * (mt_rand(0, 99)/100));
        while (strpos("|" . substr($code, -strlen($code), 1) . $letters[$r][0] . "|", $restricted) !== false) {
            $r = intval((count($letters)) * (mt_rand(0, 99)/100));
        }
        $code = $code . $letters[$r][0];
        $t = new clsQuadraticPaths();
        $t->LoadFromArray($letters[$r]);
        $t->Wave(2 * $w2 * (mt_rand(0, 99)/100) - $w2);
        $t->Rotate(2 * $rot * (mt_rand(0, 99)/100) - $rot);
        $t->Normalize(0, 100);
        if (($t->MaxX - $t->MinX) > 100) {
            $t->Normalize(100, 100);
        }
        $t->Addition(($i - 1) * $t->MaxY, 0);
        $res->AddPaths($t);
    }
    $res->Rotate(90);
    $res->Wave(2 * $w1 * (mt_rand(0, 99)/100) - $w1);
    $res->Rotate(-90);
    $res->Broke($br, $br);
    $res->Normalize($width - 12, $height - 12);
    $res->Addition(6, 6);
    $res->Mix();
    $res->Noises($noise);
    CCSetSession($sesVariableName, $code);
    return $res->ToString();
}

if (!function_exists('file_get_contents')) {
    function file_get_contents($filename, $incpath = false, $resource_context = null)
    {
        if (false === $fh = fopen($filename, 'rb', $incpath)) {
            trigger_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
            return false;
        }
        clearstatcache();
        if ($fsize = @filesize($filename)) {
            $data = fread($fh, $fsize);
        } else {
            $data = '';
            while (!feof($fh)) {
                $data .= fread($fh, 8192);
            }
        }
        fclose($fh);
        return $data;
    }
}

function CCLoginUser($login,$password){
	if (CCGetSession("usr_login")){
		echo CCGetSession("usr_login");	} else {
		echo "No login";
	}
}

function CCLogoutUser()
{
    CCSetSession("UserID", "");
    CCSetSession("UserLogin", "");
    CCSetSession("GroupID", "");
    CCSetSession("UserAddr", "");
}

function CCGetCurrentUrlPoint() {
    $url = $_SERVER['REQUEST_URI'];     $parts = explode('/',$url);
    $dir = $_SERVER['SERVER_NAME'];
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $dir .= $parts[$i] . "/";
    }
    return $dir;
}

?>
