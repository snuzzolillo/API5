<?php

/*
 * Genera un Token de trabajo a apeticion para actividades especificas de desarrollo
 * parametros:
 * generate=<x>
 * donde x es :
 *  "demo" :
 *  "databaseadmin" :
 *  "developer" :
 *  "dataexchange":
 *
 * Debe ser generado desde el browser y ser copiado y pegado en el java script en sustitucion a una respuesta de LOGIN
 *
 */


/* AREA DE DEPENDENCIAS */
define("RelativePath", "..");
define("PathToCurrentPage", "/services/");
define("FileName", "api5-jwt.php");

require_once(RelativePath . "/Common.php");
require_once(RelativePath . "/services/dosqlClasses.php");
require_once RelativePath . '/services/JWT/Firebase/JWT.php';

global $CONFIG;
$CONFIG = file_get_contents("../textdb/default.config.php");
$CONFIG = json_decode_and_validate(clsCore::getSqlParsed(clsCore::sqlSplitFromStringWithTags($CONFIG,'config'),'config'),'API5');
/* esto debe ser leido del config */
$key  = isset($CONFIG->tokenKey) ? $CONFIG->tokenKey : "";
/*********************************/

$generate = CCGetParam("generate");
switch ($generate) {
    case 'demo' :
        $aud = $generate;
        $uid = $generate;
        break;
    case 'databaseadmin' :
        $aud = $generate;
        $uid = $generate;
        break;
    case 'developer' :
        $aud = $generate;
        $uid = $generate;
        break;
    case 'dataexchange' :
        $aud = $generate;
        $uid = $generate;
        break;
    default :
        die('BAD REQUEST');
}
/*
 * aud values debera validarse segun la descripcion de cada aud
 *  "demo" :
 *  "databaseadmin" :
 *  "developer" :
 *  "dataexchange":
 */


// GENERA UN TOKEN CON UNA SEMANA DE VENCIMIENTO
$token = array(
    "iss" => "DOSQL"
    ,"sub" => "api5"
    ,"aud" => $aud
    ,"iat" => time()
    ,"exp" => time()+ (7 * 24 * 60 * 60) // A week
    ,"nbf" => 1357000000
    ## STANDARS
    ,"uid" => $uid
    ,"data" => '{"username":"anonymous", "userroles":[1,2]}'
);

$jwt = JWT::encode($token, $key);

echo "JWT Encode<br><br>\n\n<pre>";
echo $jwt."\n<br>";
echo "<br></pre>CUT AND PAST INTO JAVASCRIPT TOKEN SECTION\n<br>";
echo "THIS IS GENERATE ONLY FOR DEVELOPING PROPOUSE\n<br>";


?>