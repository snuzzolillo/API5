<?php
/**
 * Created by PhpStorm.
 * User: seven-11
 * Date: 04/01/2018
 * Time: 11:54 AM
 */
// GLOBALS VARIABLES
$SYSTEM     = new stdClass(); // Variables del Sistema solo conocidas por el back-end
$GLOBALS    = new stdClass(); // Variables GLOBALES conocidas por el front-end y por el Back-End
$PARAMETERS = new stdClass(); // Variables PASADAS por BIND
$BINDED     = new stdClass(); // Lista de Parametros en el SQL y en el parametro POST $BIND
$BINDED_IN_SQL = array(); // Solo Lista de Parametros en el SQL, necesario para ORACLE BIND

// NOTA: Inicializar una varibale PHP con "= null;" retorna isset() en false
// TODO: Crear la lista de variables SYSTEM y como asignarle valor
// ejemplo:
$SYSTEM->{"SYSDATE"}     = 'date';

// TODO: Crear varibales GLOBALES que sean visibls en el servidor y en el BROWSER
//
$GLOBALS->{"username"}     = 'any user';

class clsCore {

    public static function parse_raw_http_request()
    {
        // USED WHEN HTTP header Content-Type = Application/json
        // normalmente enviado por angular JS
        // en estos casos PHP no hace el paersin autmatico y las parametros POST estan en el input

        // read incoming post data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        //preg_match('/-----------------------------(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        if (count($matches)) {
            //preg_match('/Content-Disposition\:\ form-data\;(.*)$/', $input, $matches);
            $boundary = $matches[1];
        } else {
            $boundary = "";
        }
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/--+$boundary/", $input);
        foreach ($a_blocks as $id => $block)
        {
            if (empty($block))
                continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE)
            {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
            // parse all other fields
            else
            {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                //$var[$matches[1]] = $matches[2];
            }
            #echo "Match $id\n";
            if (isset($matches[1])) $a_data[$matches[1]] = $matches[2];
        }
        return $a_data;
    }

    public static function validateSqlStatement($SQL, $type)
    {
        // %type esperados son
        // QUERY -- SOLO SELECT sin INTO
        // DML -- INSERT UPDATE DELETE SELECT .. INTO ...
        // TABLE
        // HRCHY
        if (strtoupper($type) == 'TABLE') {
            // nada que validar, pes la sentencia es construida
            return true;
        }
        $tmpSQL = strtoupper(trim($SQL));
        switch (strtoupper($type)) {
            case "QUERY" :
            case "HRCHY" :
                // DEBEN empezar por select
                if (substr($tmpSQL, 0, 7) !== 'SELECT ') {
                    // invalid
                    error_manager("SQL statement invalid for transaction type $type", -20190);
                }

                $pos = (strpos($tmpSQL, 'SELECT ') === 0 and strpos($tmpSQL, ' INTO ') > 0);
                #var_dump($pos);
                if ($pos) {
                    // invalid
                    error_manager("SQL statement invalid for transaction type $type", -20190);
                }
                break;
            case "DML" :
                // deben empezar por 'INSERT', 'DELETE', 'UPDATE'
                // o contener la pareja SELECT INTO
                $pos = (strpos($tmpSQL, 'INSERT ') === 0 || strpos($tmpSQL, 'DELETE ') === 0 || strpos($tmpSQL, 'UPDATE ') === 0);
                #var_dump($pos);
                if ($pos) break;
                $pos = (strpos($tmpSQL, 'SELECT ') === 0 and strpos($tmpSQL, ' INTO ') > 0);
                #var_dump($pos);
                if ($pos) break;
                // invalid
                error_manager("SQL statement invalid for transaction type $type", -20190);
                break;
            case "LOGIN" :
                break;
            default : error_manager("Invalid transaction type $type", -20189);
        }
        return true;
    }

    public static function returnJson($data=false, $error=false, $info=false, $header=false, $binded=false, $otherdata=false) {
        // Funcion unica de retorno del JSON al BROWSER como resultado del llamado a la API

        # ESTO ES GLOBAL ########################
        global $Charset;
        global $CCConnectionSettings;
        global $sourceName;
        global $resultAction;
        # #######################################

        # #######################################
        $ContentType    = "application/json";
        $Charset        = $Charset ? $Charset : "utf-8";

        if ($Charset) {
            header("Content-Type: " . $ContentType . "; charset=" . $Charset);
        } else {
            header("Content-Type: " . $ContentType);
        }
        # #######################################

        //
        // Los valores  $resultAction xxxonly determinan que el JSON resultante es un objeto unico
        //
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

        # si $otherdata trae valor, build el elemento result
        if ($otherdata) {
            // Other data es en caso de no desear una estructura extra como error, info etc.
            // normalmente usado para el caso de dataonly donde quien solicita la data solo
            // espera un json de data resultante. Esto tiene el mismo efecto que usar $resultAction
            // pero la estructura a retornar esta en $otherdata
            $result = ((!is_object($otherdata) and !is_array($otherdata)) ? json_decode($otherdata) : $otherdata);
        } else {
            //
            // Construye la estructura resultate creando una sub-estructura dependiendo de los valores enviados
            //
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
        //
        // Esta funcion fue creada para simplficar el retorno de un Record de la BD ya que el adaptador
        // por defecto retorna en el mismo array, los valores por posicion (numerico) y por referencia
        // "nombre de la columna"
        //
        # ELIMINA los elementos con indices numericos
        if ($db->next_record()) {
            foreach ($db->Record as $key => $val)
                if (is_numeric($key) and $part == 'string') // only numbers, a point and an `e` like in 1.1e10
                    unset($db->Record[$key]);
                else if (!is_numeric($key) and $part !== 'string')
                    unset($db->Record[$key]);
            return true;
        } else
            return false;
        #var_dump($db->Record);
    }

    public static function sqlSetParameters(& $db, $SQL, $bind){
        //
        // Los SQL statements pueden contener variables, las cuales son aquellas prefijadas con ":"
        // esta funcion permite ubicar dichas variables dentro del SQL y asignarles valores desde
        // la estructura $bind, la cual es normalmente enviada por el parametro POST $BIND
        //
        # Extrae las variables dentro del SQL y recostruye la statement segun sea el caso del tipo de DB
        # el resultado de las variables esta en $BINDED y los valores posibles estan en $PARAMETERS, $SYSTEM y $GLOBALS
        $SQL = clsCore::sqlBindVariables($SQL, $bind);
        #echo "sqlSetParameters -> sqlBindVariables : Todo bien hasta aqui $SQL\n";

        # Coloca los valores apareando las varibales en $BINDED con los valores correspondientes
        # de tal manera que queden disponibles por el SQL al momento de ejecucion, la tecnica depende del tipo de BD
        clsCore::setBindValues($db);

        #Retorna el SQL STATMENT recostruido si fuera el caso;
        return $SQL;
    }

    public static function normalizeJSONObjectAttrName($json, $case = CASE_LOWER){
        //
        // Esta funcion normaliza los nombres asociativos de un OBJETO y/o JSON
        // Por defecto todos son lowercase y es para poder referenciar a dichos nombre de una manera unica
        // por ejemplo "ProductID" se convierte en "productid"

        if (is_object($json) or is_array($json)) {
            $json = json_encode($json);
        }
        $object = json_decode($json, true);
        if (json_last_error()) {
            error_manager('normalizeJSONObjectAttrName JSON ERROR '.json_last_error_msg(), -20999);
        }
        //var_dump($object);
        if ($object) $object =  clsCore::normalizeObjectAttrName($object, $case );

        // return a json text
        return  json_encode($object);

    }

    public static function normalizeObjectAttrName($arr, $case = CASE_LOWER){
        // Asgura que el objeto a formarse desde el JSON sean todas en minusculas para evitar problema de tipeo
        // TRATA al OBJETO como un ARREGLO asociativo
        return array_map(function($item){
            if(is_array($item))
                $item = clsCore::normalizeObjectAttrName($item);
            return $item;
        },array_change_key_case($arr, $case));
    }


    public static function sqlTableOperation() {
        //
        // Esta funcion es exclusiva para operaciones directas de INSERT, UPDATE, DELETE con TABLAS
        // DENTRO DEL CRUD, HACE UN CUD
        // DEBE SER LLAMADA CUANDO transactiontype == table o __transaction_type=table
        // Nota:    la base de esta rutina fue tomada como generalizacion de un ejemplo de jqwidget.
        //          Ver la actualizaciones de gridManager.js para ver como llama a esta parte del codigo
        //          jqwidget envia sus propios parametros, por ello, en esta seccion
        //          los parametros estan prefidos por "__"
        //
        global $CCConnectionSettings;
        // Parametros
        // __transaction_type   = es el mismo parametro transactiontype
        // __table_name         = nombre de la tabla a la cual se efetuara la operacion
        // __operation_type     = insert,update,delete default=none;
        // __pk                 = lista de los campos que confran la clave primaria , default=none, es decir no especificada);
        // __row_id             = en el caso de ORACLE, el ROWID en char
        //                      , en caso de MySQL, es el nombre de la columna autoincrement
        //                      , default=null, es decir no especificada

        // tambien vienen por parametros con el nombre de la columna precedida por "__" los valores correspondientes al primary key, o con el nombre de la columna autoincrement
        $exclude_from_data = ["boundindex", "uniqueid", "visibleindex", "uid"];
        // "boundindex", "uniqueid", "visibleindex", "uid" son parametros que envia jqxwidget
        // en estos casos nos aseguramos de eliminar ou omitirlo de la lista de parametros
        $table_name         = CCGetParam("__table_name", "none");
        $operation_type     = CCGetParam("__operation_type", "none");
        $pk                 = CCGetParam("__pk", "none");
        $row_id             = CCGetParam("__row_id", ""); // Solo puede haber una
        //$ai                 = CCGetParam("__autoincrement", ""); // Solo puede haber una

        // YA ESTA COMPLETADO EN EL CUERPO PRINCIAL
        #$sourceName = CCGetParam("sourcename", "default");

        // GET DATASOURCE PARAMETERS
        #$datasource = file_get_contents("../textdb/" . $sourceName . ".sources.json.php");
        #$datasource = json_decode($datasource, true);
        #$CCConnectionSettings[$sourceName] = $datasource;
        // YA ESTABLECIDA EN LA global

        $error = false;
        $lastkey = "";
        $lastSQL = "";
        $db = new clsDBdefault();
        #echo $SQL."<br>";

        # Parámetros application/x-www-form-urlencoded No ordenar
        # __table_name
        # __transaction_type
        # Eliminar estosssss
        #   boundindex 9
        #   uniqueid 3020-18-16-21-302716
        #   visibleindex 9

        $DATA = array();
        //
        // crea un arreglo refencial dende la posicion de la tabla debe
        // coincidir con una columna de la tabla, y el valor, es el valor
        // esperado para esa columna
        //
        foreach ($_POST as $v => $val) {
            if (!in_array($v, $exclude_from_data) and !(substr($v, 0, 2) == "__")) {
                $DATA[$v] = $val;
                #echo "$v => $val<br>";
            }
        }

        // crea un arreglo con la lista de los columnas que conforman la clave primaria (si la tiene)
        // y que se espera que vino por parametro como una lista de colimnas separadas por ","
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
            #echo "<br>".$column_list."<br>";

            #echo "<br>".$where_condition."<br>";
            $SQL = str_replace('{column_list}', $column_list, $SQL);
            $SQL = str_replace('{where_condition}', $where_condition, $SQL);
            //echo "<br>".$SQL."<br>"; die;
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
            #echo "<br>".$column_list."<br>";

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
            // Ececute Operation
            if (strtoupper($db->Type) == "ORACLE" and $operation_type == "insert") {
                //
                // Insert y control para devolver el ultimo ROW_ID de la tabla
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
                        // Crear funcion basado en ROW_ID
                        //$lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);
                    }
                }
                if (in_array($operation_type, ["insert", "update", "delete"]) && !$error) {
                    $affected_rows = $db->affected_rows();
                    ##
                    ## NOTA:
                    ## Se asume que las operaciones de update y delete debe afectar exactamente a una fila, cero o mas de una es un error
                    ##
                    if ($affected_rows != 1) {
                        ##$e = $db->Link_ID->get_warnings();
                        ##var_dump($db);
                        $db->Error['code'] = -20098;
                        $db->Error['message'] = "warning ($affected_rows) rows affected when expected exactly 1";
                        $error = $db->Error['message'];
                    }
                }
            }

            $lastSQL = json_decode(($db->LastSQL));
            $lastSQL = json_encode($lastSQL);
        }

        // TODO: LLAMAR A returnJSON
        if ($error) {
            $json = '{"ERROR" : {"CODE":"' . $db->Error['code'] . '", "MESSAGE" : "' . $db->Error['message'] . '", "SQL":"' . htmlentities($lastSQL) . '"}}';
        } else {
            //$json = '{"ERROR" : {"CODE":"0", "MESSAGE" : "SUCCESS", "LAST_INSERT_ID":"'.$lastkey.'", "AFFECTED_ROWS":"'.$affected_rows.'", "SQL":"'.$db->LastSQL.'"}}';
            //$json = '{"ERROR" : {"CODE":"0", "MESSAGE" : "SUCCESS", "LAST_INSERT_ID":"' . $lastkey . '", "AFFECTED_ROWS":"' . $affected_rows . '", "SQL":"' . $lastSQL . '"}}';
            $json = '{"ERROR" : {"CODE":"0", "MESSAGE" : "SUCCESS"}'
             .', "INFO: { "LAST_INSERT_ID":"' . $lastkey . '", "AFFECTED_ROWS":"' . $affected_rows . '"}}';
            }
        echo $json;
        exit;
    }
    /*
     * Extrae de un archivo los comentarios con tags y los trata como contenido SQL
     * EJ:
     <PLSQL ANONYMOUS CASO1>
        IF :PARAMETER.RETIRADO='S' THEN
            UPDATE SOLICITUD
            SET    STATUS='T'
            WHERE  ID_SOLICITUD=:bLOQUE.NRO_SOL;
        END IF;
        <END>

     * EL HEADER
     *  PLSQL = tipo
     *  ANOMYMOUS = scope
     *  CASO1 = name
    */
    public static function sqlSplitFromFile($currentfile = ""){
        /*
         * Lee el contenido de un archivo si no tiene parametros se lee a si mismo
         */
        if (!$currentfile) {
            $currentfile = RelativePath . PathToCurrentPage . FileName;
        }
        $file = $currentfile;
        $path = pathinfo($file);
        if ($path['dirname'] === '.') {
            // Asumo el defecto.
            // Defecto = RelativePath ./textsql/
            $path['dirname'] = RelativePath . "/textsql/";
        }
        if ($path['extension'] === 'sql') {
            // Asumo el defecto.
            // Defecto = RelativePath ./textsql/
            //$path['filename'] .= '.'.$path['extension'];
            $path['extension'] .= '.php';
        }
        if ($path['extension'] !== 'sql.php' or $path['extension'] !== 'php') {
            // Asumo el defecto.
            // Defecto = RelativePath ./textsql/
            $path['filename'] .= '.'.$path['extension'];
            $path['extension'] = 'sql.php';
        } else {
            $path['filename'] .= '.'.$path['extension'];
            $path['extension'] = 'sql.php';
        }
        $file =  $path['dirname'].$path['filename'] . '.'.$path['extension'];
        #var_dump($file);
        $text = file_get_contents($file);
        /*
         * Una vez leido el texto envia a la rutina que descompone los comentarios
         */
        #echo "\nReturn Value Parsed=\n";
        return clsCore::sqlSplitFromStringWithTags($text);
        //return clsCore::sqlSplitFromString($text);
    }

    public static function sqlSplitFromString($text){
        /*
         * Recibe un texto (sin importar el contenido, y lo descompone en una lista dependiendo de tags dentro de comentarios tipo /*
         */
        //
        $start_tag = "/*<";
        $end_tag = ">*/";
        preg_match_all('#/\*\<(.*?)\>\*/#s', $text, $matches);

        foreach ($matches[0] as $i => $code) {
            // Elimina los comentarios y el <END>*/
            $codes[] = substr($code,2, strlen($code) - 9);
        }

        if (count($codes) == 0) {
            error_manager('No codes found on file parsing..', -20101);
        }
        foreach($codes as $ind => $code) {
            $head = substr($code, 1, strpos($code, '>')-1);
            $body = trim(substr($code, strpos($code, ">")+1));//, strpos($code, '<')-(strpos($code, ">")+1)));
            #echo "<br>\n";
            #echo 'CODIGO '.$code."<BR>\n";
            #echo "<br>HEAD $head<br>\n";
            $s = strtoupper($head);
            $s = explode( ' ', $s);
            #var_dump($s);
            // 0 - LANGUAGE
            // 1 - TYPE
            //      WHEN 'QUERY'
            //          2 - NAME
            //      WHEN 'TRIGGER'
            //          COMPLEX
            // 2 - NAME

            $scope = array();
            $scope["lang"] = $s[0]; #-- Lang ej: PLSQL
            if (isset($s[1])) {
                $scope["type"] = $s[1]; #-- tipo
                if ($scope["type"] == 'TRIGGER') {
                    $t = explode( ':', $s[2]);
                    #var_dump($t);
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
                    #$scope[3] = $s[2];
                    $name = $s[2] ;
                } else if ($scope["type"] == 'QUERY') {
                    $scope["name"] = $s[2];
                    #$scope[3] = $s[2];
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
        ##
        ## AHORA EL ARRAY SCOPE TIENE TODOS LOS CODIGOS SQL
        ##
        return $plsqlParsed;
    }

    public static function normalize_tags($str, $tag)
    {
        $start = strpos(strtoupper($str), '<'.strtoupper($tag));
        if ($start === false) return $str;
        $len = strlen($tag)+1;

        //var_dump($str);
        $str = strtoupper(substr($str, $start, $len)) . substr($str, $len);

        $start = strpos(strtoupper($str), '</'.strtoupper($tag));
        if ($start === false) return $str;
        $len = strlen($tag)+2;

        $str = substr($str, 0, strlen($str) - ($len+1)). strtoupper(substr($str, $start, $len+1));
        //var_dump($str); die;
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
        #echo $string."<br>\n";
        #var_dump($xml);
        if (count(libxml_get_errors())==0) {
            return $xml;
        } else {
            return false;
        }
    }

    public static function sqlSplitFromStringWithTags($text, $tag='sql'){
        /*
         * Recibe un texto (sin importar el contenido, y lo descompone en una lista dependiendo de tags dentro de comentarios tipo /*
         * el tag es <sql type= name= lang= scope=>sql command</sql>
         * respetando el formato XML para los attributos
         */
        //

        $plsqlParsed = array();
        // Primero,.. se obiene un ARRAY de texto encerrado entre comentarios /*....*/
        // los que cumplan con el tag <sql>...</sql> seran considerados comandos sql
        //
        $start_tag = "/*";
        $end_tag = "*/";
        preg_match_all('#/\*(.*?)\*/#s', $text, $matches);
        // hasta aqui, el array 0 debe tener el contenidos de tos los comentarios
        //
        #var_dump($matches);

        foreach ($matches[1] as $i => $code) {
            // 1 Estandarizar los tags entre mayuscula y minusculas SQL, Sql, sQl,sqL ...todos a sql
            $string = clsCore::normalize_tags(trim($code), $tag);
            //var_dump($string); die;

            // 2 tomar el tag con propiedades separadas por " " blancos
            $xml = clsCore::checkXmlString($string);
            //var_dump($xml); die;

            if ($xml) {
                $json = json_encode($xml);
                // normaliza los nombres de los atributos
                $json = clsCore::normalizeJSONObjectAttrName($json);
                // Ahora tengo un json (string) del contenido
                $obj = json_decode($json);
                $name = (isset($obj->{"@attributes"}->name) ? strtoupper($obj->{"@attributes"}->name) : "ANONYMOUS");
                $type = (isset($obj->{"@attributes"}->type) ? strtoupper($obj->{"@attributes"}->type) : "QUERY");
                $lang = (isset($obj->{"@attributes"}->lang) ? strtoupper($obj->{"@attributes"}->lang) : "SQL");
                $scope = (isset($obj->{"@attributes"}->scope) ? $obj->{"@attributes"}->scope : "");
                $body = $obj->{'0'};
                // Ahora tengo un objeto del contenido
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
            //$start_tag = "/*";
            //$end_tag = "*/";
            //preg_match_all('#/\*\<(.*?)\>\*/#s', $text, $matches);
            //$codes[] = substr($code,2, strlen($code) - 9);
        }

        if (count($plsqlParsed) == 0) {
            error_manager('sqlSplitFromStringWithTags: No codes found on file parsing..', -20102);
        }

        //var_dump($plsqlParsed); die;
        ##
        ## AHORA EL ARRAY SCOPE TIENE TODOS LOS CODIGOS SQL
        ##
        return $plsqlParsed;
    }

    public static function getSqlParsed($sqlParsed, $name = '1'){
        // $sqlParsed es el resultado de obtener uno o mas SQL desde un archivo a traves de sqlSplitFromFile()
        // Retorna el primer SQL
        if ($name === '1') {
            // solo retorna el primero
            foreach ($sqlParsed as $name => $data) {
                $SQL = $data->body;
                return $SQL; //una sola vez, es decir si hay varios queries solo toma el primero
            }
        } else {
            // retorna el que coincida con el nombre
            $name = strtoupper($name);
            if (isset($sqlParsed[$name])) {
                return $sqlParsed[$name]->body;
            }
        }
        // $sqlParsed es el resultado de obtener uno o mas SQL desde un archivo a traves de sqlSplitFromFile()
        // Retorna el primer SQL
        error_manager("getSqlParsed requested Parsed SQL $name not found", -20101) ;
    }

    public static function sqlBindVariables($currenString = "", $bind) {
        //
        // $bind es un objeto PHP de parejas de valores {"variable" : "value", ...}
        // Establece los valores de parametros antes de la ejecucion del SQL ,
        //      para ORACLE usa el BIND
        //      para MySql usa el set @var =
        // retorna el SQL con los nombres de variables estandarizados

        // TODO: EN PRUEBA Probar si genera un arreglo de variables que empiezan por ":" y que permita el CUALIFICADO
        // por elemplo SYSTEM.variable
        // TODO: todas las variables seran manejadas como MAYUSCULAS?
        //

        #global $plsqlParsed;
        global $CCConnectionSettings;
        global $sourceName;
        // Posibles Variables a binded
        global $SYSTEM;
        global $GLOBALS;
        global $PARAMETERS;
        global $BINDED;

        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];
        if (!$currenString) {
            exit;
        }

        $currenString = trim($currenString);

        // ESTO DEPENDE SOLO SI ES UNA TRANSACCIONAL, SI ES QUERY NO
        if (false) {
            if (substr($currenString, strlen($currenString) - 1, 1) !== ";") {
                $currenString = $currenString . ';';
            }
        }

        preg_match_all("/\\:\s*([a-zA-Z0-9_.]+)/ise", $currenString, $arr);

        // --------------------------------------------------------------
        // add to $bind values in $arr not in $bind
        // Esto hace incapturable el hecho de que no se hayan enviado valores para una variable
        // asumiendo el valor null
        // hummmm
        // --------------------------------------------------------------
        // AQUI en este MOMENTO arr[1] contiene los nombres de variables que estan el SQL
        // importarte GUARDARLO porque en caso de ORACLE solo se puede hacer bind que existan en el SQL
        // de los contrario da ERROR
        //
        global $BINDED_IN_SQL;
        $BINDED_IN_SQL = $arr[1];
        #echo "binded before "; print_r($bind);
        #echo "arr before "; print_r($arr[1]);

        foreach ($arr[1] as $i => $var) {
            $ok = false;
            foreach($bind as $j => $n) {
                #echo "bind = $j arr = $var\n";
                if ($j == $var) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $bind->{$var} = null;
            }
        }

        #echo "binded intermedio "; print_r($bind);
        #echo "AL REVES\n";

        foreach($bind as $j => $n) {
            $ok = false;
            foreach ($arr[1] as $i => $var) {
                #echo "bind = $j arr = $var\n";
                if ($j == $var) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $arr[1][] = $j;
            }
        }
        #echo "binded after "; print_r($bind);
        #echo "arr after "; print_r($arr[1]);
        // --------------------------------------------------------------

        # 1) Convierte todos los nombres en mayuscula
        # 2) si no esta cualificado, lo coloca en PARAMETERS

        // SI, un parametro no esta cualificado, colocalo en la estructura de PARAMETERS
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

        // TODO: CON EL BIND HACER LO MISMO pero crear un estructura con el nombre original
        //  , taratar de dertminar el tipo de datos
        //  , el tipo de datos que sea igual al de CCToSql
        // Hacer la sutitucion con CCToSql

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
                    // FORMATO ESTANDAR DE FECHA ESTABLECIDO 'yyyy-mm-dd HH-mi-ss'
                    if (isDateTime($val, 'Y-m-d H:i:s') or isDateTime($val, 'Y-m-d')) {
                        $tt = ccsDate;
                    } else {
                        $tt = ccsText;
                    }
                    break;
                default :
                    $tt = ccsText;
            }
            $PARAMETERS->{$new_name}->type = $tt; // retorna el tipo CCS
        }

        $BINDED = $arr;

        #echo "binded after after "; print_r($BINDED);

        if ($DB_TYPE == "MySQL") {
            foreach ($arr as $i => $toBind) {
                // $tobind esta en la forma
                // 1) PARAMETERS.VARIABLE y referencia a $PARAMETERS->{$toBind}->value
                // 2) SYSTEM.VARIABLE y referencia a SYSTEM->{$toBind} is the value
                // 3) GLOBAL.VARIABLE y referencia a GLOBALS->{$toBind} is rhe value

                $param = trim(str_replace(',', '', $toBind));
                $mysql_param = str_replace('.', '_', $param);
                $currenString = str_replace(':' . $param, '@' . $mysql_param, $currenString);
            }
        } else if ($DB_TYPE == "Oracle") {
            foreach ($arr as $i => $toBind) {
                // $tobind esta en la forma
                // 1) PARAMETERS.VARIABLE y referencia a $PARAMETERS->{$toBind}->value
                // 2) SYSTEM.VARIABLE y referencia a SYSTEM->{$toBind} is the value
                // 3) GLOBAL.VARIABLE y referencia a GLOBALS->{$toBind} is rhe value

                $param = trim(str_replace(',', '', $toBind));
                #$oracle_param = strtolower(substr($param,strpos($param,'.')+1, 1000));
                $oracle_param = str_replace('.','_',$param);
                #$oracle_param = str_replace('.', '_', $param);
                $currenString = str_replace(':' . $param, ':' . $oracle_param, $currenString);
            }
        }
        return $currenString;
    }

    public static function setBindValues(& $db) {
        // BIND o setea los valores para los parametros del SQL si tiene
        // En caso de ORTACLE via bind
        // en caso de mysql via @variable
        global $CCConnectionSettings;
        global $sourceName;

        global $SYSTEM;
        global $PARAMETERS;
        global $GLOBALS;
        global $BINDED;

        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];
        // Hace instruccion BIND directamente cuando es Oracle y la tecnica de SET @var cuando es MySQL
        // $arr tiene os parametros utilizados dentro del SQL
        // $PARAMETERS tiene los valores enviados como parametros
        // parametros SYSTEM deben estar en un objeto SYSTEM
        // parametros GLOBAL deben estar en un objeto GLOBAL
        // parametros PARAMETERS deben estar en $PARAMETERS

        // $db = new clsDBdefault();

        // TODO por ahora dentro del SQL quedaron las variables (o parametros) con el nombre ESTRUCTURA.NOMMBRE_VARIABLE
        // cambiar por referencias :0, :1.. donde 0,1,... son laposicion del arreglo $arr
        //


        if ($DB_TYPE == "Oracle") {
            //
            // NOTA IMPORTANTE: si se trata de hacer un bind de una variable que no exista el SQL
            //                  Resultara en un error

            // Antes $BINDED mantenia solamente las variables del SQL y se cambio para que tuvieran todas
            // SE requiere otra esctructura o un indicador que diga que ESTA el el SQL
            //

            // Existe un limite para el tamano del nombre de la variable BINDED, por loq que se recomendara no mayor a 32 caracteres

            //
            // Debo asegurarme que solo haga BIND de las variables que esten referenciadas en el SQL
            // para ello tuve que crear un nuevo arreglo $BINDED_IN_SQL que igue las mismas reglas que $BINDED, pero solo incluye las que esten en el SQL
            //
            global $BINDED_IN_SQL;

            foreach ($BINDED_IN_SQL as $i => $inSQL) {
                // Asigna a la estructura de $BINDED$ a la variable $toBind cuando el "nombre_original" es igual a $inSQL
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


                // $toBind ahora esta en la forma
                // 1) PARAMETERS.VARIABLE y referencia a $PARAMETERS->{$toBind}->value
                // 2) SYSTEM.VARIABLE y referencia a SYSTEM->{$toBind} is the value
                // 3) GLOBAL.VARIABLE y referencia a GLOBALS->{$toBind} is rhe value

                $param  = trim(str_replace(',', '', $toBind));
                $varname = substr($param,strpos($param,'.')+1, 1000);
                #echo "setBindValues Check value for $varname\n";
                $value =
                    (
                    #strpos($toBind, 'PARAMETERS.') !== false ? $PARAMETERS->{$toBind}->value
                    strpos($toBind, 'PARAMETERS.') !== false ? (isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value : error_manager('Unbinded PARAMETER variable "' . $varname . '"', 20005))
                        : (strpos($toBind, 'SYSTEM.') !== false ? (isset($SYSTEM->{$varname}) ? : error_manager("Unbinded SYSTEM variable \"" . $varname . "\"", 20004))
                        : (strpos($toBind, 'GLOBAL.') !== false ? $GLOBALS->{$varname}
                            : isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value
                            : error_manager('Unbinded variable "' . $PARAMETERS->{$varname}->original_name . '"', 20003)
                        )));
                $oracle_param = str_replace('.','_',$param);
                $db->bind($oracle_param, $value , 4000, SQLT_CHR);
                #echo " DONE\n\n";
            }
            if ($db->Errors->toString()) {
                error_manager('Error binding values "' . $db->Errors->toString(), 20003);
            }
            #echo "setBindValues TODO OK returning\n";
            return;
            // FIN BIND TIPO ORACLE
        } else if ($DB_TYPE == "MySQL"){

            //var_dump($BINDED);
            //var_dump($PARAMETERS);
            foreach ($BINDED as $i => $toBind) {
                // $tobind esta en la forma
                // 1) PARAMETERS.VARIABLE y referencia a $PARAMETERS->{$toBind}->value
                // 2) SYSTEM.VARIABLE y referencia a SYSTEM->{$toBind} is the value
                // 3) GLOBAL.VARIABLE y referencia a GLOBALS->{$toBind} is rhe value

                $param      = trim(str_replace(',', '', $toBind));
                $varname    = substr($param,strpos($param,'.')+1, 1000);
                $value      =
                    (
                    strpos($toBind, 'PARAMETERS.') !== false ? (isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value : error_manager('Unbinded PARAMETER variable "' . $varname . '"', 20005))
                        : (strpos($toBind, 'SYSTEM.') !== false ? (isset($SYSTEM->{$varname}) ? : error_manager('Undefined SYSTEM variable "' . $varname . '"', 20004))
                        : (strpos($toBind, 'GLOBAL.') !== false ? $GLOBALS->{$varname}
                            : isset($PARAMETERS->{$toBind}) ? $PARAMETERS->{$toBind}->value
                            //: error_manager('Unbinded variable "' . $PARAMETERS->{$varname}->original_name . '"', 20003)
                            : error_manager('Unbinded variable "' . (isset($PARAMETERS->{$varname}) ? $PARAMETERS->{$varname}->original_name : $toBind) . '"', 20003)
                        )));
                $mysql_param = str_replace('.','_',$param);
                $db->query("set @". $mysql_param ." = " . CCToSQL($value, ccsText).';');
                //var_dump($db);
                #echo ";<BR>\n";

                if ($db->Errors->toString()) {
                    error_manager('Error binding values "' . $db->Errors->toString(), 20003);
                }
            }
            return;
            // FIN BIND TIPO MySQL

        }
        error_manager('Binding values for DataBase type "' . $DB_TYPE . ", not implemented yet.", 20003);
    }

    public static function getBindValues(& $db)
    {
        // Actualiza los valores en la estructura PARAMETERS

        $DB_TYPE = $db->Type;
        global $PARAMETERS;

        if ($DB_TYPE == "Oracle") {

            #
            # GET BINDS
            #
            // SI LA SENTENCIA EJECUTADA ES ORACLE los valores BIND deben haber sido recuperados justo despues

            // SOLO MODIFICA LOS VALORES DE LA ESTRUCTURA PARAMETERS. NO SE PUEDEN MODIFICAR POR SELECT .. INTO ... NI SYSTEM ni GLOBAL
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
            // SI LA SENTENCIA EJECUTADA ES MY SQL los valores BIND deben haber sido recuperados justo despues
            // Recupera los valores de las @parameters
            $SQL = '';
            //foreach ($BINDED as $i => $toBind) {
            global $PARAMETERS;
            foreach ($PARAMETERS as $toBind => $obj) {

                $param = trim(str_replace(',', '', $toBind));
                $mysql_param = str_replace('.', '_', $param);
                $SQL .= (!$SQL ?  'SELECT ' : ',') . "@" . $mysql_param . ' as "'.$param.'"' ;

            }

            #echo $SQL;
            $db->query($SQL);
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            $db->next_record();

            // SET NEW VALUES to $PARAMETERS
            foreach($db->Record as $toBind => $value) {
                #if (isset($PARAMETERS[strtoupper($name)])) {
                if (!is_numeric($toBind)) {
                    #echo  "$toBind = $value\n";
                    $PARAMETERS->{strtoupper($toBind)}->value = $value;
                }
            }
        }
    }

    public static function getBindResult(& $db) {
        //
        // NOTA: los valeres BINDED puede cambiar solo bajo de ciertas circustancias (por ahora) y solamente involucra el array $PARAMETERS
        //       no debe afectar ni a $SYSTEM ni a $GLOBAL
        //      ejemplo:
        //      un SELECT ... INTO ... (En oracle se auto implementa como un BEGIN .. SELECT ... INTO; END; y en Mysql es una sentencia SQL aceptada
        //      un blque anonimo de ORACLE es decir una sentencia SQL con BEGIN ... END; donde los parametros (prefijados con ":" ) pueden tener asignacion de valores
        //      un llamado de procedimiento o funcion donde internamete cambia loa valores de parametros (prefijados con ":" )
        //

        // Partiendo del arreglo $PARAMETERS crear un objeto con los valores
        // este arreglo tiene como indice la referencia del parametro o variable ":" es decir la variable BINDED de la siguiente manera
        // si la variable BINDED no esta cualificada, se auto cualifica con PARAMETERS,
        //      ejemplo: el SQL referencia una variable :variable_name, el indice en $PARAMETERES es "PARAMETERS.variable_name"
        // si la variable BINDED esta cualificada, utiliza la CUALIFICACION, ejemplo "NOMBRE.variable_name"
        //      ejemplo: el SQL referencia una variable :nombre.variable_name, el indice en $PARAMETERES es "NOMBRE.variable_name"
        //
        // el resultado debe ser un arregloglo que sea
        // ["PARAMETERS"]->original_name = values
        // ["OTHERS"]->original_sub_cualified_name = values
        //
        global $CCConnectionSettings;
        global $sourceName;

        global $SYSTEM;
        global $PARAMETERS;
        global $GLOBALS;
        global $BINDED;


        $DB_TYPE = $CCConnectionSettings[$sourceName]["Type"];

        // SI LA SENTENCIA EJECUTADA ES ORACLE los valores BIND deben haber sido recuperados justo despues

        $result = new stdClass();

        // Nota importante.
        // se asume que los nombre de variables binded puede tener a lo sumo una sola subestructura por ejemplo :bloque.variable.
        // no puede ser por jemeplo :bloque.variable.otronombre
        foreach($PARAMETERS as $var => $obj) {
            #echo " $var \n";//print_r($obj);echo "\n";

            $x = pathinfo($var);
            $objName = $x['filename'];
            $varName = $x['extension'];
            #echo " Estructura = $objName variable = $varName \n";//print_r($obj);echo "\n";
            #var_dump($obj);
            $x = pathinfo($obj->original_name);
            $objOriginalName = isset($x['extension']) ? $x['filename'] : "";
            $varOriginalName = isset($x['extension']) ? $x['extension'] : $x['filename'];;
            #echo " Original Estructura = $objOriginalName variable = $varOriginalName \n";//print_r($obj);echo "\n";
            //var_dump($x);
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
        #var_dump($user);
        #var_dump($password);
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
            #var_dump($this);
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
        // TODO: es importante limitar el resultado, un resultado de mas de 10.000 linea pudiera traer problema con el buffer de PHP y enviar un resultado parcial e Invalido
        if (strtoupper($this->PageSize) == 'ALL') return $SQL;
        $PageSize = (int) $this->PageSize;
        if (!$PageSize) return $SQL;
        $Page = $this->AbsolutePage ? (int) $this->AbsolutePage : 1;

        ## En caso de Oracle

        if ($this->Type == "Oracle") {
            $SQL = "SELECT a.*, rownum a_count_rows FROM (".$SQL.") a where rownum <= ".(($Page) * $PageSize);
            $SQL = "SELECT * from (".$SQL.") where a_count_rows > ".(($Page - 1) * $PageSize)."";

        } else if ($this->Type == "MySql" or $this->Type == "MySQL") {
            if (strcmp($this->RecordsCount, "CCS not counted")) {
                $SQL = "SELECT * FROM (".$SQL.") a ". (" LIMIT " . (($Page - 1) * $PageSize) . "," . $PageSize);
                #$SQL =  (" LIMIT " . (($Page - 1) * $PageSize) . "," . $PageSize);
                #$SQL .= (" LIMIT " . ((($Page - 1) * $PageSize) + 1) . "," . $PageSize);
            } else {
                $SQL = "SELECT * FROM (".$SQL.") a ". (" LIMIT " . (($Page - 1) * $PageSize) . "," . ($PageSize + 1));
                #$SQL .= (" LIMIT " . (($Page - 1) * $PageSize) . "," . ($PageSize + 1));
            }
        }
        return $SQL;
    }
}


class clsResultDataSource extends clsDBdefault {

//DataSource Variables
    public $Parent = "";
    public $CCSEvents = "";
    public $CCSEventResult;
    public $ErrorBlock;
    public $CmdExecution;

    public $CountSQL;
    public $wp;
    public $Query;


    // Datasource fields

//End DataSource Variables

//DataSourceClass_Initialize Event
    function __construct(& $Parent)
    {
        clsResultDataSource($Parent);
    }

    function clsResultDataSource(& $Parent)
    {
        $this->Parent = & $Parent;
        $this->ErrorBlock = "Grid Result";
        $this->Initialize();

        # metadata(), construye la lista de campos que tiene el select
        $Parent->Metadata = metadata($this);

        foreach($Parent->Metadata->colsbyname as $col => $prop) {
            $this->{$col} = new clsField($col, $prop->type, ($prop->type == ccsDate ? $this->DateFormat : ""));
        }
    }
//End DataSourceClass_Initialize Event

//SetOrder Method
    function SetOrder($SorterName, $SorterDirection)
    {
        $this->Order = trim("$SorterName $SorterDirection");
        $this->Order = CCGetOrder($this->Order, $SorterName, $SorterDirection, "");
    }
//End SetOrder Method

//Prepare Method
    function Prepare()
    {
        global $CCSLocales;
        global $DefaultDateFormat;
    }
//End Prepare Method

//Open Method
    function Open()
    {
        global $transactiontype;
        // TODO : CHECK
        // TODO : 1) LIMIT no esta permitido en el SELECT el LIMIT se construye por los parametros PAGE y PAGESIZE
        // 2) SOLO SENTENCIAS SELECT son prmitidas
        // trigger $this->CCSEventResult = CCGetEvent($this->CCSEvents, "BeforeBuildSelect", $this->Parent);
        $this->SQL = $this->Parent->Query ." {SQL_Where} {SQL_OrderBy}";

        //clsCore::validateSqlStatement($this->SQL, $transactiontype );

        $this->CountSQL = "SELECT COUNT(*) from (\n\n" . $this->Parent->Query .") aszalst";
        // trigger $this->CCSEventResult = CCGetEvent($this->CCSEvents, "BeforeExecuteSelect", $this->Parent);
        if ($this->CountSQL)
            $this->RecordsCount = CCGetDBValue(CCBuildSQL($this->CountSQL, $this->Where, ""), $this);
        else
            $this->RecordsCount = "CCS not counted";
        #echo $this->SQL." ".$this->Where." ".$this->Order;exit;
        #echo $this->OptimizeSQL(CCBuildSQL($this->SQL, $this->Where, $this->Order));
        $this->query($this->OptimizeSQL(CCBuildSQL($this->SQL, $this->Where, $this->Order)));
        // trigger $this->CCSEventResult = CCGetEvent($this->CCSEvents, "AfterExecuteSelect", $this->Parent);
    }
//End Open Method

//SetValues Method
    function SetValues()
    {
        foreach($this->Parent->Metadata->colsbyname as $col => $prop) {
            $this->{$col}->SetDBValue(trim($this->f($col)));
            #echo "<br> $col = ".$this->{$col}->GetDBValue();
        }
    }
//End SetValues Method
}

/// CLASES Y MANEJO //////////////////////////////////////////////

class clsSqlResult {

//Variables

    // Public variables
    #public $ComponentType = "Grid";
    #public $ComponentName;
    public $Metadata;
    public $Query;
    #public $Visible;
    public $Errors;
    #public $ErrorBlock;
    public $ds;
    public $DataSource;
    public $PageSize;
    #public $IsEmpty;
    #public $ForceIteration = false;
    public $HasRecord = false;
    public $SorterName = "";
    public $SorterDirection = "";
    public $PageNumber;
    public $RowNumber;
    #public $ControlsVisible = array();

    #public $CCSEvents = "";
    #public $CCSEventResult;

    public $RelativePath = "";
    #public $Attributes;

    // Grid Controls
    #public $StaticControls;
    #public $RowControls;
//End Variables

//Class_Initialize Event

    function __construct($RelativePath, & $Parent, $Query, $WhereCondition, $SorterName, $SorterDirection) {
        #### elimin global $FileName;
        global $CCSLocales;
        global $DefaultDateFormat;

        #$this->ComponentName = "Result";
        #$this->Visible = True;
        $this->Records = array();
        $this->Parent = & $Parent;
        $this->RelativePath = $RelativePath;
        $this->Errors = new clsErrors();
        $this->ErrorBlock = "Result";
        #$this->Attributes = new clsAttributes($this->ComponentName . ":");

        ## ES IMPORTANTE MANEJAR EL SQL SEPARANDO EL SELECT + WHERE Y EL ORDER BY.
        ## AQUI ASUMINOS QUE NO HAY ORDER BY PUES DEBEMOS USAR UN LIMIT. SINO QUITARLO PROGRAMATICAMENTE.
        clsCORE::validateSqlStatement($Query, 'QUERY');
        $this->Query           = $Query;
        $this->SorterName      = $SorterName;
        $this->SorterDirection = $SorterDirection;

        $this->DataSource = new clsResultDataSource($this);
        $this->ds = & $this->DataSource;

        $this->DataSource->Where = $WhereCondition;

        ## YA AQUI DEBE EXISTIR EL METADATA QUE LO DEBE HABER CREADO clsResultDataSource
        ## VERIFICAR
        ## var_dump($this->Metadata);
        ## die;
        ## VERIFICADO (Y)
        ######################
        #$this->ds->Debug = 1;

        // ESTANDARIZANDO EL USO DE LIMIT
        // jqwidget envia dos parametros cuando trabaja con jqxGrid que son
        //      pagenum y pagesize que son el numero de la pagina (o lote, o buffer) deseado y el tamaño de cada pagina (o lote, o buffer)
        // Para una connotacion mas generalizadas utilizaremos entonces los terminas de
        //      lote, buffersize y pagesize son sinonimos y significan la cantidad de registros que enviara por cada solicitud
        //      numlote, buffernum y pagenum son sinonimos significan la posicion dendetro del resultado de dividir la cantiddad total de registros entreel tamano del lote
        //
        $this->PageSize = CCGetParam("pagesize", CCGetParam("buffersize", CCGetParam('lote','ALL'))); //es decir por defecto es TODOS los registros
        if (strtoupper($this->PageSize) == "ALL") {
            $this->PageNumber = intval(1);
        } else {
            $this->PageSize = intval($this->PageSize);
            $this->PageNumber = CCGetParam("pagenum", CCGetParam("buffernum", CCGetParam('numlote','1'))); //es decir por defecto es el primero
            $this->PageNumber = intval($this->PageNumber);
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
//End Class_Initialize Event

//Initialize Method
    function Initialize()
    {
        $this->DataSource->PageSize     = & $this->PageSize;
        $this->DataSource->AbsolutePage = & $this->PageNumber;
        $this->DataSource->SetOrder($this->SorterName, $this->SorterDirection);
        clsCore::setBindValues($this->DataSource);
    }
//End Initialize Method

//Show Method
    function Show()
    {
        global $jsonStyle;
        #$Tpl = CCGetTemplate($this);
        global $CCSLocales;
        $this->RowNumber = 0;

        $this->DataSource->Prepare();
        $this->DataSource->Open();

        if ($jsonStyle == 'OBJECT') while (clsCore::simplifyNextRecord($this->DataSource)) {
            //simplifyNextRecord elemina los elementos Numericos
            #var_dump($this->DataSource->Record);
            $this->Records[] = $this->DataSource->Record;
            $this->RowNumber++;
            #if ($this->RowNumber > 1000) break;
        }
        if ($jsonStyle == 'ARRAY') while (clsCore::simplifyNextRecord($this->DataSource, 'numeric')) {
            //simplifyNextRecord elemina los elementos String Key
            #var_dump($this->DataSource->Record);
            $this->Records[] = $this->DataSource->Record;
            $this->RowNumber++;
        }

    }
//End Show Method

//GetErrors Method
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
//End GetErrors Method
}
// End Class clsSqlRequest

class clsDMLResult {

//Variables

    // Public variables

//End Variables


    function __construct($SQL) {
        $this->exectuteDMLStatement($SQL);
    }

    function clsDMLResult($SQL)
    {
        self::__construct($SQL);
    }

    function exectuteDMLStatement($SQL = '')
    {
        // La diferencia de DML con respecto a otros es que debe retornar una estructura igual o aumentada del BIND
        // la razon es que en caso de SELECT INTO los valores en el BIND pueden variar o existen variables en SQL que
        // no estan en el BIND, por eso decimos que puede aumatar.
        // la structura debe retornar las variables BIND con sunombre original y en un formato de objeto

        if (substr($SQL, 0, 1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL, 1));
            $SQL = clsCore::getSqlParsed($sqlParsed);
        }

        #
        # VALIDA QUE EL SQL CORRESPONDE A UNA DML VALIDA
        #
        global $transactiontype;
        clsCORE::validateSqlStatement($SQL, $transactiontype);

        global $BIND; // Original $BIND parameter
        global $BINDED; // After set Parameters
        $bind = is_object($BIND) ? $BIND : new stdClass();

        $db = new clsDBdefault();

        #var_dump($BIND);

        $DB_TYPE = $db->Type;

        $lastkey = "";
        if ($DB_TYPE == "Oracle") {

            // TODO : Si es un INSERT detectar la tabla "$table_name" y ubicar el LAST ROW_ID y retornar como LAST_INSERTED_ID
            //
            // Requiero saber si es un insert y sobre que tabla es. Para el caso de Oracle es necesario para determinar el LAST ROWID
            // El insert es de la FORMA "INSERT INTO {TABLE} (......) VALUES (.....)
            // el nombre de la tbala es lo que este dentro de "INSERT INTO" y "/s*"
            //

            preg_match_all("/\\INSERT INTO\s*([a-zA-Z0-9_.]+)/ise", $SQL, $arr);

            $insertTable = false;
            if (isset($arr[1][0])) {
                $insertTable = $arr[1][0];
            }

            // Si es INSERT bloquea la tabla;
            if ($insertTable) {
                #echo "1\n";
                //$db->query("lock table  $insertTable in exclusive mode");
                //if ($db->Errors->ToString()) {
                //    error_manager($db->Errors->ToString(), -20101);
                //}
                $SQL = "". $SQL . " returning rowid||'' into :___lastkey; commit";
            }

            global $PARAMETERS;
            #echo "10\n";
            $SQL = clsCore::sqlSetParameters(
                $db                 // El conector
                , $SQL              // el SQL original tomado del sqlparsed
                , $bind             // Lista de parametros
            );

            #var_dump($PARAMETERS);
            $db->query("BEGIN " . $SQL . "; END;");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            #
            # GET BINDS
            #
            // SI LA SENTENCIA EJECUTADA ES ORACLE los valores BIND deben haber sido recuperados justo despues

            // SOLO MODIFICA LOS VALORES DE LA ESTRUCTURA PARAMETERS. NO SE PUEDEN MODIFICAR POR SELECT .. INTO ... NI SYSTEM ni GLOBAL
            clsCore::getBindValues($db);
            /*
             foreach ($PARAMETERS as $toBind => $obj) {
                $param = trim(str_replace(',', '', $toBind));
                $oracle_param = str_replace('.','_',$param);
                if (isset($db->Record[$oracle_param])) {
                    if ($oracle_param = "PARAMETERS____LASTKEY") {
                        $lastkey = $db->Record["PARAMETERS____LASTKEY"];
                        unset($PARAMETERS->{$toBind});
                    } else {
                        $obj->value = $db->Record[$oracle_param];
                    }
                }
            }
            */

            // Genera el objeto RESULT;
            $result = clsCore::getBindResult($db);

        } else if ($DB_TYPE == "MySQL"){

            $SQL = clsCore::sqlSetParameters(
                $db                 // El conector
                , $SQL              // el SQL original tomado del sqlparsed
                , $bind             // Lista de parametros
            );

            $db->query($SQL);
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            #
            # GET BIND
            #
            clsCore::getBindValues($db);

            // Build Result
            // la siguiente rutina, simplemente construye el objeto RESULT
            $result = clsCore::getBindResult($db);

            $lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);

        }

        $affected_rows = $db->affected_rows();
        #
        # Debe retorna INFO
        # Debe retornar ERROR
        # Debe retornae BINDED como RESULT
        #
        global $includeError;
        global $includeInfo;
        global $includeResult;

        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        clsCore::returnJson(
            false// data
            , $includeError ? '{"CODE":"0", "MESSAGE" : "SUCCESS"}' : false // ERROR
            , $includeInfo ? '{"LAST_INSERT_ID":"'.$lastkey.'", "AFFECTED_ROWS":"'.$affected_rows.'"}' : false
            , false // header
            , $includeResult ? $result : false // BINDED originales y/o modificados
        );

    }

}
// End Class clsDMLResult

function isDateTime($dateStr, $format){
    date_default_timezone_set('UTC');
    $date = DateTime::createFromFormat($format, $dateStr);
    return $date && ($date->format($format) === $dateStr);
}
