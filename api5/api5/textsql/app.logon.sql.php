<?php
// Autentificando con un usuario LOCAL - Ejemplo MYSQL
/*
<sql type="QUERY" name="LOGIN">
SELECT user_id userid
, username username
, case when superuser = 1 then 'ADMIN' else 'USER' end roles
into :userid
  ,:username
  ,:roles
FROM users
where email = :email
and   password = md5(:password)
</sql>
*/

/*
<sql type="QUERY" name="ROLES">
select role
from user_roles
where username = :username;
</sql>
*/
