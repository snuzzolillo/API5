<?php
/*
<sql type="JSON" name="ANYNAME">
{"Name": "ZONAS"
		 ,"RelationType": "0"
		 ,"Query" : "SELECT id, cod_afiliacion, cod_actividad, zona, cod_centro
                     FROM ele_centro_zonas
                     where cod_actividad = 1
                     "
		 ,"DeleteRecord": "1"
		 ,"Detail": [{
		        "Name": "CENTROS"
                ,"RelationType": "0"
                ,"Query" : "SELECT id, cod_pais, cod_estado, cod_municipio, cod_parroquia, cod_centro, nom_centro, dir_centro, cod_unico, status, fecha, latitud, longitud
                            FROM geo_centros
                            where cod_centro like :cod_centro
                            "
                ,"DeleteRecord": "1"
                ,"Detail": []
                ,"AutoQuery": "true"
                ,"JoinCondition": "CENTROS.cod_centro = zonas.cod_centro"
         }]
		 ,"AutoQuery": "true"
}
</sql>
*/


