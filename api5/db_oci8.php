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



      
 

class DB_OracleOCI {
  public $Debug    =  0;
  public $sqoe     =  1; 
  public $DBDatabase = "";
  public $DBUser     = "";
  public $DBPassword = "";
  public $Persistent = false;
  public $Uppercase  = false;
  public $Case       = false;

  public $Record    = array();
  public $Row;

  public $Binds = array();

  public $Link_ID  = 0;
  public $Query_ID = 0;
  public $Connected = false;

  public $Encoding = "";

  public $Error     = "";

  
  function DB_Sql($query = "") {
  }

  function try_connect() {
    $this->Query_ID  = 0;

      $old_error_reporting = error_reporting();
      error_reporting($old_error_reporting & ~E_WARNING);

      if ($this->Persistent)
      $this->Link_ID = @OCIplogon ("$this->DBUser", "$this->DBPassword", "$this->DBDatabase");
    else
      $this->Link_ID = @OCIlogon ("$this->DBUser", "$this->DBPassword", "$this->DBDatabase");

      error_reporting($old_error_reporting);

    $this->Connected = $this->Link_ID ? true : false;
      if (!$this->Connected) {
          $this->Error=OCIError();
          $this->Halt($this->Error);
      }
    return $this->Connected;
  }

  function connect() {
      if (!$this->Connected) {
          $this->Query_ID  = 0;
          if($this->Debug) {
              printf("<br>Connecting to $this->DBDatabase...<br>\n");
          }

          $old_error_reporting = error_reporting();
          error_reporting($old_error_reporting & ~E_WARNING);

          if ($this->Persistent)
            $this->Link_ID=OCIplogon ("$this->DBUser","$this->DBPassword","$this->DBDatabase", $this->Encoding);
          else
            $this->Link_ID=OCIlogon ("$this->DBUser","$this->DBPassword","$this->DBDatabase", $this->Encoding);

          error_reporting($old_error_reporting);

          if (!$this->Link_ID) {
              $this->Error=OCIError();
              $this->Halt($this->Error);
                            return 0;
          }

          if($this->Debug) {
              printf("<br>Obtained the Link_ID: $this->Link_ID<br>\n");
          }   
          $this->Connected = true;
      }
      return $this->Connected;
  }

  function bind($parameter_name, $parameter_value, $parameter_length = -1, $parameter_type = 0)
  {
    if($parameter_length == -1 && $parameter_type == 0)
      $parameter_length = strlen($parameter_value);
    $this->Binds[$parameter_name] = array($parameter_value, $parameter_length, $parameter_type);
  }

  function query($Query_String) {
    
    if ($Query_String == "")
      
      return 0;
            restore_error_handler();
      $this->connect();

      $this->Query_ID = OCIParse($this->Link_ID, $Query_String);
      if(!$this->Query_ID) {
        $this->Error=OCIError($this->Query_ID);
          $this->Halt($this->Error);
      } else {
                    $old_error_reporting = error_reporting();
          error_reporting($old_error_reporting & ~E_WARNING);
          if(sizeof($this->Binds) > 0) {
              foreach ($this->Binds as $parameter_name => $parameter_values) {
                  if($parameter_values[2] == OCI_B_CURSOR)
                      $this->Binds[$parameter_name][0] = OCINewCursor($this->Link_ID);

                  if($parameter_values[2] == 0)
                      OCIBindByName ($this->Query_ID, ":" . $parameter_name, $this->Binds[$parameter_name][0], $parameter_values[1]);
                  else {
                                            OCIBindByName($this->Query_ID, ":" . $parameter_name, $this->Binds[$parameter_name][0], $parameter_values[1], $parameter_values[2]);
                  }
              }
          }

          OCIExecute($this->Query_ID);
                                                            error_reporting($old_error_reporting);

          $this->Error=OCIError($this->Query_ID);
      }

      $this->Row=0;

      if($this->Debug) {
          printf("Debug: query = %s<br>\n", $Query_String);
      }
      
      if ($this->Error["code"] != 0 && $this->sqoe) {
          $this->Halt($this->Error, $Query_String );
      }
      

      if(sizeof($this->Binds) > 0)
      {
        $bi = 0;
        foreach ($this->Binds as $parameter_name => $parameter_values) {
          if($parameter_values[2] == OCI_B_CURSOR) {
            OCIExecute($this->Binds[$parameter_name][0]);
            $this->Error=OCIError($this->Binds[$parameter_name][0]); 
            $this->Query_ID = $this->Binds[$parameter_name][0];
          }
          $this->Record[ $parameter_name ] = $parameter_values[0];
          $this->Record[ $bi++ ]           = $parameter_values[0];
        }
        $this->Binds = array();
      }

            set_error_handler("all_errors_handler", E_ALL);
      return $this->Query_ID;


  }
  
  function next_record() {
      if (!$this->Query_ID) 
        return 0;

      if(0 == @OCIFetchInto($this->Query_ID,$result,OCI_ASSOC+OCI_RETURN_NULLS)) {
          if ($this->Debug) {
            printf("<br>ID: %d,Rows: %d<br>\n",
              $this->Link_ID,$this->num_rows());
          }
          $this->Row        +=1;
          
          $errno=OCIError($this->Query_ID);
          if(1403 == $errno) {               $this->Error="";
              $this->disconnect();
              $stat=0;
          } else {
              $this->Error=OCIError($this->Query_ID);
              if($this->Debug) {
                  printf("<br>Error: %s",
                  $this->Error["message"]);
              }
              $stat=0;
          }
      } else { 
          for($ix=1;$ix<=OCINumcols($this->Query_ID);$ix++) {
              $col = OCIColumnname($this->Query_ID,$ix);
              $colreturn= $col;
              $this->Record[ "$colreturn" ] = $result["$col"]; 
              $this->Record[ $ix - 1 ] = $result["$col"]; 
              if($this->Debug) echo"<b>[$col]</b>:".$result["$col"]."<br>\n";
          }
          $stat=1;
      }

  return $stat;
  }

  function seek($pos) {
    $i = 0;
    while($i < $pos && @OCIFetchInto($this->Query_ID,$result,OCI_ASSOC+OCI_RETURN_NULLS)) {$i++; }

    $this->Row += $i;
    return true;
  }
           
  function metadata($table,$full=false) {
      $count = 0;
      $id    = 0;
      $res   = array();
      
    

      $this->connect();

                                          $this->query("SELECT T.table_name,T.column_name,T.data_type,".
           "T.data_length,T.data_precision,T.data_scale,T.nullable,".
           "T.char_col_decl_length,I.index_name".
           " FROM ALL_TAB_COLUMNS T,ALL_IND_COLUMNS I".
           " WHERE T.column_name=I.column_name (+)".
           " AND T.table_name=I.table_name (+)".
           " AND T.table_name=UPPER('$table') ORDER BY T.column_id");
      
      $i=0;
      while ($this->next_record()) {
        $res[$i]["table"] =  $this->Record[table_name];
        $res[$i]["name"]  =  strtolower($this->Record[column_name]);
        $res[$i]["type"]  =  $this->Record[data_type];
        $res[$i]["len"]   =  $this->Record[data_length];
        if ($this->Record[index_name]) $res[$i]["flags"] = "INDEX ";
        $res[$i]["flags"] .= ( $this->Record[nullable] == 'N') ? '' : 'NOT NULL';
        $res[$i]["format"]=  (int)$this->Record[data_precision].",".
                             (int)$this->Record[data_scale];
        if ("0,0"==$res[$i]["format"]) $res[$i]["format"]='';
        $res[$i]["index"] =  $this->Record[index_name];
        $res[$i]["chars"] =  $this->Record[char_col_decl_length];
        if ($full) {
                $j=$res[$i]["name"];
                $res["meta"][$j] = $i;
                $res["meta"][strtoupper($j)] = $i;
        }
        if ($full) $res["meta"][$res[$i]["name"]] = $i;
        $i++;
      }
      if ($full) $res["num_fields"]=$i;
      return $res;
  }


  function affected_rows() {
    return $this->num_rows();
  }

  function num_rows() {
    return OCIrowcount($this->Query_ID);
  }

  function num_fields() {
      return OCINumcols($this->Query_ID);
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    if($this->Uppercase) $Name = strtoupper($Name);
    if (array_key_exists($Name, $this->Record) && is_object($this->Record[$Name]))
    {
      return $this->Record[$Name]->load();
    } else
    {
      return $this->Record && array_key_exists($Name, $this->Record) ? $this->Record[$Name] : "";
    }
  }

  function p($Name) {
    if($this->Uppercase) $Name = strtoupper($Name);
    print $this->f($Name);
  }

  function nextid($seqname)
  {
    $this->connect();

    $Query_ID=@ociparse($this->Link_ID,"SELECT $seqname.NEXTVAL FROM DUAL");

    if(!@ociexecute($Query_ID))
    {
      $this->Error=@OCIError($Query_ID);
      if($this->Error["code"]==2289)
      {
        $Query_ID=ociparse($this->Link_ID,"CREATE SEQUENCE $seqname");
        if(!ociexecute($Query_ID))
        {
          $this->Error=OCIError($Query_ID); 
          $this->Errors->addError("Database error: " . $this->Error["message"]);
          return 0;
        } else 
        {
          $Query_ID=ociparse($this->Link_ID,"SELECT $seqname.NEXTVAL FROM DUAL");
          ociexecute($Query_ID);
        }
      }
    }

    if (ocifetch($Query_ID))
    {
       $next_id = ociresult($Query_ID,"NEXTVAL");
    } else
    {
       $next_id = 0;
    }
    ocifreestatement($Query_ID);
    return $next_id;
  }

  function disconnect() {
      if($this->Debug) {
          printf("Disconnecting...<br>\n");
      }
      OCILogoff($this->Link_ID);
  }


  function free_result() {
    @ocifreestatement($this->Query_ID);
    $this->Query_ID = 0;
  }

  function close()
  {
    if ($this->Query_ID) {
      $this->free_result();
    }
    if ($this->Connected && !$this->Persistent) {
      OCILogoff($this->Link_ID);
      $this->Connected = false;
    }
  }

  function halt($msg, $query=null) {
      $msg["message"] = str_replace(array("\\", '"', "/", "\n" , "\r", "\t", "\b"), array("\\\\", '\"', '\/', '\\n', '', '\t', '\b'), $msg["message"]);
      error_manager($msg["message"], $msg["code"], 'DB');
  }

  function lock($table, $mode = "write") {
    $this->connect();
    if ($mode == "write") {
      $Parse=OCIParse($this->Link_ID,"lock table $table in row exclusive mode");
      OCIExecute($Parse); 
    } else {
      $result = 1;
    }
    return $result;
  }
  
  function unlock() {
    return $this->query("commit");
  }

  function table_names() {
   $this->connect();
   $this->query("
   SELECT table_name,tablespace_name
     FROM user_tables");
   $i=0;
   while ($this->next_record())
   {
   $info[$i]["table_name"]     =$this->Record["table_name"];
   $info[$i]["tablespace_name"]=$this->Record["tablespace_name"];
   $i++;
   } 
  return $info;
  }

  function add_specialcharacters($query)
  {
  return str_replace("'","''",$query);
  }

  function split_specialcharacters($query)
  {
  return str_replace("''","'",$query);
  }
  
  function esc($value) {
    return str_replace("'", "''", $value);     
  }
}



?>
