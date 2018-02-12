<?php

/*
 *
 API5 : retorna resultado de SQL en formatos JSON ...por ahora.
    Por razones de logica y seguridad esta API
    1) No tiene intenciones cross domain
    2) Solo espera peticiones XHR, es decir no reponde a una URL llamda directamente desde el browser
*/


# ----------------------------------------------------------------
# LAS SIGUIENTES LINEAS son para tratar de asegurarse que todos los errores de la API sean reportados por el objeto ERROR
# ----------------------------------------------------------------
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
            , -20999);
        //log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
    }
}

function all_errors_handler($errno, $errstr, $errfile, $errline) {
    error_manager(addslashes("API5 unhandled exception $errno:$errstr -> $errfile on $errline"), -20998);
}
# ----------------------------------------------------------------


# ----------------------------------------------------------------
/* AREA DE DEPENDENCIAS */
# ----------------------------------------------------------------
//Include Common Files @1-64426E2F
define("RelativePath", "..");
define("PathToCurrentPage", "/services/");
define("FileName", "api5.php");

require_once(RelativePath . "/Common.php");
require_once(RelativePath . "/services/dosqlClasses.php");
#include_once(RelativePath . "/services/getpage.php");
include_once(RelativePath . "/services/cryptojs-aes/cryptojs-aes.php");
include_once(RelativePath . "/services/cryptojs-aes/cryptojs-aes.php");
// JWT
require_once RelativePath . '/services/JWT/Firebase/JWT.php';
//End Include Common Files
# ----------------------------------------------------------------


# ----------------------------------------------------------------
# CONFIG Area
# ----------------------------------------------------------------

/* read Config File */
global $CONFIG;
$CONFIG = file_get_contents("../textdb/default.config.php");
$CONFIG = json_decode_and_validate(clsCore::getSqlParsed(clsCore::sqlSplitFromStringWithTags($CONFIG,'config'),'config'),'API5');

// AES encrypt ----------------------
global $AESpassPhrase;
$AESpassPhrase  = isset($CONFIG->AESpassPhrase) ? $CONFIG->AESpassPhrase : "" ;

// Token Key -----------------------
$tokenKey       = isset($CONFIG->tokenKey) ? $CONFIG->tokenKey : "";
# ----------------------------------------------------------------

$headers = apache_request_headers();
# GET FROM HEADER ej: "Authorization": "token=kdjdosanxadoidoqeuio"

# Diferenciar la forma en como leer los datos POST
# revisar que dice el header

if(isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    #echo "YES TRANSFORMING is set Content-Type ".$headers['Content-Type']."\n";
    $_POST = array_merge($_POST, (array) json_decode(trim(file_get_contents('php://input')), true));
    $_POST = array_merge($_POST, clsCore::parse_raw_http_request());
    #$a_data = array();
    #parse_raw_http_request($a_data);
    #var_dump($a_data);
    #die;
}

# GET FROM POST
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
set_time_limit(0); // Tiempo de ejecucion Ilimitado.

#var_dump($headers);
#var_dump($_SERVER); die;

if (!$token and $CONFIG->tokenRequired) {
    error_manager('Auhorization : Token required ', "SYS-5");
}

if ($token != "inside") {
    ### Validando si la peticion es valida. Primero, token
    if ($CONFIG->tokenRequired) {
        $decoded = JWT::decode($token, $tokenKey, array('HS256'));
        try {
            #echo PRUEBA "\n<br>";
            $appData = json_decode($decoded->data);
            if (json_last_error()) {
                throw new Exception ('JSON ERROR ' . json_last_error());
            }
            #echo $appData->username . "\n<br>";
            #echo $appData->userroles . "\n<br>";
            //die('TOKEN IS ' . $token);
        } catch (Exception $e) {
            error_manager('Unmanaged Error ( ' . $e . ')', 20001);
        }
    }
    ### CHEQUEADO HASTA AQUI

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
        #error_manager(4);
    }
}

/* *********************************************************************************************
 * API5 retrna un objeto json formado por varias substeructuras dependiendo del tipo de transaccion
 * *********************************************************************************************
 *   DATA: normalmente es el resultado del QUREY
 *
 *   ERROR: Siempre retorna el objeto ERROR, de existir error indicara el tipo de error y el mensaje. cuando no hay error es de esta forma  "ERROR" : {"CODE":"0", "MESSAGE" : "SUCCESS"}
 *
 *   INFO: normalmente asociado con un DML o con QUERY         "INFO": {"RECORDS_COUNT":"", "CURRENT_PAGENUMBER":"", "CURRENT_PAGESIZE":"", "LAST_INSERT_ID":"", "AFFECTED_ROWS":""},';
 *
 *   HEADER: en caso de QUERY contiene un objeto con la info de cada columna resultante del QUERY
 *
 *   RESULT: en caso de una DML retorna el objeto BINDED en caso de que esto fueron modificados.
 * *********************************************************************************************
 *
 * MANEJO DE PARAMETROS....
 *
 * transactiontype  = QUERY : return data result from QUERY (jsonstyle : object, array)
 *                    DML   : (jsonstyle ignored return object with result based on binded object and ERROR
 *                    DDL   : (jsonstyle ignored return object with ERROR)
 *                    HRCHY : return a tree like data structured (jsonstyle: SIMPLE, BYNODE, MASTER-DETAL)
 *                    TABLE : operacion DML sobre una tabla especifica
 *                    LOGIN:
 *
 * action         	=
 *      dataonly 		= retorna solo el objeto de DATA
 *      headeronly 	    = Retorna solo el objeto HEADER
 *      erroronly 	    = Retorna solo el objeto ERROR
 *      infoonly 	    = Retorna solo el objeto INFO
 *      resultonly 	    = Retorna solo el objeto RESULT
 *      all 			= retorna todos los objetos DATA, ERROR, INFO y HEADER, al menos que
 *                              se use includeerror=0, o includeheader=0
 *
 * SQL              = <un COMANDO SQL>. El nombre del paremetro es en MAYUSCULA
 *                    el contenido puede ser:
 *                    'anytext' donde anytext es el comando sql.
 *                    '@anytext' donde anytext es un elemento de $_SESSION['anytext'] que contiene el comando.
 *                    ':anytext' donde anytext es un archivo alojado en /textsql con extension .sql.php.
 *                               Este archivo debe tener una estructura especifica encerrado en comentarios y con tags especifico
 *                               el tag es <sql name= lang= scope=>sql command</sql>
 *
 * sortername       = nombre del campo por el que se desea ordenar como Order By. Un unico campo. Se espera cambiar por una lista separada por comas
 *
 * sorterdirection  = indica el orden en que se desea el Order B
 * asc				= orden ascendente
 * desc			    = order descentente
 *
 * sourcename       = nombre del data source, respresenta un archivo que debe estar definido y en este archivo estan los parametros necesarios para indicar los datos del servidor
 *
 * jsonstyle        = indica como se desea el resultado en el jsonstyle
 *      object = es el defecto. por nombre DATA : [{field_name:val, field_name:val}]
 *      array  = por posicion DATA : [{val,val}] las posiciones van en el mismo orden que resulten del select, es el select es un "defualt position" es decir "*", el orden es el mismo que se retorne en HEADER
 *
 * includeheader	    = indica si desea que retorne el OBJETO HEADER  default 1=YES
 *      1				= YES
 *      0				= NO
 *
 * includeerror    	= Indica si se desea que retorne el objeto ERROR. default 1=YES
 *      1				=YES
 *      0				=NO
 * includeinfo    	= Indica si se desea que retorne el objeto ERROR. default 1=YES
 *      1				=YES
 *      0				=NO
 * includeresult   = Indica si se desea que retorne el objeto ERROR. default 1=YES
 *      1				=YES
 *      0				=NO
 * jqwidget         = Para uso especial con los componenetes jqwidget
*/
// Si parametro jqwidget = true, toma los valores de estos parametros
// Parametros de JQWidget pagenum  ==> Parametro ResultPage
// Parametros de JQWidget pagesize ==> Paramentro ResultPageSize


## PARA EFECTOS DE PRUEBAS
//$_SESSION["CONNECTED"]["default"] = new stdClass();

// EJEMPLO USO VIA POST POR FORMS-JS
// ../services/api5.php?sourcename=source1
// el SQL via post.
//

#### elimin $dest           = CCGetParam("dest", "recordarray");
$resultAction   = CCGetParam("action", "all");
$action         = CCGetParam("action", "all");
$loginType      = strtoupper(CCGetParam("logintype", "LOCAL"));
#$defName        = CCGetParam("defname", "");

$SQL            = CCGetParam("SQL", "");
// TODO NOTA: CAMBIO DIC 2017
// EL SQL QUE COMIENCE CON ":",ES UN IDENTIFICADOR QUE REFERENCIA A UN ARCHIVO .sql.php en el diretorio textsql
// SE ELIMINA LO QUE TENGA QUE VER CON PIVOT, ejemplo defname

// TODO: Cambiar esto para otro momento, lo tengo en comentario
#if (substr($SQL,0,1) == ':') {
#    $SQL  = file_get_contents(RelativePath . "/textsql/".substr($SQL,1,strlen($SQL)-1).".sql.php");
#}
//
$BIND = CCGetParam("BIND","{}");
try {
    $BIND = json_decode($BIND);
    if (json_last_error()) {
        throw new Exception ('JSON ERROR '.json_last_error());
    }
} catch (Exception $e) {
    error_manager('BAD BINDED values ( '.$e.')', 20002);
}
//var_dump($BIND); die;
//
$SorterName         = CCGetParam('sortdatafield');
$SorterDirection    = CCGetParam('sortorder');

$sourceName         = CCGetParam("sourcename", "default");
$jsonStyle          = strtoupper(CCGetParam("jsonstyle", "OBJECT"));

$includeResult      = CCGetParam("icluderesult", "1");
$includeInfo        = CCGetParam("icludeinfo", "1");
$includeHeader      = CCGetParam("icludeheader", "1");
$includeError       = CCGetParam("includeerror", "1");



$transactiontype    = strtoupper(CCGetParam("transactiontype", CCGetParam("__transaction_type","QUERY")));


## ###################################################
## Para que funcione debe haber un DO_SQL_CONNECT // Esto cambia, API5 no es dependiente del js do_sql.js
## la seguridad sera manejada por token
##
## por ahora deja hacer operaciones sin chequear que hubo connect

### OJO PRUEBA QUITAR
$_SESSION["CONNECTED"] =  array();
$_SESSION["CONNECTED"][$sourceName] = new stdClass();
## ###################################################
if ($sourceName != 'default'
    and (!isset($_SESSION["CONNECTED"])
        or !isset($_SESSION["CONNECTED"][$sourceName])
    )
) {
    die('{"ERROR" : {"CODE":"2","MESSAGE":"NOT CONNECTED TO DATABASE '.$sourceName.'."}}');
}


// Es requerido un SQL de lo contrario no tiene sentido
if (!$SQL and !($transactiontype == 'LOGIN' and ($loginType == 'DATABASE' or $loginType == 'OS'))) {
    error_manager('NON SQL ','SYS-'.'10');
}

#if ($SQL) {
    if (!$sourceName) {
        error_manager('No datasource (or sourcename) defined ','SYS-'.'11'); // exit
    }

    #$sourceSQL  = $SQL;
#}

// GET DATASOURCE PARAMETERS
// VALIDA SI EXISTE
if (!file_exists("../textdb/" . $sourceName . ".sources.json.php")) {
    error_manager('Source name (sourcename or datasource) \"'.$sourceName. '\" do not exists.', 'SYS-'.'12');
}
$datasource = file_get_contents("../textdb/" . $sourceName . ".sources.json.php");
#var_dump($datasource);
//$datasource = json_decode($datasource, true);
$datasource = json_decode_and_validate($datasource, "Setting datasource $sourceName ",true);
if (json_last_error()) {
    echo json_last_error_msg()."\n";
}
$CCConnectionSettings[$sourceName] = $datasource;
#var_dump($CCConnectionSettings);

// KEEP IN MIND THIS POSIBILITY ---------------
#$query = <<<API5SQL
#   $sourceName and ANY TEXT
#API5SQL;
#var_dump($query); die;
// -------------------------------------------

//Initialize DB Objects
$DBmetadata = new clsDBdefault();

// BIFUCAR SI ES QUERY O TRANSACTION
//// TODO debe diferenciarse si es un query o una transaccion
////

#echo "\nBefore Transaction type=\n";
// ///////////////////////////
if ($transactiontype == 'LOGIN') {
    //echo "GO TO APILoginUser\n";
    APILoginUser($SQL, $loginType);
    die;
} else if ($transactiontype == 'QUERY') {
    // BLOQUE PRINCIPAL /////////////////////////////////////////////
    //// Parse parameters into SQL where viariables starts with ":"
    //// and normalize parameters NAMES into SQL text
    $sourceSQL = clsCore::sqlBindVariables($SQL, $BIND);
    //// Ahora $sourceSQL esta normalizado y los bind ejecutados

    // Variables
    #### elimin $FileName = "";
    #### elimin $Redirect = "";
    $Tpl = "";
    $TemplateFileName = "";
    $BlockToParse = "";
    $ComponentName = "";
    $Attributes = "";

    // Events;
    $CCSEvents = "";
    $CCSEventResult = "";
    $TemplateSource = "";

    $BlockToParse = "main";
    $TemplateEncoding = "UTF-8";
    //$ContentType = "text/html";
    $ContentType = "application/json";
    $Charset = $Charset ? $Charset : "utf-8";
    $PathToRoot = "../";
    //End Initialize Page

    //Before Initialize
    $CCSEventResult = CCGetEvent($CCSEvents, "BeforeInitialize", $MainPage);
    //End Before Initialize

    //Initialize Objects
    $DBmyDB     = new clsDBdefault();
    $MainPage->Connections[$sourceName] = &$DBmyDB;
    $Attributes = new clsAttributes("page:");
    $Attributes->SetValue("pathToRoot", $PathToRoot);
    $MainPage->Attributes = &$Attributes;

    // Controls
    ##### AQUI ENVIAR SELECT COMO PARAMETRO $sql
    $WhereCondition = buildWhereCondition();
    if ($WhereCondition) {
        #echo $WhereCondition; die;
    }

    ## AQUI SE INICIALIZA. Ocurre una conexion y trata de buscar el Metadato.

    ## El dilema es si dejar que ejecute la instruccion inicial o
    ## si es headersonly, vaya directo y exlusivamente a eso.
    ## Queda en espera de decidir

    // Crea e instancia el Objeto de datos el Objeto de Datos
    $Result = new clsSqlResult("", $MainPage, $sourceSQL, $WhereCondition, $SorterName, $SorterDirection);

    // Inicializa elObjeto de Datos
    $Result->Initialize();


    if ($action == "headeronly") {

        // En teoria, AQUI, aun no ha ido a buscar los datos, por lo headeronly
        //no consumira recursos adiciomales
        $header = $Result->Metadata->colsbyname;

        clsCore::returnJson(
            '{}'
            , '{"CODE":"0", "MESSAGE" : "SUCCESS"}'
            , '{"DB_TYPE":"'.$CCConnectionSettings[$sourceName]["Type"].'"}'
            , $header
        );
    }


    ######## AQUI EJECUTA EL QUERY RELACIONADO
    $Result->Show();
    ##########################################


    if ($action == "dataonly") {
        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        #clsCore::returnJson(false, false, false, false, false, $main_block);
        clsCore::returnJson(false, false, false, false, false, $Result->Records);

    }

    // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
    clsCore::returnJson(
        #$main_block // data
        $Result->Records // data
        , $includeError ? '{"CODE":"0", "MESSAGE" : "SUCCESS"}' : false // ERROR
        , '{"RECORDS_COUNT":"' . $Result->DataSource->RecordsCount . '", "CURRENT_PAGENUMBER":"' . $Result->PageNumber . '", "CURRENT_PAGESIZE":"' . (strtoupper($Result->PageSize) == 'ALL' ? $Result->RowNumber : $Result->PageSize) . '" }' // INFO
        , $includeHeader ? $Result->Metadata->colsbyname : false // header
    );

    //End Show Page

} else if ($transactiontype == "DML" ){

    $Result = new clsDMLResult($SQL);

    die;


} else if ($transactiontype == "TABLE" ){
    clsCore::sqlTableOperation();
} else if ($transactiontype == "HRCHY" ){
    // AQUI AHORA PUREBA DE ESTRUCTURA
    # echo 'CALL FROM INCLUDE '."<br>\n";
    include_once './dosqlHerachies.php';
    #echo "\nAfter include type=\n";
    $c = new clsHierarchiesResult($SQL, $jsonStyle);
    //$c->herachiesRequest();
} else {
    error_manager('Transaction Type "'.$transactiontype. '"" do not exists.', 21);
}

exit;

function changeFunctions(&$in_obj, &$sec, &$value_arr, &$replace_keys) {
    foreach($in_obj as $key => &$value){
        // Look for values starting with 'function('
        if (is_object($value) or is_array($value)) changeFunctions($value, $sec, $value_arr, $replace_keys );
        else {
            //echo $key . '=' . $value . '<br>';
            if (strpos($value, 'function(') === 0) {
                // Store function string.
                $value_arr[] = $value;
                // Replace function string in $foo with a 'unique' special key.
                $value = '%' . $key . '-' . $sec++ . '%';
                // Later on, we'll look for the value, and replace it.
                $replace_keys[] = '"' . $value . '"';
            }
        }
    }

}

function MetaStandardType($DBtype, $DATAtype, $DATAscale = 0) {
	#echo "-- DATA TYPE=".$DATAtype."----- ESCALA = ".$DATAscale;
	switch ($DBtype) {
		case "ORACLE" : switch($DATAtype) {
			//Internal Oracle Datatype 	Maximum Internal Length 	Datatype Code
			//
			//I INTERVAL YEAR TO MONTH	5 bytes	182
			//I INTERVAL DAY TO SECOND	11 bytes	183
			
			//T VARCHAR2, NVARCHAR2	4000 bytes	1
			//T LONG	2^31-1 bytes (2 gigabytes)	8
			//T ROWID	10 bytes	11 
			//T CHAR, NCHAR	2000 bytes	96
			//T CLOB, NCLOB	4 gigabytes	112			
			//T TIMESTAMP	11 bytes	180
			//T TIMESTAMP WITH TIME ZONE	13 bytes	181
			//T TIMESTAMP WITH LOCAL TIME ZONE	11 bytes	231			
			
			//F NUMBER	21 bytes	2
			//D DATE	7 bytes	12
			//* RAW	2000 bytes	23
			//* LONG RAW	2^31-1 bytes	24
			//* User-defined type (object type, VARRAY, Nested Table)	N/A	108
			//* REF	N/A	111
			//* BLOB	4 gigabytes	113	
			//* BFILE	4 gigabytes	114
			//* UROWID	3950 bytes	208

			case "2":  
				#echo "------- ESCALA = ".$DATAscale;
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
			//For version 4.3.4, types returned are:
			//
			//T STRING, VAR_STRING: string
			//I TINY, SHORT, LONG, LONGLONG, INT24: int
			//F FLOAT, DOUBLE, DECIMAL: real
			//D TIMESTAMP: timestamp
			//I YEAR: year
			//D DATE: date
			//D TIME: time
			//D DATETIME: datetime
			//T TINY_BLOB, MEDIUM_BLOB, LONG_BLOB, BLOB: blob
			//* NULL: null
			//Any other: unknown			
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
			//case "blob": 
			//	return ccsText;
			//	break;
			default: return ccsText; break;
		}
		case "MYSQLI" : switch($DATAtype) {
			//Codigos de tipos de datos devueltos por fetch_fields()
			//	
			//	Nombre        Codigo
			//	B boolean_    1
			//	I tinyint_    1
			//	I bigint_        8
			//	I serial        8
			//	I mediumint_    9
			//	I smallint_    2
			//	I int_        3
			//	I time_        11
			//	I year_        13
			//	F float_        4
			//	F double_        5
			//	F real_        5
			//	F decimal_    246
			//	D timestamp_    7
			//	D date_        10
			//	D datetime_    12
			//	* bit_        16
			//	T text_        252
			//	T tinytext_    252
			//	T mediumtext_    252
			//	T longtext_    252
			//	T tinyblob_    252
			//	T mediumblob_    252
			//	T blob_        252
			//	T longblob_    252
			//	T varchar_    253
			//	T varbinary_    253
			//	T char_        254
			//	T binary_        254			
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
		$META->colsbyname[ "$col" ]->{"is_null"}        = !(MYSQLI_NOT_NULL_FLAG & $property->flags) ;// decbin($property->flags ); //1;
		$META->colsbyname[ "$col" ]->{"primary_key"}    = !(!(MYSQLI_PRI_KEY_FLAG & $property->flags)) ;// decbin($property->flags ); //1;
		$META->colsbyname[ "$col" ]->{"auto_increment"} = !(!(MYSQLI_AUTO_INCREMENT_FLAG & $property->flags)) ;// decbin($property->flags ); //1;

        //if (CCGetParam("action", "") == "headeronly") {
        //    echo "MYSQLI FLAG=".MYSQLI_PRI_KEY_FLAG."<br>";
        //    var_dump($META->colsbyname[ "$col" ]);
        //}

        $META->cols[ $i ] = new stdClass();
	    $META->cols[ $i ]->{"type"}  	    = $standarType;
	    $META->cols[ $i ]->{"type_raw"}     = $type;
	    $META->cols[ $i ]->{"size"}   	    = $property->length;
		$META->cols[ $i ]->{"precision"}    = $property->decimals;
		$META->cols[ $i ]->{"scale"}	    = $property->decimals;
		$META->cols[ $i ]->{"is_null"}      = !(MYSQLI_NOT_NULL_FLAG & $property->flags) ;// decbin($property->flags ); //1;
        $META->cols[ $i ]->{"primary_key"}  = !(!(MYSQLI_PRI_KEY_FLAG & $property->flags)) ;// decbin($property->flags ); //1;
        $META->cols[ $i ]->{"auto_increment"} = !(!(MYSQLI_AUTO_INCREMENT_FLAG & $property->flags)) ;// decbin($property->flags ); //1;
		$i++;
		//FLAG (Posiciones binarias) :
		// NOT_NULL 
		// PRI_KEY  
		// UNIQUE_KEY
		// MULTIPLE_KEY
		// UNSIGNED
		// ENUM
		// AUTO_INCREMENT
		// GROUP
		// UNIQUE		
		
		// MYSQLI_NOT_NULL_FLAG & 49967
		// MYSQLI_PRI_KEY_FLAG & 49967
		// MYSQLI_UNIQUE_KEY_FLAG & 49967
		// MYSQLI_MULTIPLE_KEY_FLAG & 49967
		// MYSQLI_BLOB_FLAG & 49967

		//foreach ($property as $a => $val) {
		//	echo $a.' '.$val."<br>";
		//}

		//complete list of flags from MySQL source code:
		//
		//NOT_NULL_FLAG   1       /* Field can't be NULL */
		//PRI_KEY_FLAG    2       /* Field is part of a primary key */
		//UNIQUE_KEY_FLAG 4       /* Field is part of a unique key */
		//MULTIPLE_KEY_FLAG 8     /* Field is part of a key */
		//BLOB_FLAG   16      /* Field is a blob */
		//UNSIGNED_FLAG   32      /* Field is unsigned */
		//ZEROFILL_FLAG   64      /* Field is zerofill */
		//BINARY_FLAG 128     /* Field is binary   */
		//ENUM_FLAG   256     /* field is an enum */
		//AUTO_INCREMENT_FLAG 512     /* field is a autoincrement field */
		//TIMESTAMP_FLAG  1024        /* Field is a timestamp */
		//SET_FLAG    2048        /* field is a set */
		//NO_DEFAULT_VALUE_FLAG 4096  /* Field doesn't have default value */
		//ON_UPDATE_NOW_FLAG 8192         /* Field is set to NOW on UPDATE */
		//NUM_FLAG    32768       /* Field is num (for clients) */
		//PART_KEY_FLAG   16384       /* Intern; Part of some key */
		//GROUP_FLAG  32768       /* Intern: Group field */
		//UNIQUE_FLAG 65536       /* Intern: Used by sql_yacc */
		//BINCMP_FLAG 131072      /* Intern: Used by sql_yacc */
		//GET_FIXED_FIELDS_FLAG (1 << 18) /* Used to get fields in item tree */
		//FIELD_IN_PART_FUNC_FLAG (1 << 19)/* Field part of partition func */     
		

		#echo"<b>[$col]</b>:"
		#.$META->colsbyname["$col"]->type
		#.' '.$META->{"cols"}["$col"]->size
		#.' '.$META->{"cols"}["$col"]->precision
		#.' '.$META->{"cols"}["$col"]->scale
		#.' '.$META->{"cols"}["$col"]->is_null
		#.' type='.$META->{"cols"}["$col"]->type_raw
		#.' '."<br>\n";   
  	}
    #echo "mysqliMetadata=Fin<br>";
    #echo "mysqliMetadata=Fin<br>";
	return $META;
}               

function oracleMetadata(& $db) {   
	$id 	= $db->Query_ID;
	$META = new stdClass();

	#echo "SQL=".$db->LastSQL."<br>";
	#echo "Columnas =".OCINumcols($id)."<br>";

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
		
		//if($db->Debug) 
		#echo"<b>[$col]</b>:"
		#.$META->colsbyname["$col"]->type
		#.' '.$META->colsbyname["$col"]->size
		#.' Presicion='.$META->colsbyname["$col"]->precision
		#.' Ecala='.$META->colsbyname["$col"]->scale
		#.' '.$META->colsbyname["$col"]->is_null
		#.' type='.$META->colsbyname["$col"]->type_raw
		#.' '."<br>\n";   
	}   
	return $META;  
	
	
}                     
		
function metadata(& $db) {
	#
	# $db debe ser un objeto de DB de CodeCharge
	#
	#echo "Metadata Si me llamarion<br>";

    ## Elimina el order by, si lo tiene
	$re = "/ORDER BY.*?(?=\\s*LIMIT|\\)|$)/mi";
	$sql = preg_replace($re, "", $db->Parent->Query);
	#echo "<br>El select sin el Order by ".$sql;

    $tipo = $db->Type;
    if ( !(CCGetParam("action", "") == "headeronly") or strtoupper($tipo) == 'ORACLE') {
        #$this->query("select * from (". $sql .") x1q1 limit 1");
        #$this->query("select * from (". $sql .") x1q1 ");
        #$this->query( $sql );
        $db->query("select * from ($sql) any_table where 1=2");
        if ($db->Errors->ToString()) {
            die("Error ... " . $this->Errors->ToString());
        }

        $id = $db->Query_ID;
        if (!$id){
            $db->Errors->addError("Metadata query failed: No query specified.");
            return false;
        }

        #die($db->Query_ID);
    }

    $META = new stdClass();

	switch (strtoupper($tipo)) {
		case "ORACLE" :
			#echo "<br>DATABASE TYPE=".$db->Type."<br>\n\n"; 
			return oracleMetadata($db); 
			break;
		case "MYSQL"  :
            #var_dump($db->Parent->Query);
            if (CCGetParam("action", "") == "headeronly" and CCGetParam("statement_type", "table") == "table") {
                #echo "MYSQL DESCRIBE " . "para el Query = ".$db->Parent->Query."<br>";
                $tables = extratTablesOnSQL($db->Parent->Query);
                #var_dump($tables);
                if (count($tables) === 1) {
                    #echo "Usar Describe con la tabla " . $tables[0] . "<br>\n";
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

                    #echo 'mysqliMetadata llamado desde aqui    ';
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
                    #echo "<br>DATABASE TYPE=" . $db->DB . "<br>\n\n";
                    return mysqliMetadata($db);
                } else {
                    #echo "<br>DATABASE TYPE=" . $db->Type . "<br>\n\n";
                    return mysqlMetadata($db);
                }
            }
            break;
		default: return false;
	}
  	
  	return $META;
}

function mysqlDescribe(& $db, $table) {
    #echo "OK, Inside mysqlDescribe<br>";
    $db->query("describe ".$table);
    #
    if (!$db->Query_ID or $db->Errors->toString()){
        echo "Hubo error, se muestra? ".$db->Errors->toString()."<br>\n";
        $db->Errors->addError("Describe query failed: No query specified.");
        return false;
    }

    #
    $ix     = 0; // Column Indice
    $META = new stdClass();
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

        #print_r($db->DataSource->Record);
        $ix++;
    }
    return $META;
}

function extratTablesOnSQL($SQL) {

    //$SQL = "select * from (select * from (select * from demo.tabulado where 10=20 and x = 30 and a in (select * from xxx)) abc) where 1=2 ";

    function getListTable($text) {

        $text = preg_replace('/\s+/S', " ", $text); // Blacos extras , tabs y saltos de lineas extras
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        # Si hay un parentesis es porque era parte de un subselect. Elimina del ) hasta el fnal inclusive

        if (strpos($text, ')')) {
            $text = substr($text, 0, strpos($text, ')'));
        }
        #echo "Texto Original =".$text."<br>\n";

        $t_TABLE = '~\bfrom\b\s*(.*)\s*~si'; // casi OK
        preg_match_all($t_TABLE, strtolower($text), $matches);
        $ts = array();
        if (isset($matches[1])) {
            #var_dump($matches[1]);
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
                #echo "Separa por comas=".$text."<br>\n";
                #var_dump($rs);
                foreach ($rs as $i => $t) {
                    $wt = strpos($t, ' as ');
                    #echo "Tiene AS=".$t."<br>\n";
                    if ($wt !== false) {
                        $rs[$i] = trim(substr($t, 0, $wt));
                        $t = trim(substr($t, 0, $wt));
                        $ts[] = trim($t);
                    } else {
                        $t = trim($t);
                        $wt = strpos($t, ' ');
                        #echo "Tiene BLANCO=".$t."<br>\n";
                        if ($wt !== false) {
                            $rs[$i] = trim(substr($t, 0, $wt));
                            $t = trim(substr($t, 0, $wt));
                            #echo "Si Tiene BLANCO=".$t."<br>\n";
                            $ts[] = trim($t);
                        } else {
                            $ts[] = trim($t);
                        }
                    }
                }
            }
        }
        #echo "Antes de salir=".$text."<br>\n";
        #var_dump($ts);
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
                #echo "Valor de texto = ".$levels->text."<br>\n";
                $ts = getListTable($levels->text);
                #var_dump($ts);
                $tables = array_merge($ts,$tables);
                return;
            }
        }
    }

    #echo "<br>\nGo Partentesis<br>\n";
    $x = array();
    $x = getFromWhere(strtolower($SQL), $x, 'from', 'where', 0, '(',')');
    #var_dump($x);
    #die;
    $tables = array();
    getLastChild($x, $tables);
    #if (count($tables) === 1) {
    #    echo "Usar Describe con la tabla ".$tables[0]."<br>\n";
    #} else {
    #    echo "Usar metadata";
    #}

    #die;
    return $tables;
}

//BindEvents Method 
function BindEvents()
{
    global $Result;
    $Result->CCSEvents["BeforeShowRow"] = "ResultBeforeShowRow";
}
//End BindEvents Method

//ResultBeforeShowRow
function ResultBeforeShowRow(& $sender)
{
    $ResultBeforeShowRow = true;
    $Component = & $sender;
    $Container = & CCGetParentContainer($sender);
    global $Result; //Compatibility
//End ResultBeforeShowRow

//Format JSON 
    foreach ($Component->Metadata->colsbyname as $col => $prop) {
    	if ($prop->type == ccsText) {
    		$Component->{$col}->SetValue(str_replace(array("\\", '"', "/", "\n" , "\r", "\t", "\b"), array("\\\\", '\"', '\/', '\\n', '', '\t', '\b'), $Component->{$col}->GetValue()));
    	}
    }   
//End Format JSON

//Close ResultBeforeShowRow 
    return $ResultBeforeShowRow;
}
//End Close ResultBeforeShowRow

function json_validate($string,$flag=false)
{
    // clena the string
    $string = str_replace("\n", "", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\t", " ", $string);


    // decode the JSON data
    $result = json_decode($string, $flag);

    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid // No error has occurred
            break;
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
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
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
        // throw the Exception or exit // or whatever :)
        exit($error);
    }

    // everything is OK
    return $result;
}

function json_decode_and_validate($string,$in_case_error,$flag=false)
{
    // clena the string
    $string = str_replace("\n", "", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\t", " ", $string);

    //echo $string."<rn>\n";
    // decode the JSON data
    $result = json_decode($string, $flag);

    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid // No error has occurred
            break;
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
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
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
        // throw the Exception or exit // or whatever :)
        error_manager($in_case_error." : ".$error, -20301);
    }

    // everything is OK
    return $result;
}

function error_manager($msg, $code=3, $status = 400)
{
    $e = new stdClass();
    $e->{'ERROR'} = new stdClass();
    http_response_code($status);
    if ($msg == '5') {
        $e->ERROR->{'CODE'} = $code;
        $e->ERROR->{'MESSAGE'} = "BAD REQUEST $msg";
        //$e = '{"ERROR" : {"CODE":"'. $code . '", "MESSAGE" : "' . "BAD REQUEST $msg".'"}}'; //, "TYPE" : "'.$CCConnectionSettings[$sourceName]["Type"].'"}}';
    } else {
        $e->ERROR->{'CODE'} = $code;
        $e->ERROR->{'MESSAGE'} = $msg;
        $e->ERROR->{'USERID'} = CCGetSession("USERID");
        //$e = '{"ERROR" : {"CODE":"' . $code . '", "MESSAGE" : "' . "$msg".'"}}'; // " . '", "TYPE" : "'.$CCConnectionSettings[$sourceName]["Type"].'"}}';
    }
    die(json_encode($e));
}

function buildWhereCondition() {
    //
    // Asumo, que no recuerdo que estos parametros son enviados por jqwidget
    // para mantener el filtrado del grid
    // Asumo tambien que para este momento, el select original esta encerrado en un subselect
    //

    // filter data.
    $filterscount = CCGetParam("filterscount", "0");
    $where = "";
    #if (isset($_GET['filterscount'])) {
    #$filterscount = $_GET['filterscount'];
    if ($filterscount) {
        $where = " (";
        $tmpdatafield = "";
        $tmpfilteroperator = "";
        $valuesPrep = "";
        $values = [];
        for ($i = 0; $i < $filterscount; $i++) {

            $filtervalue = CCGetParam("filtervalue" . $i); // get the filter's value.
            $filtercondition = CCGetParam("filtercondition" . $i); // get the filter's condition.
            $filterdatafield = CCGetParam("filterdatafield" . $i); // get the filter's column.
            $filteroperator = CCGetParam("filteroperator" . $i); // get the filter's operator.

            if ($tmpdatafield == "") {
                $tmpdatafield = $filterdatafield;
            } else if ($tmpdatafield <> $filterdatafield) {
                $where .= ")AND(";
            } else if ($tmpdatafield == $filterdatafield) {
                if ($tmpfilteroperator == 0) {
                    $where .= " AND ";
                } else $where .= " OR ";
            }
            // build the "WHERE" clause depending on the filter's condition, value and datafield.
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

                // COMO NEGAR A TODOS
            }
            $where .= " " . $filterdatafield . $condition . CCToSQL($value, ccsText);
            //$valuesPrep = $valuesPrep . "s";
            //$values[] = & $value;
            if ($i == $filterscount - 1) {
                $where .= ")";
            }
            $tmpfilteroperator = $filteroperator;
            $tmpdatafield = $filterdatafield;
        }
        //$valuesPrep = $valuesPrep . "ii";
        //$values[] = & $start;
        //$values[] = & $pagesize;
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

    #echo "\n getParentesis ENTER WITH = " . $level . " text=$text"."<br>\n";

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

    #if ($usingGroup and $level === 0) {
    #    #echo "Using Group = " . $usingGroup . " New Group=" . substr($text, 0, 200) . "<br>\n";
    #    $text = '('.$text.')';
    #}
    while ($i <= $max) {
        if ($usingGroup and substr($text, $i, strlen($groupOpen)) === $groupOpen) {
            $inGroup++;
            #echo "Group = " . $inGroup . " New Group=" . substr($text, $i, 200) . "<br>\n";
        } else if ($usingGroup and substr($text, $i, strlen($groupClose)) === $groupClose) {
            #echo "Group = " . $inGroup . " END Group=" . substr($text, $i, 200) . "<br>\n";
            $inGroup--;
        } else if (substr($text, $i, strlen($tagOpen)) === $tagOpen and (!$open or !$inGroup)) {
            #echo "Level = " . $level . " Start Tag Found=" . substr($text, $i, 200) . "<br>\n";
            array_push($start, $i);
            array_push($start_tag, $tagOpen);
            $open++;
        } else if (substr($text, $i, strlen($tagClosed)) === $tagClosed and !$inGroup) {
            #echo "Level = " . $level . " END Tag Found=" . substr($text, $i, 200) . "<br>\n";
            array_push($end, $i);
            array_push($end_tag, $tagClosed);
            $close++;
        }

        if (end_of_line($text, $i)) {
                array_push($end, $i);
                array_push($end_tag, ")");
                $close++;
                #echo "Level = " . $level . " End of Line=" . $text . " OPEN=$open CLOSE=$close<br>\n";
        }
        if ($open == $close and $open) {
            while (count($start) > 0) {
                #echo "Level = " . $level . " test=" . $text . "<br>\n";
                array_push($levels, new stdClass());
                $n = count($levels) - 1;
                $levels[$n]->start = array_shift($start); //[0];
                $levels[$n]->end = array_pop($end);
                $levels[$n]->endTag = array_pop($end_tag);
                $levels[$n]->startTag = array_shift($start_tag);
                $levels[$n]->text = substr($text, $levels[$n]->start, (($levels[$n]->end + (strlen($levels[$n]->endTag) - 1)) - $levels[$n]->start) + 1);
                $levels[$n]->levels = array();
                #echo "\n getParentesis SALE CON WITH = " . $level . " text="
                #    . substr($levels[$n]->text
                #        , strlen($levels[$n]->startTag)
                #        , ($levels[$n]->text - (strlen($levels[$n]->endTag)))) . "<br>\n";
                getFromWhere(
                    #substr($levels[$n]->text, 1, $levels[$n]->text - 2)
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
/*
    foreach ($start as $i => $val) {
        $levels[$i] = new stdClass();
        $levels[$i]->start = $start[$i];
        $levels[$i]->end = $end[$i];
        $levels[$i]->text = substr($text, $start[$i], $end[$i] - $start[$i]);
        $levels[$i]->levels = array();
        getParentesis($levels[$i]->text, $levels[$i]->levels);
    }
*/
    //print_r($levels);
    return $levels;
}

function APILoginUser($SQL, $loginType = 'LOCAL')
{
    global $BIND;
    global $PARAMETERS;

    // inicializa SYSTEM and $SESSION variables -------------------------
    CCSetSession('USERNAME', null);
    CCSetSession('USERID', null );
    CCSetSession('ROLES', null);
    global $SYSTEM;
    $SYSTEM->{'USERNAME'} = CCGetSession('USERNAME');
    $SYSTEM->{'USERID'} = CCGetSession('USERID');
    $SYSTEM->{'ROLES'} = CCGetSession('ROLES');
    // ---------------------------------------------------------------------
    #var_dump($SQL); die;

    $bind = is_object($BIND) ? $BIND : new stdClass();

    switch(strtoupper($loginType)) {
        case "LOCAL" :
            // EN ESTE CASO LA SENTENCIA DEBE ESTAR EN UN ARCHIVO
            // NO SE ESPERA UN LOSQL EN TEXTO POR RAZON DE SEGURIDAD
            // SE ESPERAN DOS INSTRUCCIONES EN EL ARCHIVO
            // UNA con NAME="LOGIN" y otra con NAME="ROLES"
            // es requrido y necesario que los parametros
            // username, userid y roles existan exactamente asi

            if (substr($SQL, 0, 1) == ':') {
                // Extrae TODOS los SQL del archivo
                $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL, 1));
                //var_dump($sqlParsed);
                // Toma solo el primero
                $SQL = clsCore::getSqlParsed($sqlParsed, "LOGIN");
            }

            $db = new clsDBdefault();

            // Es en dos fases el LOGIN que autentifica el esuausrio y el ROLES que define los roles de este

            // EL LOGIN
            $SQL = clsCore::getSqlParsed($sqlParsed, "LOGIN");
            $SQL = clsCore::sqlSetParameters(
                $db                 // El conector
                , $SQL              // el SQL original tomado del sqlparsed
                , $bind             // Lista de parametros
            );


            $db->query($SQL);

            // Before return ERROR manage specific NO ROWS SELECTED para indicar username/password NOT FOUND?
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            // Actualiza PARAMETERS con los valores Binded
            clsCore::getBindValues($db);

            if (isset($sqlParsed["ROLES"])) {
                // LOS ROLES aqui se espera una o mas filas, asi que ROLES debe ser elaborado como array.
                $SQL = clsCore::getSqlParsed($sqlParsed, "roles");

                // SI NO HAY $SQL no ejecuta, y no es error. Se asume que es un valor unico retornado por el LOGIN
                // Cambia o asigna el valor de la variable username en caso de que no esta en el bind original
                $bind->{'username'} = $PARAMETERS->{"PARAMETERS.USERNAME"}->value;
                $bind->{'userid'}   = $PARAMETERS->{"PARAMETERS.USERID"}->value;
                $bind->{'roles'}   = $PARAMETERS->{"PARAMETERS.ROLES"}->value;

                $SQL = clsCore::sqlSetParameters(
                    $db                 // El conector
                    , $SQL              // el SQL original tomado del sqlparsed
                    , $bind             // Lista de parametros
                );


                $db->query($SQL);
                while (clsCore::simplifyNextRecord($db)) {
                    if (!is_array($PARAMETERS->{"PARAMETERS.ROLES"}->value))
                        $PARAMETERS->{"PARAMETERS.ROLES"}->value = array();

                    $PARAMETERS->{"PARAMETERS.ROLES"}->value[] = $db->Record['role'];
                }
            }

            break;

        case "DATABASE" :
            #echo "Start DATABASE CASE \n";
            #var_dump($bind);
            $db = new clsDBdefault($bind->username, $bind->password);
            $connect = $db->Provider->try_connect();
            #var_dump($connect);
            // connect cierto es usuario correcto, falso no
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
            #die;
            break;
        case "OS" :
            $authorized = false;

            # LOGOUT
            #if (isset($_GET['logout']) && !isset($_GET["login"]) && isset($_SESSION['auth']))
            #{
            #    $_SESSION = array();
            #    unset($_COOKIE[session_name()]);
            #    session_destroy();
            #    echo "logging out...";
            #}

            # GET THE PASSOWRDS LIST ON
            $file = dirname(__FILE__). "/.htpasswd";

            function crypt_apr1_md5($plainpasswd, $crypted) {
                #
                # Recreate Password extracting salt from previusly encrypted "$crypted"
                #
                # PASSWORDS GENERATE WITH MD5
                # using http://www.htaccesstools.com/htpasswd-generator/
                #

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

            // Loads htpasswd file into an array of form
            // Array( username => crypted_pass, ... )
            function load_htpasswd($file)
            {
                #echo "FILE= $file\n";
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

            #var_dump($users);
            # checkup login and password
            if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                // Password to be encrypted for a .htpasswd file
                $pass = $_SERVER['PHP_AUTH_PW'];
                $user = $_SERVER['PHP_AUTH_USER'];
                // Encrypt password
                //$pass = crypt($pass, base64_encode($pass));
                $users =  load_htpasswd($file);

                if (isset($users[$user])) {
                    #echo "File pass " . $users[$user] . "\n";
                    $pass = crypt_apr1_md5($pass, $users[$user]);
                    #echo "User  $user\n";
                    #echo "Encripted pass $pass\n";

                    if (isset($users[$user]) && ($users[$user] == $pass)) {
                        $PARAMETERS->{"PARAMETERS.USERNAME"} = new stdClass();
                        $PARAMETERS->{"PARAMETERS.USERNAME"}->value = $user;
                        $PARAMETERS->{"PARAMETERS.USERNAME"}->original_name = 'username';

                        $PARAMETERS->{"PARAMETERS.ROLES"} = new stdClass();
                        $PARAMETERS->{"PARAMETERS.ROLES"}->value = 'API-SU';
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

    // REMOVE PASSWORD FROM PARAMETERS
    unset($PARAMETERS->{"PARAMETERS.PASSWORD"});

    // ADD VALUES TO SYSTEM and $SESSION variables -------------------------
    CCSetSession('USERNAME', $PARAMETERS->{"PARAMETERS.USERNAME"}->value );
    CCSetSession('USERID', $PARAMETERS->{"PARAMETERS.USERID"}->value );
    CCSetSession('USERROLES', $PARAMETERS->{"PARAMETERS.ROLES"}->value );
    global $SYSTEM;
    $SYSTEM->{'USERNAME'} = CCGetSession('USERNAME');
    $SYSTEM->{'USERID'} = CCGetSession('USERID');
    $SYSTEM->{'USERROLES'} = CCGetSession('USERROLES');
    // ---------------------------------------------------------------------

    global $CONFIG;
    if ($CONFIG->autenticationmethod == 'TOKEN') {
        // GENERATE TOKEN
        $token = array(
            "iss" => "API5"
        ,"sub" => "API5"
        ,"aud" => "user"
        ,"iat" => time()
        ,"exp" => time()+ (7 * 24 * 60 * 60) // A week
        ,"nbf" => 1357000000
            ## STANDARS
        ,"uid" => $SYSTEM->USERID
        ,"data" => '{"username":"'.$SYSTEM->USERNAME.'"'
                .', "userroles":'.(is_array($SYSTEM->USERROLES) ? json_encode($SYSTEM->ROLES) : '"'.$SYSTEM->USERROLES.'"' ).'}'
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        $jwt = JWT::encode($token, $CONFIG->tokenKey);
        $PARAMETERS->{"PARAMETERS.TOKEN"} = new stdClass();
        $PARAMETERS->{"PARAMETERS.TOKEN"}->value = $jwt;
        $PARAMETERS->{"PARAMETERS.TOKEN"}->original_name = 'token';
        CCSetSession('USERTOKEN', $PARAMETERS->{"PARAMETERS.TOKEN"}->value );
        global $SYSTEM;
        $SYSTEM->{'USERTOKEN'} = CCGetSession('USERTOKEN');
    }
    $result = clsCore::getBindResult($db);
    #var_dump($SYSTEM);
    #var_dump($_SESSION);
    clsCore::returnJson(
        false// data
        , false
        , false
        , false
        , false
        , $result
    );
    return $result;
}
function APILoginUser2($login, $password)
{
    #/api5.php?action=login&login=login&password=password // pasar parametros por POST
    $server = CCGetCurrentUrlPoint();

    global $AESpassPhrase;
    global $sourceName;
    global $token;
    $login = CCGetParam("login");
    $password = CCGetParam("password");
    $sql = cryptoJsAesEncrypt(
        $AESpassPhrase
        , "select id, email, name, role, phone into :id, :userlogin, :nombre, :role, :telefono
          from users
          where email = :login
          and password = md5(:password)");
    CCLogoutUser();

    $bind = new stdClass();
    $bind->login = $login;
    $bind->password = $password;
    $data = [
        'BIND' => json_encode($bind)
        ,'sourcename' => $sourceName
        ,'SQL' => $sql
        ,'token' => $token
    ];

    //echo json_encode($data);
    $result = getPage("http://".$server."/apidosql.php",$data);
    $apiResult = json_decode($result);
    if (json_last_error()) {
        error_manager('ON APILOGIN '.$result.' '.json_last_error_msg(), 13);
    }

    $apiResult->RESULT->token = bin2hex(openssl_random_pseudo_bytes(16));
    if ($apiResult->ERROR->CODE == 0) {
        ## LOGIN COPLETE
        CCSetSession("UserID", $apiResult->RESULT->id);
        CCSetSession("UserLogin", $apiResult->RESULT->userlogin);
        CCSetSession("GroupID", $apiResult->RESULT->id);
        CCSetSession("UserAddr", $_SERVER["REMOTE_ADDR"]);
        CCSetSession("UserToken", $apiResult->RESULT->token);
    }
    $result = json_encode($apiResult,JSON_UNESCAPED_UNICODE);
    if (json_last_error()) {
        error_manager('ON APILOGIN (2) '.$result.' '.json_last_error_msg(), 15);
    }
    //var_dump($apiResult); die;
    return $result;
}


?>
