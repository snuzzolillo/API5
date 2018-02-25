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

 error_reporting(E_ALL);?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>CryptoJS AES and PHP</title>
<script type="text/javascript" src="aes.js"></script>
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="../aes-json-format.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $(document.d).find(".val, .pass").on("keyup init", function(){
        document.d.json.value = CryptoJS.AES.encrypt(JSON.stringify(document.d.val.value), document.d.pass.value, {format: CryptoJSAesJson}).toString();
    }).trigger("init");
});
</script>
</head>
<body>
<h1>CryptoJS AES and PHP</h1>

<h2>Example to encrypt with CryptoJS on client side and decrypt on PHP side</h2>
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

if(isset($_POST["decrypt"])){
    include("../cryptojs-aes.php");
    ?>
    Json value received: <input type="text" value="<?php

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

 echo htmlentities($_POST["json"])?>" size="90" disabled="disabled"/><br/>
    Passphrase: <input type="text" value="<?php

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

 echo $_POST["pass"]?>" size="90" disabled="disabled"/><br/>
    Decrypted value: <input type="text" value="<?php

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

 echo cryptoJsAesDecrypt($_POST["pass"], $_POST["json"])?>" size="45" disabled="disabled"/><br/>
    <hr/>
    <br/><br/>
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

}
?>

<form name="d" method="post" action="">
    Value to encrypt: <input type="text" name="val" value="My string - Could also be an JS array/object" class="val" size="45"/><br/>
    Passphrase: <input type="text" name="pass" class="pass" value="my secret passphrase" size="45"/><br/>
    CryptoJS encrypted json output: <input type="text" name="json" class="json" size="90" onclick="this.select()"/>
    <input type="submit" name="decrypt" value="Send to server and decrypt"/>
</form>

</body>
</html>
