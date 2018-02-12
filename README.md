# API5
Generic SQL to JSON API RESTful for JavaScritpt Ajax Component

INTRODUCCION
API5 es una "interface" entre el fron-end y una base de datos SQL (para muchos base de datos es sinónimo de back-end) cuyo resultado es una estructura JSON. Podríamos decir que es una interface "SQLtoJSON" (ver mas adelante). Esta programado en PHP, simplemente porque fue el lenguaje que estuvo a la mano y solo se utiliza como un medio para que JavaScript llegue, lea  y opere con una base de datos relacional o RDBMS, pero en ningún momento el programador del front-end requiere elaborar un código PHP. 

Definiendo PHP con el Middleware, la tendencia es que sea transparente para el propósito final de la API.
(Nota: para esta versión la API ha sido probada con MYSQL y Oracle esperando pronto integrar POSTGRES y MSSQL).

SOBRE MODELO-VISTA-CONTROLADOR
Si definiéramos API5 dentro del esquema MVC, diríamos que API5 sustituye el modelo de datos por la "base de datos" entera, es decir, la base de datos para que exista tuvo que ser modelada según las necesidades de las reglas de negocio, por lo que se espera que un buen diseño debe contemplar una grafica de entidad relación o algo parecido a ella. Pues este MODELO Entidad-Relación es lo que se convierte en la "M" dentro una arquitectura MVC y la forma de accederlo es a través de la API. La Base de datos bien diseñada es, en si, el MODELO de datos, no hay por que redefinirla, con solo mirar la ENTIDAD-RELACION el programador puede conceptualizar todo el modelo de datos disponible.

#INSTALACION
