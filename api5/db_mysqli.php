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

class DB_MySQLi {

  public $DBHost     = "";
  public $DBPort     = 0;
  public $DBSocket   = "";
  public $DBDatabase = "";
  public $DBUser     = "";
  public $DBPassword = "";
  public $Persistent = false;
  public $Case       = CASE_LOWER;

  public $Auto_Free     = 1;       public $Debug         = 0;       public $Seq_Table     = "db_sequence";

  public $Record   = array();
  public $Row;

  public $Errno    = 0;
  public $Error    = "";
    public $SqlState = "";

  public $type     = "mysql";
  public $revision = "1.2";

  public $Link_ID  = 0;
  public $Query_ID = 0;
  public $Connected = false;

  public $Encoding = "";

  function DB_Sql($query = "") {
      $this->query($query);
  }

  function link_id() {
    return $this->Link_ID;
  }

  function query_id() {
    return $this->Query_ID;
  }

  function try_connect($DBDatabase = "", $DBHost = "", $DBPort = 0, $DBSocket = "", $DBUser = "", $DBPassword = "") {
    $this->Query_ID  = 0;
    
    if ("" == $DBDatabase)   $DBDatabase = $this->DBDatabase;
    if (0 == $DBPort)        $DBPort     = $this->DBPort;
    if ("" == $DBSocket)     $DBSocket   = $this->DBSocket;
    if ("" == $DBHost)       $DBHost     = $this->DBHost;
    if ("" == $DBUser)       $DBUser     = $this->DBUser;
    if ("" == $DBPassword)   $DBPassword = $this->DBPassword;

    restore_error_handler();

    $this->Link_ID = @mysqli_connect($DBHost, $DBUser, $DBPassword, $DBDatabase, $DBPort, $DBSocket);
    $this->Connected = $this->Link_ID ? true : false;

        set_error_handler("all_errors_handler", E_ALL);

    return $this->Connected;
  }

  function connect($DBDatabase = "", $DBHost = "", $DBPort = 0, $DBSocket="", $DBUser = "", $DBPassword = "") {
    
    if ("" == $DBDatabase)   $DBDatabase = $this->DBDatabase;
    if (0 == $DBPort)        $DBPort     = $this->DBPort;
    if ("" == $DBSocket)     $DBSocket   = $this->DBSocket;
    if ("" == $DBHost)       $DBHost     = $this->DBHost;
    if ("" == $DBUser)       $DBUser     = $this->DBUser;
    if ("" == $DBPassword)   $DBPassword = $this->DBPassword;

    if (!$this->Connected) {

      restore_error_handler();

      $this->Query_ID  = 0;
      $this->Link_ID = @mysqli_connect($DBHost, $DBUser, $DBPassword, $DBDatabase, $DBPort, $DBSocket);

      if (!$this->Link_ID) {
          $msg["code"] =  "2";
          
          $msg["message"] = "mysqli_connect($DBHost, $DBUser, \$DBPassword, $DBDatabase, $DBPort, $DBSocket) failed.";
          $this->halt($msg, '');

                  return 0;
      }
      set_error_handler("all_errors_handler", E_ALL);

      $server_info = @mysqli_get_server_info($this->Link_ID);
      preg_match("/\d+\.\d+(\.\d+)?/", $server_info, $matches);
      $version_str = $matches[0];
      $version = explode(".", $version_str);
      if ($version[0] >= 4) {
        if (($version[0] > 4 || $version[1] >= 1) && is_array($this->Encoding) && $this->Encoding[1])
          @mysqli_query($this->Link_ID, "set character set '" . $this->Encoding[1] . "'");
        elseif (is_array($this->Encoding) && $this->Encoding[0])
          @mysqli_query($this->Link_ID, "set character set '" . $this->Encoding[0] . "'");
      }

      $this->Connected = true;
    }
    
    return $this->Link_ID;
  }

  function free_result() {
    if (is_resource($this->Query_ID)) {
      @mysqli_free_result($this->Query_ID);
    }
    $this->Query_ID = 0;
  }

  function query($Query_String) {
    
    if ($Query_String == "")
      
      return 0;

    if (!$this->connect()) {
      return 0; 
    };

        if ($this->Query_ID) {
      $this->free_result();
    }

    if ($this->Debug)
      printf("Debug: query = %s<br>\n", $Query_String);

    $this->Query_ID = @mysqli_query($this->Link_ID, $Query_String);
    $this->Row   = 0;
    $this->Errno = mysqli_errno($this->Link_ID);
    $this->Error = mysqli_error($this->Link_ID);

    if (!$this->Query_ID) {
        $msg["code"]    = $this->Errno;
        $msg["message"] = $this->Error;
        $this->halt($msg, $Query_String );
        $this->Errors->addError("Database Error: " . mysqli_error($this->Link_ID));
    } else {
      if ($this->Link_ID->affected_rows == 0 and $this->Link_ID->warning_count > 0) {
        $e = $this->Link_ID->get_warnings();
        if ($e->errno == 1305) {
                  } else {
          $msg["code"] = 1329;
          $msg["code"] = $e->errno;
          $msg["message"] = $e->message.($msg["code"] == 1329 ? "" : " No data - zero rows fetched, selected, or processed ");
          $this->Errors->addError("Database Error: " . $msg["message"]);
          $this->halt($msg, $Query_String);
        }
      }
    }
        return $this->Query_ID;
  }

  function next_record() {
    if (!$this->Query_ID) 
      return 0;

    $this->Record = @mysqli_fetch_array($this->Query_ID, MYSQLI_BOTH);

    if ($this->Case !== false) {
      $this->Record = is_array($this->Record) ? array_change_key_case($this->Record, $this->Case) : $this->Record;
    }

    $this->Row   += 1;
    $this->Errno  = mysqli_errno($this->Link_ID);
    $this->Error  = mysqli_error($this->Link_ID);

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      $this->free_result();
    }
    return $stat;
  }

  function seek($pos = 0) {
    $status = @mysqli_data_seek($this->Query_ID, $pos);
    if ($status) {
      $this->Row = $pos;
    } else {
      $this->Errors->addError("Database error: seek($pos) failed -  result has ".$this->num_rows()." rows");

      @mysqli_data_seek($this->Query_ID, $this->num_rows());
      $this->Row = $this->num_rows();
    }
    return true;
  }

  function lock($table, $mode="write") {
    $this->connect();
    
    $query="lock tables ";
    if (is_array($table)) {
      while (list($key,$value)=each($table)) {
        if ($key=="read" && $key!=0) {
          $query.="$value read, ";
        } else {
          $query.="$value $mode, ";
        }
      }
      $query=substr($query,0,-2);
    } else {
      $query.="$table $mode";
    }
    $res = @mysqli_query($this->Link_ID, $query);
    if (!$res) {
      $this->Errors->addError("Database error: Cannot lock tables - " . mysqli_error($this->Link_ID));
      return 0;
    }
    return $res;
  }
  
  function unlock() {
    $this->connect();

    $res = @mysqli_query("unlock tables");
    if (!$res) {
      $this->Errors->addError("Database error: cannot unlock tables - " . mysqli_error($this->Link_ID));
      return 0;
    }
    return $res;
  }

  function affected_rows() {
    return @mysqli_affected_rows($this->Link_ID);
  }

  function num_rows() {
    return @mysqli_num_rows($this->Query_ID);
  }

  function num_fields() {
    return @mysqli_num_fields($this->Query_ID);
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    return $this->Record && array_key_exists($Name, $this->Record) ? $this->Record[$Name] : "";
  }

  function p($Name) {
    print $this->Record[$Name];
  }

  function nextid($seq_name) {
    $this->connect();
    
    if ($this->lock($this->Seq_Table)) {
      
      $q  = sprintf("select nextid from %s where seq_name = '%s' LIMIT 1",
                $this->Seq_Table,
                $seq_name);
      $id  = @mysqli_query($this->Link_ID, $q);
      $res = @mysqli_fetch_array($id);

      if (!is_array($res)) {
        $currentid = 0;
        $q = sprintf("insert into %s values('%s', %s)",
                 $this->Seq_Table,
                 $seq_name,
                 $currentid);
        $id = @mysqli_query($this->Link_ID, $q);
      } else {
        $currentid = $res["nextid"];
      }
      $nextid = $currentid + 1;
      $q = sprintf("update %s set nextid = '%s' where seq_name = '%s'",
               $this->Seq_Table,
               $nextid,
               $seq_name);
      $id = @mysqli_query($this->Link_ID, $q);
      $this->unlock();
    } else {
      $this->Errors->addError("Database Error: " . mysqli_error($this->Link_ID));
      return 0;
    }
    return $nextid;
  }

    function close()
    {
      if ($this->Query_ID) {
        $this->free_result();
      }
      if ($this->Connected && !$this->Persistent) {
        mysqli_close($this->Link_ID);
        $this->Connected = false;
      }
    }

    function halt($msg, $query) {
      $msg["message"] = str_replace(array("\\", '"', "/", "\n" , "\r", "\t", "\b"), array("\\\\", '\"', '\/', '\\n', '', '\t', '\b'), $msg["message"]);
      error_manager($msg["message"] . ' '.$query, $msg["code"], 'DB');
  }

  function table_names() {
    $this->query("SHOW TABLES");
    $i=0;
    while ($info=mysqli_fetch_row($this->Query_ID))
     {
      $return[$i]["table_name"]= $info[0];
      $return[$i]["tablespace_name"]=$this->DBDatabase;
      $return[$i]["database"]=$this->DBDatabase;
      $i++;
     }
   return $return;
  }
  
  function esc($value) {
    if ($this->Connected) {
      return mysqli_real_escape_string($this->Link_ID, $value);
    } elseif (function_exists("mysql_escape_string")) {
      return mysql_escape_string($value);
    } else {
      return addslashes($value);
    } 
  }

}

?>
