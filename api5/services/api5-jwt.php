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

define("RelativePath", "..");
define("PathToCurrentPage", "/services/");
define("FileName", "api5-jwt.php");

require_once(RelativePath . "/Common.php");
require_once(RelativePath . "/services/dosqlClasses.php");
require_once RelativePath . '/services/JWT/Firebase/JWT.php';

global $CONFIG;
$CONFIG = file_get_contents("../textdb/default.config.php");
$CONFIG = json_decode_and_validate(clsCore::getSqlParsed(clsCore::sqlSplitFromStringWithTags($CONFIG,'config'),'config'),'API5');

$key  = isset($CONFIG->tokenKey) ? $CONFIG->tokenKey : "";

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

$token = array(
    "iss" => "DOSQL"
    ,"sub" => "api5"
    ,"aud" => $aud
    ,"iat" => time()
    ,"exp" => time()+ (7 * 24 * 60 * 60)     ,"nbf" => 1357000000
        ,"uid" => $uid
    ,"data" => '{"username":"anonymous", "userroles":[1,2]}'
);

$jwt = JWT::encode($token, $key);

echo "JWT Encode<br><br>\n\n<pre>";
echo $jwt."\n<br>";
echo "<br></pre>CUT AND PAST INTO JAVASCRIPT TOKEN SECTION\n<br>";
echo "THIS IS GENERATE ONLY FOR DEVELOPING PROPOUSE\n<br>";

?>