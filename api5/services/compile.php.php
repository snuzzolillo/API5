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


define("RelativePath", "..");
define("PathToCurrentPage", "/services/");
define("FileName", "compile.php.php");

require_once(RelativePath . "/Common.php");

$codeOriginal = CCGetParam('origen');
$codeDestino = CCGetParam('destino');
$codeDestino = CCGetParam('destino');

function getDirs($initDir) {
    $dirs = glob($initDir.'/*', GLOB_ONLYDIR);
    foreach($dirs as $i => $value){
                $dirs[$i] = basename($value);
    }
    return $dirs;
}
function getFiles($rootDir, $dirs)
{
    foreach ($dirs as $n => $dir) {
                $filesDir = $rootDir . $dir;

        foreach (glob($filesDir . "/*.php") as $file) {

            echo "$file<br>\n";

                                                
        }
    }
}
$dirs = array_merge([''],[]);
$omitDir = ['.idea','textdb','textsql'];
$rootDir = $codeOriginal;
function getFilesFromDir($rootDir, $destDir) {
    global $omitDir;
    if ($handle = opendir($rootDir)) {

        while (false !== ($entry = readdir($handle))) {

            if ($entry != "." && $entry != ".." && !(in_array($entry,$omitDir))) {
                if (is_dir("$rootDir/$entry")) {
                    getFilesFromDir("$rootDir/$entry", "$destDir/$entry");
                } else {
                    compileFile($entry, $rootDir, $destDir);
                    echo "$rootDir/$entry to $destDir/$entry<br>\n";
                }
            }
        }

        closedir($handle);
    }
}

getFilesFromDir($rootDir, $codeDestino);
die;
getFiles($rootDir, $dirs);
die;

function compileFile($file, $from, $to)
{
    $date = date('m/d/Y');
    $time = date('h:i:s A');
    $version = '0.0.1';
    $header = "
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
 |   Date   : $date                                                 |
 |   Time   : $time                                                |
 |   Version: $version                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/
";
    if (is_file("$from/$file")) {
        echo "Read File $from/$file<br>\n";
        $fileStr = file_get_contents("$from/$file");
        $newStr = '';

        $commentTokens = array(T_COMMENT);

        if (defined('T_DOC_COMMENT'))
            $commentTokens[] = T_DOC_COMMENT;         if (defined('T_ML_COMMENT'))
            $commentTokens[] = T_ML_COMMENT;  
        $tokens = token_get_all($fileStr);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $commentTokens))
                    continue;

                $token = $token[1];
            }

            $newStr .= $token;
        }
        $newStr = str_replace("<?php

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

", "<?php

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

\n" . $header . "\n", $newStr);
                        if (!is_dir($to)) {
            echo "Create Directory $to<br>\n";
            mkdir($to);
        }
        echo "Save file $to/$file<br>\n";
        file_put_contents("$to/$file",$newStr);

    } else {
        echo "$from/$file not found ,br>\n";
    }
}