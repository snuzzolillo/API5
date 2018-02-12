# API5
Generic SQL to JSON API RESTful for JavaScritpt Ajax Component

<b>INTRODUCCION</b><br>
API5 es una "interface" entre el fron-end y una base de datos SQL (para muchos base de datos es sinónimo de back-end) cuyo resultado es una estructura JSON. Podríamos decir que es una interface "SQLtoJSON" (ver mas adelante). Esta programado en PHP, simplemente porque fue el lenguaje que estuvo a la mano y solo se utiliza como un medio para que JavaScript llegue, lea  y opere con una base de datos relacional o RDBMS, pero en ningún momento el programador del front-end requiere elaborar un código PHP. 

Definiendo PHP como el Middleware, la tendencia es que sea transparente para el propósito final de la API.
(Nota: para esta versión la API ha sido probada con MYSQL y Oracle esperando pronto integrar POSTGRES y MSSQL).

<b>SOBRE MODELO-VISTA-CONTROLADOR</b><br>
Si definiéramos API5 dentro del esquema MVC, diríamos que API5 sustituye el modelo de datos por la "base de datos" entera, es decir, la base de datos para que exista tuvo que ser modelada según las necesidades de las reglas de negocio, por lo que se espera que un buen diseño debe contemplar una grafica de entidad relación o algo parecido a ella. Pues este MODELO Entidad-Relación es lo que se convierte en la "M" dentro una arquitectura MVC y la forma de accederlo es a través de la API. La Base de datos bien diseñada es, en si, el MODELO de datos, no hay por que redefinirla, con solo mirar la ENTIDAD-RELACION el programador puede conceptualizar todo el modelo de datos disponible.

### INSTALACION
Debe tener acceso a modificar archivos del lado del servidor para efecto de configuración.<br>
<br>
Requerimientos del lado del servidor<br>
	• Apache 2.2 o superior (puede ser IIS)<br>
	• PHP 5.4 o superior incluido PHP 7<br>
	• Dependiendo de la base de datos debe tener activo los módulos PHP acorde.<br>
Para mysql -> mysqli<br> 
Para oracle -> oci8<br>
<br>
**Configuración requerida antes de su USO**<br>
API5 requiere de por lo menos un conector de base de datos, estos se definen en los archivos identificados como "source" ubicado en el sub-directorio /textdb. Existe un archivo llamado "default.source.json.php" el cual contiene información de como  conectarse a una base de datos.<br>

Ejemplo para una base de datos MySQL:
```json
{
  "Type"            : "MySQL",
  "DBLib"           : "MySQLi",
  "Database"        : "employees",
  "Host"            : "192.168.1.39",
  "Port"            : "3306",
  "User"            : "demo",
  "Password"        : "onlyfordemo",
  "Encoding"        : ["", "utf8"],
  "Persistent"      : false,
  "DateFormat"      : ["yyyy", "-", "mm", "-", "dd", " ", "HH", ":", "nn", ":", "ss"],
  "BooleanFormat"   : [1, 0, ""],
  "Uppercase"       : false
}
```
Este ejemplo servirá como base para crear otros "source". Para este caso se indica que la base de datos es MySql y DBLib es el adaptador que indica que es atreves del modulo PHP "php_mysqli"

Ejemplo para una base de datos Oracle:
```json
{
"Type"          :"Oracle",
"DBLib"         :"OracleOCI",
"Database"      :"localhost:1521/ORCL",
"Host"          :"",
"Port"          :"",
"User"          :"user",
"Password"      :"password",
"Encoding"      :"UTF8",
"Persistent"    :false,
"DateFormat"    :["yyyy","-","mm","-","dd","","HH",":","nn",":","ss"],
"BooleanFormat" :[1,0,""],
"Uppercase"     :false
}
```
##LISTO!! 
<br>
<br>
<br>
**Consideraciones:**<br>
	  1) Type y DBLib son dependientes y siempre deben tener la pareja combinada. En el pasado Mysql podia utilizar dos librerías distintas, pero en la actualidad solo se utiliza "MySQLi".<br>
	  2) DateFormat se sugiere ampliamente mantenerlo inalterable como en  los ejemplos. Para mysql es su formato natural para oracle es seteada de esa manera en lo interno, para estandarizar el output de los tipo de fecha.<br>
	  3) Encoding UTF8 como apreciarán es algo distinto entre las dos definiciones, se recomienda considerarlos tal como están para ambos tipos.<br>
<br>	
Una vez modificado el archivo con los valores correspondientes a su instalación, esta listo para su uso.
API5 considera mas configuraciones mas avanzadas que están descritas en la documentación.

### USO BASICO

El siguiente es el uso mas primario usando todos los valores por defecto que iremos explicando
<br>
**EJEMPLO 001:**<br>
```javascript
// JAVASCRIPT 
// -------------------------------------------
// TEST WITHOUT JQUERY
var data = new FormData();
data.append('SQL','select * from departments');

var xhr = new XMLHttpRequest();
xhr.open('POST','./services/api5.php',true);
xhr.onload = function(){
	//do something to response
	console.log(JSON.parse(this.responseText));
}
//Required to detect that is a XHR
xhr.setRequestHeader("X-Requested-With","XMLHttpRequest");

xhr.send(data);
// -------------------------------------------

// JAVASCRIPT 
// -------------------------------------------
// TEST WITH JQUERY
jQuery.ajax({
	url :'./services/api5.php'
	,type :'post'
	,data : {
		SQL:'select * from departments'
	}
	,success : function(result){
		// en este caso result ya es un objeto JS
		console.log(result);
	}
	,error : function(error){
		console.log(error);
	}
});
// -------------------------------------------
```
### RESULTADO:
```json
{
	"HEADER":{
		"dept_no":{
			"type":3,
			"type_raw":254,
			"size":4,
			"precision":0,
			"scale":0,
			"is_null":false,
			"primary_key":true,
			"auto_increment":false
		},
		"dept_name":{
			"type":3,
			"type_raw":253,
			"size":40,
			"precision":0,
			"scale":0,
			"is_null":false,
			"primary_key":false,
			"auto_increment":false
		}
	},
	"ERROR":{
		"CODE":"0",
		"MESSAGE":"SUCCESS"
	},
	"INFO":{
		"RECORDS_COUNT":"9",
		"CURRENT_PAGENUMBER":"1",
		"CURRENT_PAGESIZE":"9",
		"DB_TYPE":"MySQL"
	},
	"DATA":[
		{"dept_no":"d009","dept_name":"CustomerService"},
		{"dept_no":"d005","dept_name":"Development"},
		{"dept_no":"d002","dept_name":"Finance"},
		{"dept_no":"d003","dept_name":"HumanResources"},
		{"dept_no":"d001","dept_name":"Marketing"},
		{"dept_no":"d004","dept_name":"Production"},
		{"dept_no":"d006","dept_name":"QualityManagement"},
		{"dept_no":"d008","dept_name":"Research"},
		{"dept_no":"d007","dept_name":"Sales"}
	]
}
```
