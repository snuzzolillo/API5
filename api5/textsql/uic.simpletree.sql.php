<?php
/*<SQL QUERY ESTRUCTURAS2>
select a.cod_dato id
  , a.cod_filtro parent_id
  , a.cod_objeto value
  , a.descripcion label
  , a.cod_objeto sec
from cfg_estructura_datos a
where a.cod_estructura = :estructura
and (cod_filtro = :parent_id
  or (coalesce(cod_filtro, '') = coalesce(:parent_id, ''))
)
order by a.cod_objeto # sec;
<END>*/
