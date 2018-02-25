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

class DB_Array {
  public $DBDatabase = "";
  public $DBUser     = "";
  public $DBPassword = "";
  public $Persistent = false;
  public $Uppercase  = false;
  public $Options    = array();
  public $Encoding   = "";

  public $Binds = array();

  public $Link_ID  = 0;
  public $Query_ID = 0;
  public $Record   = array();
  public $Row      = 0;
  
  public $Errno    = 0;
  public $Error    = "";

  public $Auto_Free = 1;       public $Debug     = 0;       public $Connected = false;
  
  public $Stored_Query = 0;
  public $Child_Field  = "children";
  public $Parents      = array();

  function DB_Sql($query = "") {
      $this->query($query);
  }

  function try_connect() 
  {
    return true;
  }

  function connect() {}

  function free_result() {
    $this->Query_ID = null;
  }

  function query($Query) 
  {
    if (is_array($Query)) {
      $this->Query_ID = $Query;
      $this->Stored_Query = $Query;
    } else if (is_string($Query) && strpos($Query, "SELECT COUNT(*)") === 0) {
      $this->Query_ID = array(array($this->num_rows()));
    } else {
      $this->Query_ID = $this->Stored_Query;
    }
    return $this->Query_ID;
  }
  
  function next_record() {
    $this->Record = current($this->Query_ID);
    $this->seek(1);
    return $this->Record;
  }
  
  function seek($pos) {
    for ($i = 0; $i < $pos; $i++) {
      $Result = next($this->Query_ID);
      if ($Result === false) return false; 
    }
    return true;
  }

  function affected_rows() {
    return false;
  }
    
  function num_rows($Arr = null) {
    if (is_null($Arr)) {
      $Arr = $this->Query_ID;
    }
    $Count = count($Arr);
    for($i = 0; $i < count($Arr); $i++) {
      if (isset($Arr[$i][$this->Child_Field])) {
        $Count += $this->num_rows($Arr[$i][$this->Child_Field]);
      }
    }  
    return $Count;
  }
  
  function num_fields() {
    return count($this->Query_ID[0]);
  }

  function nf() {
    return $this->num_rows();
  }
  
  function np() {
    print $this->num_rows();
  }
  
  function f($Name) {
    if($this->Uppercase) $Name = strtoupper($Name);
    return $this->Record && array_key_exists($Name, $this->Record) ? $this->Record[$Name] : "";
  }
  
  function p($Field_Name) {
    print $this->f($Field_Name);
  }

  function close()
  {
    if ($this->Query_ID) {
      $this->free_result();
    }
    if ($this->Connected && !$this->Persistent) {
      $this->Link_ID = null;
      $this->Connected = false;
    }
  }  

  function halt($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>Array Error</b><br>\n");
    die("Session halted.");
  }

}

?>
