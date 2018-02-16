<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of API5 RESTful SQLtoJSON                           |
 | Copyright (C) 2007-2018, Santo Nuzzolillo                             |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the LICENSE file for a full license statement.                    |
 |                                                                       |
 | Pduction                                                              |
 |   Date   : 02/16/2018                                                 |
 |   Time   : 12:47:27 PM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/





Class clsHierarchiesResult {


    public $Query;
    public $Errors;
    public $DataSource;

    function __construct($SQL, $hierarchiesType = 'SIMPLE'){

                                                                                                                                                                                        global $CCSLocales;
        global $DefaultDateFormat;

        
        $this->Errors = new clsErrors();
        $this->DataSource = new clsDBdefault($this);
                                                switch(strtoupper($hierarchiesType)) {
            case 'BY NODE'          : $this->hierarchiesRequestByNodeType($SQL); break;
            case 'SIMPLE'           : $this->hierarchiesRequestSimple($SQL); break;
            case 'MASTER-DETAILS'   : $this->hierarchiesRequestMD($SQL); break;
            default : break;
        }

                                                                                            }
    function clsHierarchiesResult($SQL, $hierarchiesType = 'SIMPLE'){
        self::__construct($SQL, $hierarchiesType);
    }

    function getLevelInfo($levels, $parent_id) {
                $level = array();
        foreach($levels as $i => $v) {
                                    if ($v['parent_id'] === $parent_id) {
                $level[] = $v;
            }
        }
        return $level;
    }

        function getChildren(
        $reference                      , $parent_id = null             , $levels                       , $nodeType = ''            ) {
                                                        
                $reference = json_decode(clsCore::normalizeJSONObjectAttrName($reference));
                                if (json_last_error()) {
            error_manager('getChildren in hierarchiesRequest bad json '.json_last_error_msg(), -20101) ;
        }
        $branch = array();
                                        if (!is_object($reference)) {
            return $branch;
        }
        switch ($reference->tipo) {
            case "BY SELECT" :
                                $bind = new stdClass();
                $bind->parent_id = $parent_id;

                $db = new clsDBdefault();
                $SQL = clsCore::sqlSetParameters($db, $reference->select, $bind);
                $db->query($SQL);
                if ($db->Errors->ToString()) {
                    error_manager($db->Errors->ToString(), -20101);
                }

                                                                                
                                while (clsCore::simplifyNextRecord($db)) {
                                        $record = $db->Record;
                    $element = new stdClass();

                    $element->{"title"} = $record['label'];
                    $element->{"value"} = $record['value'];

                                                            $element->{"isFolder"} = false;
                    $element->{"isNode"} = true;
                    $element->{"icon"} = false;
                    $element->{"expand"} = false;
                                                            
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
                                        $branch = array();
        $level = $this->getLevelInfo($levels, $nodetype);                         foreach ($level as $i => $typeNode) {

            $element = new stdClass();
            $element->{"title"}          = $typeNode['label'];
            $element->{"value"}          = $typeNode['value'];

                                    $element->{"isFolder"}       = true;
            $element->{"isNode"}         = true;
            $element->{"icon"}           = false;
            $element->{"expand"}         = true;
            
            $element->{'children'} = $this->getChildren(
                $typeNode['reference']                  , $parent_id                            , $levels                               , $typeNode['id']                   );
            $branch[] = $element;
                    }

        return $branch;
    }

    function hierarchiesRequestByNodeType($SQL = '')
    {
                        
                                                                                
                                                        
                                                
                                                                                                                                
                                        
        if (substr($SQL,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL,1));
                                    $SQL = clsCore::getSqlParsed($sqlParsed);
        }

                        global $BIND;
        $bind = $BIND;

        $SQL = clsCore::sqlSetParameters(
            $this->DataSource               , $SQL                          , $bind                     );

        $this->DataSource->query($SQL);

        if ($this->DataSource->Errors->ToString()) {
            error_manager($this->DataSource->Errors->ToString(), -20101) ;
        }
                                        $levels = array();
        while (clsCore::simplifyNextRecord($this->DataSource)) {
            $levels[] = $this->DataSource->Record;
        }
                                        
                                $records = $this->buildNode(
            $levels                           , $bind->estructura               , $bind->estructura           );

                clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function hierarchiesRequestSimple($SQL = '')
    {
        
                                                                                                                                                                                        if (substr($SQL,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($SQL,1));
                        $SQL = clsCore::getSqlParsed($sqlParsed);
        }
                
                                        global $BIND;
        $bind = $BIND;

        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                             , $SQL                        , $bind                     );

        $db->query($SQL);
        if ($db->Errors->ToString()) {
            error_manager($db->Errors->ToString(), -20101);
        }

                                
        $records = array();
        $parent_id = false; 
        while (clsCore::simplifyNextRecord($db)) {
            if ($parent_id===false) $parent_id = $db->Record['parent_id'];             $records[$db->Record['id']] = $db->Record;
        }

        function buildElement($record) {
            $element = new stdClass();

            $element->{"title"} = $record['label'];
            $element->{"value"} = $record['value'];

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
                                        $node->{'children'} = $children;
                                        $branch[] = $node;
                    unset($element);
                }
            }
            return $branch;
        }

        $result = buildTreeSimple($records, $parent_id);

                clsCore::returnJson($result, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function hierarchiesRequestMD($JSON = '')
    {
                                
                
        if (substr($JSON,0,1) == ':') {
            $sqlParsed = clsCore::sqlSplitFromFile(substr($JSON,1));
                                    $JSON = clsCore::getSqlParsed($sqlParsed);
        }

        

                
                                        $Relation = json_decode_and_validate($JSON,"hierarchiesRequestMD in hierarchiesRequest bad json");
        $Relation = clsCore::normalizeJSONObjectAttrName($Relation);
        $Relation = json_decode_and_validate($Relation,"hierarchiesRequestMD in hierarchiesRequest bad json");

                        
                
                        global $BIND;
        $bind = $BIND;

        $bd = new clsDBdefault();
        $SQL = $Relation->query;

        $db = new clsDBdefault();
        $SQL = clsCore::sqlSetParameters(
            $db                             , $SQL                        , $bind                     );


        $db->query($SQL);
        if ($db->Errors->ToString()) {
            error_manager($db->Errors->ToString(), -20101);
        }

        function buildMaster($record){
                        return json_decode(json_encode($record));
                    }

        function buildDetail($masterAlias, $master, $details, $bind){
            global $PARAMETERS;
            foreach($details as $detail) {
                                $SQL = 'SELECT * FROM ('.$detail->query.' ) '.$detail->name . ' WHERE '.$detail->joincondition;
                preg_match_all("/" . $masterAlias . "\." . "\s*([a-zA-Z0-9_]+)/ise", $SQL, $arr);

                foreach ($arr[0] as $i => $name) {
                                        $bind->{$name} = $master->{$arr[1][$i]};
                                        $SQL = str_replace($name, ':' . $name, $SQL);
                }
                                
                clsCore::sqlBindVariables($SQL, $bind);

                $db = new clsDBdefault();
                $SQL = clsCore::sqlSetParameters(
                    $db                                     , $SQL                                  , $bind                             );

                                                
                                                                
                $db->query($SQL);
                if ($db->Errors->ToString()) {
                    error_manager($db->Errors->ToString(), -20101);
                }

                while (clsCore::simplifyNextRecord($db)) {
                    $master = buildMaster($db->Record);
                                        $master->{'detail'} = buildDetail($detail->name, $master, $detail->detail, $bind);
                    return $master;
                }
            }
        }

        $records = array();
                while (clsCore::simplifyNextRecord($db)) {
            $master = buildMaster($db->Record);
                        $master->{'detail'} = buildDetail($Relation->name, $master, $Relation->detail, $bind);
            $records[] = $master;
        }

                clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function Initialize()
    {
        
                                    }

    function Show()
    {

    }

}
