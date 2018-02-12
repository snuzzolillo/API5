<?php
/*
<sql type="QUERY" name="ESTRUCTURAS2">
select a.cod_dato id
  , coalesce(a.cod_filtro,'0') parent_id
  , a.cod_objeto value
  , a.descripcion label
  , a.cod_objeto sec
from cfg_estructura_datos a
where a.cod_estructura = :estructura
and a.cod_dato like  '580709%'
order by a.cod_filtro, a.cod_objeto
# a.cod_filtro = parent_id
# a.cod_objeto = sec;
</sql>
*/
