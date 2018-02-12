<?php
/**
 * Created by PhpStorm.
 * User: seven-11
 * Date: 07/01/2018
 * Time: 12:45 PM
 */
/*
 * Estructura de arbol creada a partir de una definicion que indica una estructura diferenciada por un codigo y un nombre
 * , y nieles jerarquicos, definidos por tipos de nodo que a se bez pueden tener sub nodos.
 *  la diferencia con respecto a una estructura simle, es que la lista subordinada (o hujos) de cada nodo - sub nodo
 *  corresponde a la solucuin de un select
 *  la estructura padre- hijo corresponde a los nobres cod_nodo_padre y con_nodo (el hijo)
 *
 */
#echo 'INCLUDED ../textsql/uic.estructuras.sql.php'.'<br>';

//
// Este query resuelve la lista de tipos de nodos dependientes,
// El primer registro del resultado es el NODO ROOT y referencia a la ESTRCTRA como TAL
// los subsiguientes registros son dependientes, ordenados por dependencia directa, es decir
// el segundo nodo es hijo del PRIMER nodo... pero un padre puede tener varios tipos de hijos.
// Esta estructura muestra el NODO tipo como parte del arbol resultante
//
Class clsHierarchiesResult {

//Variables

    public $Query;
    public $Errors;
    public $DataSource;
//End Variables

//Class_Initialize Event
    function __construct($SQL, $hierarchiesType = 'SIMPLE'){

        // Los resultados Jerarquicos pueden ser de varios tipos
        // 1)   Por tipos de Nodos, que dependeran de una definicion de los tipos de nodos el nivel de cada nodo
        //      y como recuperar los hijos de dicho tipo de NODO. La estructura es FNITA es decir
        //      los niveles maximos son conocido y la relacion entre los tipos de nodos esta prestab;ecida.
        //      La relacion PADRE e HIJO es a traves de un ID unico.
        //
        // 2)   Jerarquia LINEAL, es decir un resultado simple donde debe indicar un ID y PARENT_ID. En este caso las relaciones o niveles
        //      puede ser infinitas, tantas comos padres que tengan hijos dentro de un resultado tabular con los dos campos ID y PARENT_ID.
        //      La estructura jerarquica se construye de un unico QUERY que disponga de todos los resutados posibles
        //
        // 3)   Maestro detalle es igual o parecido a la 1era pero la definicion es Dinamica.
        //      Se espera un JSON que defina las relaciones.
        //      Dichas relaciones pueden ser mas complejas que un simple ID, es decir puede ser cualquier columna
        //      o una combinacion de columnas.
        //
        // Para el caso 1 y 2 el resultado actual es compatible con DynaTree y FancyTree. Se espera poder definir otras salidas
        // Para el caso 3 el resultado es un array de objetos con las columnas resultantes del QUERY y con una columna adicional
        // llamada details (a diferencia de children). El primer nivel del ARRAY son los MASTER.
        // el DETAIL es un array de objetos con los resultados que coincidan segun lo especificado en el RELATION que a
        // su vez pudiera o no tener details
        //
        #### elimin global $FileName;
        global $CCSLocales;
        global $DefaultDateFormat;

        #echo"clsHierarchiesResult \n$SQL\n$hierarchiesType\n";

        $this->Errors = new clsErrors();
        $this->DataSource = new clsDBdefault($this);
        // Empezamos resolviento elprimer caso que es una estructura de arbol con tipos de nodos
        //$hierarchiesType = 'BY NODE';
        //$hierarchiesType = 'MASTER-DETAILS';
        //#$hierarchiesType = 'SIMPLE';
        #echo "\nJerarquias=\n";
        switch(strtoupper($hierarchiesType)) {
            case 'BY NODE'          : $this->hierarchiesRequestByNodeType($SQL); break;
            case 'SIMPLE'           : $this->hierarchiesRequestSimple($SQL); break;
            case 'MASTER-DETAILS'   : $this->hierarchiesRequestMD($SQL); break;
            default : break;
        }

        // TODO REVISAR ESTO QUE HA CAMBIADO
        // Resultado esperado del query
        // ["label"]=> string(11) "DIVILGACION"
        // ["parent_id"]=> string(4) "_103"
        // ["id"]=> string(4) "_104"
        // ["sec"]=> string(1) "1"
        // ["value"]=> string(7) "MODULOS"
        // ["otros_datosv"]=> string(189) "{"TIPO":"BY SELECT" ,"SELECT":"select  distinct null parent,  aplica from afi_cargos_aplica", "COD_DATO" : "aplica"  ,"COD_FILTRO":"parent"  ,"DESCRIPCION":"aplica"  ,"COD_OBJETO":"aplica"}"
        //
        // DEBEN ESTAR ORDENADOS POR "parent_id , id" o "parent_id, sec" donde sec = secuencia dentro del arbol
        // --------------------------------------------------------------------------
    }
    function clsHierarchiesResult($SQL, $hierarchiesType = 'SIMPLE'){
        self::__construct($SQL, $hierarchiesType);
    }
//End Class_Initialize Event

    function getLevelInfo($levels, $parent_id) {
        // Retorna un array con los elementos cuyo "parent_id" = "$parent_id"
        $level = array();
        foreach($levels as $i => $v) {
            #var_dump($v);
            #var_dump($parent_id);
            if ($v['parent_id'] === $parent_id) {
                $level[] = $v;
            }
        }
        return $level;
    }

    // TODO lo que sigue debe ser una rutina Recursiva donde el parametros debe ser el conector de la BD
    function getChildren(
        $reference              // Define como resolver la lista de HIJOS
        , $parent_id = null     // Define el valor (filtro) del parent_id dentro de la lista
        , $levels               // contiene todos los niveles (tipos de nodos) de la estructura de nodos
        , $nodeType = ''        // El codigo del tipo de odo actual
    ) {
        // ----------------------------------------------------
        // $nodeType:   tiene el tipo de  nodo al que pertenecen estos hijos
        //              Cada hijo de este odo debe tener como hijo, los subnodos y estos los hijos de la definicion de dichos subnodos
        // $levels:     Es un array que tiene todos los niveles que conforman la estructura segun su definicion
        //              Los tipos de nodos depentiente del $nodeType actual son todos quellos donde  en $level tiene
        //              $level['parent_id'] = $nodeType
        // ----------------------------------------------------

        // resuelve el reference, estructura json... json_encode($reference)
        $reference = json_decode(clsCore::normalizeJSONObjectAttrName($reference));
        // reference contiene informacion de como resolver los hijos asociados al padre del tipo de nodo actual
        // por ahora, la unica manera de recupera los hijos es a traves de un QUERY
        //
        if (json_last_error()) {
            error_manager('getChildren in hierarchiesRequest bad json '.json_last_error_msg(), -20101) ;
        }
        $branch = array();
        #echo "Get children reference PARENT ID = $parent_id<br>\n";
        #echo "TIPO=".$reference->tipo."<br>\n";
        #echo "SELECT=".$reference->select."<br>\n";
        #var_dump($reference);
        if (!is_object($reference)) {
            return $branch;
        }
        switch ($reference->tipo) {
            case "BY SELECT" :
                // prepare select. A traves del objeto bind, se envian los parametros al siguiente query
                $bind = new stdClass();
                $bind->parent_id = $parent_id;

                $db = new clsDBdefault();
                $SQL = clsCore::sqlSetParameters($db, $reference->select, $bind);
                $db->query($SQL);
                if ($db->Errors->ToString()) {
                    error_manager($db->Errors->ToString(), -20101);
                }

                //
                // nota: simplifyNextRecord sustituye a $db->next_record
                // el formato original es un array con referencias numericas = "posicion" y por referencia string = "nombre de la columna"
                // simplifyNextRecord retorna un registro (array) sin referencias numericas
                //

                // Cada Registro es un hijo de este nivel
                while (clsCore::simplifyNextRecord($db)) {
                    #echo "WHILE DATA $parent_id" . "<br>\n";
                    $record = $db->Record;
                    $element = new stdClass();

                    $element->{"title"} = $record['label'];
                    $element->{"value"} = $record['value'];

                    // elemetos fijos preparados para DynaTree o FancyTree ----------------
                    //$element->{"key"} = $record['id']; // Si no se coloca dynatree o fancytree crearan un KEY unico
                    $element->{"isFolder"} = false;
                    $element->{"isNode"} = true;
                    $element->{"icon"} = false;
                    $element->{"expand"} = false;
                    // --------------------------------------------------------------------
                    // TODO: leer del query resultantes (en $record) otras columnas distintas a value, label, id, parent_id y adicionarlas en la estructura de $element
                    // --------------------------------------------------------------------

                    $element->{'children'} = $this->buildNode($levels, $record['id'], $nodeType);
                    $branch[] = $element;
                }
                break;
            default :
                break;
        }

        return $branch;

    }

    function buildNode($levels, $parent_id, $nodetype) {
        //
        // $parent_id : es el codigo o ID del "dato padre"
        //              El "dato padre" corresponde al "nodo de datos" que es hijo de un TIPO de nodo
        //
        $branch = array();
        $level = $this->getLevelInfo($levels, $nodetype); // Retorna un array con los tipos de nodos asociados al nivel del nodo actual
        #var_dump($level);
        #die;
        foreach ($level as $i => $typeNode) {

            $element = new stdClass();
            $element->{"title"}          = $typeNode['label'];
            $element->{"value"}          = $typeNode['value'];

            // elemetos fijos preparados para DynaTree o FancyTree ----------------
            //$element->{"key"}            = $typeNode['id'];
            $element->{"isFolder"}       = true;
            $element->{"isNode"}         = true;
            $element->{"icon"}           = false;
            $element->{"expand"}         = true;
            // --------------------------------------------------------------------

            $element->{'children'} = $this->getChildren(
                $typeNode['reference']  // DBE SER Una Estructura strignify en JSON que dice como recuperar los hijos
                , $parent_id            // el parent este ID es el padre de la estructura de datos y no de la estructura de niveles
                , $levels               // para determinar si tiene level asociado
                , $typeNode['id']       // Tipo de Nodo, necesario para verficar si el tipo de nodo tiene sub nodos
            );
            $branch[] = $element;
            // return $element->children; // Activar esta linea si se quiere omitir el NOTO tipo
        }

        return $branch;
    }

    function hierarchiesRequestByNodeType($SQL = '')
    {
        //
        // TODO $SQL puede ser una referencia a un archivo o un SQL como tal, estandarizar para saber cuando es uno y cuando es otro
        //

        // Esta opcion es una arbol con TIPOS de nodos, los tipos de NODOS
        // deben venir de una deficion, que es una estructura de arbol en si
        // En esta implementacion las estrucuras de los tipos de NODOS estan definidos
        // en un SELECT de BD en el archivo uic.estructuras.sql
        // El modelo de datos o entidad relacion define un nombre de estructura y su secuencia por tipo de NODO
        // ademas de su dependencia jerarquica, ademas define a traves de un objeto JSON
        // como resolver los hijos de ese tipo de NODO. Se espera que se envie por parametro el codigo de la estructura
        // que se desea resplver de manera jerarquica.
        // Por ahorala forma de resolver los hijos es "BY SELECT", es decir un QUERY de base de datos
        //

        // --------------------------------------------------------------------------
        // TRABAJANDO CON TL TIPO DE ESTRUCTURAS NODO Y TIPO_NODO
        // PARA QUE FUNCIONE DBBE EXISTIR UNA DEFINICION De LA ESTRUCTURA
        // BASADA EN UN ARBOL POR TIPO DE NODO
        // EL TIPO DE NODO DEBE INDICAR DONDE TOMAR LOS DATOS
        // POR AHORA SOLO SE TOMA DE UN QUERY
        // --------------------------------------------------------------------------

        // TODO: Actualmente requiere de una definicion en una estructura guardada en la BD permitir que laestructura de tipos de nodo sea un JSON en el archivo SQL
        //      Hacer que dicha estructura sea un JSON obteniendo el mismo resultado.
        //      Ver la solucion MASTER-DETAIL
        //
        // PRUEBA USO DE ARHIVO CON CONTENIDO SQL en COMENTARIOS
        //

        //$fileToParse = "../textsql/uic.generic.sql.php"; // Debe ser uno de los parametros
        # -------------
        # nota: api5 fue creado para resolver solicitudes de datos desde una BD SQL y ser recibida por el browser en formato JSON.
        # El Query o SQL puede venir en formato de TEXTO directo, una referencia DINAMICA, o una referencia por archivo.
        ######
        # Referencia DINAMICA
        #   propuesto para resolver contenido de archivos con SQL embedido y referenciado en $_SESSION
        #   La referencia es directa y dinamica y dicha referencia esta precedida por ":"
        ######
        # Referencia por archivo.
        #   Propuesto para resolver codigo SQL "no embedido" y guardado en un archivo con extension ".sql.php" para asi
        #   proteger su lectura externa y hacerlo invisible del lado de afuera del servidor.
        #   El codigo SQL esta encerrado en comentario con TAGS especificos para la extraccion del texto del query.
        #   un archivo puede contener uno o mas queries y su estructura permite diferenciar uno de otro.
        #   a este proceso se le denomino "sqlParsing", que tiende a confunir con el termino de parsing de un RDBMS.
        #

        #
        # Luego de esta funcion, $sqlParsed contiene una estructura array con todos los queries encontrado en el archivo.
        # al descomponer los tags obtiene la estructura en la forma
        # $sqlParsed[nombre_del_query].
        #

        if (substr($SQL,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL,1));
            # getSqlParsed($var, $name) retorna desde un array resultante de  sqlSplitFromFile(), el que coincida con el nombre
            # si no se especifica el nombre, retorna el primero
            $SQL = clsCore::getSqlParsed($sqlParsed);
        }

        // EL Bind aqui debe ser sustituido por la caprura del parametro BIND y debe tener
        // el parametro "estructura" el cual indica cual estructura debe devolver
        global $BIND;
        $bind = $BIND;

        $SQL = clsCore::sqlSetParameters(
            $this->DataSource   // El conector
            , $SQL              //
            , $bind             // Lista de parametros
        );

        $this->DataSource->query($SQL);

        if ($this->DataSource->Errors->ToString()) {
            error_manager($this->DataSource->Errors->ToString(), -20101) ;
        }
        //
        // CREA UN ARRAY CON LOS NIVELES DE LA ESTRUCTURA para no tener que reelrlos
        // EN LA ESTRCTURA EL TIPO ES "BY NODE"
        //
        $levels = array();
        while (clsCore::simplifyNextRecord($this->DataSource)) {
            $levels[] = $this->DataSource->Record;
        }
        //
        // EN este punto se tiene los hijos de la estructura en forma de tipo de NODOS
        // Cada tipo de nodo debe tener en "reference" una estructura JSON que indica como resolver
        // los dependientes o hijos de ese tipo de NODO
        //

        // Segun el concepto de configuracion las estructuras que tienen nivel, el tipo de node el primer nivel
        // es el mismo codigo que el codigo de la estructura. Poe hora se mantiene asi
        //
        $records = $this->buildNode(
            $levels               // Array con id y parent_id que definen el arbol de tipos de Nodos
            , $bind->estructura   // El primer parent. Recuerda que BIND es un objeto con parametros que vienen del borwser
            , $bind->estructura   // Codigo de la estructura, el cual esta en el primer nivel.
        );

        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function hierarchiesRequestSimple($SQL = '')
    {
        // --------------------------------------------------------------------------

        // --------------------------------------------------------------------------
        // Esta opcion es una arbol construido a partir de una lista (evidentemente resultado del SQL
        // con un par de columnas llamadas "id" y "parent_id", las cueles deben ser colocadas como alias
        // de las columnas que confrman la ralacion padre-hijo.
        //
        // La relacion se establece por ID unico, es decir un "unico valor". Si el id es compuesto, debe ser resuelto en el SQL
        // y tener como alias ID, igual debe hacerse con el PARENT_ID
        //
        // los datos minimos del SQL deben ser
        //      id              : id del elemento
        //      parent_id       : id del padre
        //      sec             : secuencia del orden dentro del padre
        //      value           : valor real asociado al nodo
        //      label           : etiqueta, lo que normalmente se muestra en un TREEVIEW
        //
        // LUEGO DE VARIOS METODOS,
        //      LLENA UNA TABLA Y APLICA LA RUTINA DE ARRAY? PROBEMOS
        //      Este metodo resulto ser mas simple y rapido.
        //
        // PARA ESTE CASO EL SQL se ejecuta una sola vez por lo que las exigencias son
        // ordenado por parent_id y por sec
        //
        if (substr($SQL,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL,1));
            //$SQL  = file_get_contents(RelativePath . "/textsql/".substr($SQL,1,strlen($SQL)-1).".sql.php");
            $SQL = clsCore::getSqlParsed($sqlParsed);
        }
        #$fileToParse = "../textsql/uic.simpletree2.sql.php"; // Debe ser uno de los parametros
        #$sqlParsed = clsCore::sqlSplitFromFile($fileToParse);

        // EL Bind aqui debe ser sustituido por la caprura del parametro BIND y debe tener
        // el parametro "estructura" el cual indica cual estructura debe devolver
        // $bind = new stdClass();
        //$bind->estructura = '_38'; // Debe venor en BIND verdadeso de los parametros desde el browser
        global $BIND;
        $bind = $BIND;

        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                 // El conector
            , $SQL            // el SQL original tomado del sqlparsed
            , $bind             // Lista de parametros
        );

        $db->query($SQL);
        if ($db->Errors->ToString()) {
            error_manager($db->Errors->ToString(), -20101);
        }

        // TRADITIONAL METHOD BETTER THAN fetch_all
        #$result = mysqli_fetch_all($db->Query_ID, MYSQLI_ASSOC);
        #var_dump($result);
        #die;

        $records = array();
        $parent_id = false; // El parent_id ROOT es el "parent_id" de primer elemento del resultado del query

        while (clsCore::simplifyNextRecord($db)) {
            if ($parent_id===false) $parent_id = $db->Record['parent_id']; // El parent_id del primer registro se convierte en el parent_id de ROOT
            $records[$db->Record['id']] = $db->Record;
        }

        function buildElement($record) {
            $element = new stdClass();

            $element->{"title"} = $record['label'];
            $element->{"value"} = $record['value'];

            // elemetos fijos preparados para DynaTree o FancyTree ----------------
            //$element->{"key"} = $record['id']; // Si no se coloca dynatree o fancytree crearan un KEY unico
            $element->{"isFolder"} = false;
            $element->{"isNode"} = true;
            $element->{"icon"} = false;
            $element->{"expand"} = false;
            return $element;
        }

        function buildTreeSimple(array &$elements, $parentId = '') {

            $branch = array();

            foreach ($elements as &$element) {

                if ($element['parent_id'] == $parentId) {
                    $node = buildElement($element);
                    $children = buildTreeSimple($elements, $element['id']);
                    #if ($children) {
                    $node->{'children'} = $children;
                    #}
                    $branch[] = $node;
                    unset($element);
                }
            }
            return $branch;
        }

        $result = buildTreeSimple($records, $parent_id);

        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        clsCore::returnJson($result, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function hierarchiesRequestMD($JSON = '')
    {
        // echo "hierarchiesRequestMD start <br>\n";
        //
        // PRUEBA USO DE ARHIVO CON CONTENIDO BASADO EN RELATION
        //

        //SELECT id, cod_pais, cod_estado, cod_municipio, cod_parroquia, cod_centro, nom_centro, dir_centro, cod_unico, status, fecha, latitud, longitud
        //                    FROM geo_centros

        if (substr($JSON,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($JSON,1));
            #echo "\nJSON=\n";
            //$SQL  = file_get_contents(RelativePath . "/textsql/".substr($SQL,1,strlen($SQL)-1).".sql.php");
            $JSON = clsCore::getSqlParsed($sqlParsed);
        }

        /*
         $Relation = '{"Name": "ZONAS"
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
                            "
                ,"DeleteRecord": "1"
                ,"Detail": []
                ,"AutoQuery": "true"
                ,"JoinCondition": "CENTROS.cod_centro = zonas.cod_centro"
            }]
		    ,"AutoQuery": "true"}';
        */
/*
        #echo addcslashes($Relation,"\0..\37"); die;
        $Relation = '{"Name": "CLI_DOCPEN","RelationType": "0"
        ,"DeleteRecord": "1","Detail": "DOCPEN","AutoQuery": "true"
        ,"JoinCondition": "cli.codigo_empr = docpen.codigo_empr
            and cli.codigo_sucursal = docpen.sucursal
            and cli.codigo_cliente    = docpen.auxiliar"}'
        ;
*/
        //echo addcslashes($Relation,"\0..\37")."\n";
        //die;

        //$string = str_replace("\n", "", $Relation);
        //$string = str_replace("\r", "", $string);
        //echo $string;
        //$x =  json_decode($Relation);
        $Relation = json_decode_and_validate($JSON,"hierarchiesRequestMD in hierarchiesRequest bad json");
        $Relation = clsCore::normalizeJSONObjectAttrName($Relation);
        $Relation = json_decode_and_validate($Relation,"hierarchiesRequestMD in hierarchiesRequest bad json");

        //var_dump($Relation); die;
        // El atributo Name es utilizado como alias, Cuando se refencia en el JoinCondition, este es comparado exactamente igual con mayusculas y minusculas
        // De no estar igual no detectara en la relacion al campo del join

        // $Relation tiene los attributos del MASTER y si tiene un Detail lo resuelve
        // 1) EJECTA EL QUERY ASOCIADO

        #$bind = new stdClass();
        #$bind->estructura = '_38'; // Debe venor en BIND verdadeso de los parametros desde el browser
        global $BIND;
        $bind = $BIND;

        $bd = new clsDBdefault();
        $SQL = $Relation->query;

        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                 // El conector
            , $SQL            // el SQL original tomado del sqlparsed
            , $bind             // Lista de parametros
        );


        $db->query($SQL);
        if ($db->Errors->ToString()) {
            error_manager($db->Errors->ToString(), -20101);
        }

        function buildMaster($record){
            //$master = new stdClass();
            return json_decode(json_encode($record));
            //echo "\n";
        }

        function buildDetail($masterAlias, $master, $details, $bind){
            global $PARAMETERS;
            foreach($details as $detail) {
                #echo "IN DETAIL SEARCH FOR " . "/" . $masterAlias . "\." . "\s*([a-zA-Z0-9_]+)/ise" . "<BR>\n";
                $SQL = 'SELECT * FROM ('.$detail->query.' ) '.$detail->name . ' WHERE '.$detail->joincondition;
                preg_match_all("/" . $masterAlias . "\." . "\s*([a-zA-Z0-9_]+)/ise", $SQL, $arr);

                foreach ($arr[0] as $i => $name) {
                    #echo "$i $name<BR>\n";
                    $bind->{$name} = $master->{$arr[1][$i]};
                    #$detail->JoinCondition = str_replace($name, ':' . $name, $detail->JoinCondition);
                    $SQL = str_replace($name, ':' . $name, $SQL);
                }
                // Anade el JoinCondition al Where
                // $SQL = 'SELECT * FROM ('.$detail->Query.' ) '.$detail->Name . ' WHERE '.$detail->JoinCondition;

                clsCore::sqlBindVariables($SQL, $bind);

                $db = new clsDBdefault();
                $SQL = clsCore::sqlSetParameters(
                    $db                 // El conector
                    , $SQL              // el SQL original tomado de $Relation
                    , $bind             // Lista de parametros
                );

                # DEBUG: chequeando valores de parametros y de JOINCONDITION
                #var_dump($PARAMETERS);
                #echo "SQL=$SQL<BR>\n";

                #$db->query('select @PARAMETERS_COD_CENTRO centro');
                #$db->next_record();
                #var_dump($db->Record);
                #die;

                $db->query($SQL);
                if ($db->Errors->ToString()) {
                    error_manager($db->Errors->ToString(), -20101);
                }

                while (clsCore::simplifyNextRecord($db)) {
                    $master = buildMaster($db->Record);
                    // Recorre los Details
                    $master->{'detail'} = buildDetail($detail->name, $master, $detail->detail, $bind);
                    return $master;
                }
            }
        }

        $records = array();
        // Recorre el resultado del MASTER
        while (clsCore::simplifyNextRecord($db)) {
            $master = buildMaster($db->Record);
            // Recorre los Details
            $master->{'detail'} = buildDetail($Relation->name, $master, $Relation->detail, $bind);
            $records[] = $master;
        }

        // remember: returnJson($data, $error=false, $info=false, $header=false, $binded=false)
        clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

//Initialize Method
    function Initialize()
    {
        //if(!$this->Visible) return;

        //$this->DataSource->PageSize     = & $this->PageSize;
        //$this->DataSource->AbsolutePage = & $this->PageNumber;
        //$this->DataSource->SetOrder($this->SorterName, $this->SorterDirection);
        // clsCore::setBindValues($this->DataSource);
    }
//End Initialize Method

//Show Method
    function Show()
    {

    }
//End Show Method

}
