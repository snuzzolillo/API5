<?php
/*
 * anonynousallowed : indica si permite o no peticiones sin autentificacion
 * autenticationmethod : SESSION or TOKEN or BOTH
 * autenticationsesionvariable : Indica el nombre de la variable en $_SESSION que debe ser verificada para indicar si tiene o no acceso, debe tener un valor o ser true
 * -- En revision autenticationpassword : Indica el metodo para verificar el password por ahora solo MD5 (pronto AES). API5 no interfiere en la forma como se guarda el password
 * autenticatedroles : Es una variable del tipo sumple o ARRAY donde puede indicar un o o mas roles. Por defecto es ANONYMOUS
 * tokenKey : Clave secreta en caso de que la verificacion del token es por key (ver JWT)
 * tokenRequired : -- revisar con respecto a los metodos
 * -- En revision AESpassPhrase : Frase utilizada por el el modulo de encriptacion AES. no implementado aun
 */
/*
<config type="JSON" name="config">
{
"autenticationmethod" : "TOKEN"
,"anonynousallowed" : true
,"autenticationsesionvariable" : "USERNAME"
,"autenticationpassword" : "MD5"
,"autenticatedroles" : "ANONYMOUS"
,"tokenRequired": false
,"tokenKey":"Enfasy"
,"AESpassPhrase":"PASS PHRASE"
}
</config>
*/