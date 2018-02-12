<?php
// Estrctura ejemplo de los tags a ser utilizado por el sistema de creacion de modelos
/*
 * ESTE DEBE SER IGNORADO
 */

/*<sQl name="ES1" LANG="SQL">
select a.cod_dato id
  , coalesce(a.cod_filtro,'0') parent_id
  , a.cod_objeto value
  , a.descripcion label
  , a.cod_objeto sec
from cfg_estructura_datos a
where a.cod_estructura = :estructura
and a.cod_dato like  '58%'
order by a.cod_filtro, a.cod_objeto
# a.cod_filtro = parent_id
# a.cod_objeto = sec;
</sqL>*/

/*<sql name="es2" lang="plsql" type="any">
select a.cod_dato id
  , coalesce(a.cod_filtro,'0') parent_id
  , a.cod_objeto value
  , a.descripcion label
  , a.cod_objeto sec
from cfg_estructura_datos a
where a.cod_estructura = :estructura
and a.cod_dato like  '58%'
order by a.cod_filtro, a.cod_objeto
# a.cod_filtro = parent_id
# a.cod_objeto = sec;
</sql>*/
