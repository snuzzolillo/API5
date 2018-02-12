<?php
/**
 * Created by PhpStorm.
 * User: SANTO
 * Date: 29/10/2015
 * Time: 11:47 AM
 * Para trabajar con el datasource "default", es responsabilidad del login de la aplicacion crear la varaible de session:
 *      TO CONNECT TO A default DATABASE debe setearse la variable de session
 *      $_SESSION["CONNECTED"]["default"] = new stdClass();
 *
 */
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

        global $BIND;
        $bind = is_object($BIND) ? $BIND : new stdClass();
        $bind->{'___lastkey'} = ''; // Necesario en caso de oracle para simular el LAST_INSERTED_ID

        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                 // El conector
            , $SQL              // el SQL original tomado del sqlparsed
            , $bind             // Lista de parametros
        );


        $DB_TYPE = $db->Type;

        if ($DB_TYPE == "Oracle") {

            // TODO : Si es un INSERT detectar la tabla "$table_name" y ubicar el LAST ROW_ID y retornar como LAST_INSERTED_ID
            /*
            $SQL = str_replace('{tablename}',$table_name,str_replace('{INSERT_STATEMENT}', $SQL,
                "
                    lock table  {tablename} in exclusive mode;
                    {INSERT_STATEMENT};
                    select max(rowid)||'' row_id into :PARAMETERS____lastkey from {tablename};

            "));
            // $lastkey = ??? estara entre las variables BINDED
            */

            $db->query("BEGIN ' . $SQL . '; END;");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            #
            # GET BINDS
            #
            #foreach ($arr as $toBind) {
            #    $t = trim(str_replace(',', '', $toBind));
            #    $phpCode .= '$' . $t . ' = $db->Record[\'' . $t . '\'] ;' . "\n";
            #}
            $lastkey = "?"; // Extraer de la tabla de BINDED luego de get BINDED
        } else if ($DB_TYPE == "MySQL"){

            $db->query("'.$SQL.'");
            if ($db->Errors->ToString()) {
                error_manager($db->Errors->ToString(), -20101);
            }

            #$phpCode .= ' if ($db->Link_ID->affected_rows == 0 and $db->Link_ID->warning_count > 0 and  $db->Link_ID->info === null) {
            # $msg["code"]    = 1329;
            # $msg["message"] = "No data - zero rows fetched, selected, or processed";
            # $db->halt($msg);

            #
            # GET BIND
            #
            #$phpCode .= '$result = CCDLookUp("'.$unique_name.'()", "", "", $db);' . "\n";
            #$phpCode .= '$result = CCDLookUp("@____resultado", "", "", $db);' . "\n";

            $lastkey = CCDLookUp("LAST_INSERT_ID()", "", "", $db);

        }

        #
        # Debe retorna INFO
        # Debe retornar ERROR
        # Debe retornae BINDED como RESULT
        #
        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        clsCore::returnJson(
            false
            , '{"CODE":"0", "MESSAGE" : "SUCCESS"}'
            , "INFO"
            , false
            , "$result" // binded o resultado de get BINDS
        );
    }

}
// End Class clsDMLResult

