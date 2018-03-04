<?php
/*
 * anonynousallowed : indica si permite o no peticiones sin autentificacion
 * authenticationmethod : SESSION or TOKEN or BOTH
 * authenticationsesionvariable : Indica el nombre de la variable en $_SESSION que debe ser verificada para indicar si tiene o no acceso, debe tener un valor o ser true
 * -- En revision authenticationpassword : Indica el metodo para verificar el password por ahora solo MD5 (pronto AES). API5 no interfiere en la forma como se guarda el password
 * authenticatedroles : Es una variable del tipo sumple o ARRAY donde puede indicar un o o mas roles. Por defecto es ANONYMOUS
 * tokenKey : Clave secreta en caso de que la verificacion del token es por key (ver JWT)
 * tokenRequired : -- revisar con respecto a los metodos
 * -- En revision AESpassPhrase : Frase utilizada por el el modulo de encriptacion AES. no implementado aun
 */
/*
<config type="JSON" name="config">
{
"authenticationmethod" : "TOKEN"
,"anonymousallowed" : true
,"authenticationsessionvariable" : "USERID"
,"authenticationpassword" : "MD5"
,"authenticatedroles" : "ANONYMOUS"
,"tokenRequired": true
,"tokenKey":"Enfasy"
,"AESpassPhrase":"PASS PHRASE"
}
</config>
*/