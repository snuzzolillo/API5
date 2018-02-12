select * from (
select concat(nac,'-',cedula) cedula
, concat(trim(primer_nombre),' ',trim(primer_apellido)) nombre
 from uic_personas p
 order by p.cedula
 limit 10
) a