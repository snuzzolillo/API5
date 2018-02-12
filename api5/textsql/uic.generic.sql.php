<?php
/*
<sql type="QUERY" name="ESTRUCTURAS2">
SELECT a.cod_nodo_parent parent_id,
       a.cod_nodo id,
       a.sec,
       a.cod_nodo value,
       a.nivel_nombre label,
       a.otros_datosv reference
FROM (cfg_estructura_niveles  a
      INNER JOIN cfg_estructuras b
         ON (b.cod_estructura =
                a.cod_estructura))
     INNER JOIN cfg_estructura_tipos c
        ON (c.cod_tipo = a.cod_nodo)
WHERE a.cod_estructura = :estructura
order by a.nivel, a.sec, a.cod_nodo
</sql>*/

/*
<sql type="QUERY" name="ESTRUCTURAS">
SELECT cfg_estructuras.cod_estructura,
       cfg_estructuras.descripcion,
       cfg_estructura_niveles.cod_nodo_parent parent_id,
       cfg_estructura_niveles.cod_nodo id,
       cfg_estructura_tipos.nom_tipo,
       cfg_estructura_niveles.sec,
       cfg_estructura_niveles.nivel_nombre,
       cfg_estructura_niveles.otros_datosv
FROM (uicipe_proyecto.cfg_estructura_niveles    cfg_estructura_niveles
      INNER JOIN uicipe_proyecto.cfg_estructuras cfg_estructuras
         ON (cfg_estructura_niveles.cod_estructura =
                cfg_estructuras.cod_estructura))
     INNER JOIN uicipe_proyecto.cfg_estructura_tipos cfg_estructura_tipos
        ON (cfg_estructura_tipos.cod_tipo = cfg_estructura_niveles.cod_nodo)
WHERE cfg_estructuras.cod_estructura = :estructura
UNION
SELECT cfg_estructuras.cod_estructura,
       cfg_estructuras.descripcion,
       0 cod_nodo_parent,
       cfg_estructuras.cod_estructura cod_nodo,
       cfg_estructuras.descripcion nom_tipo,
       0 sec,
       cfg_estructuras.descripcion,
       null otros_datosv
FROM uicipe_proyecto.cfg_estructuras cfg_estructuras
where cfg_estructuras.cod_estructura = :estructura
ORDER BY cod_estructura ASC,
         parent_id ASC,
         sec ASC
</sql>
*/
