<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of API5 RESTful SQLtoJSON                           |
 | Copyright (C) 2017-2018, Santo Nuzzolillo                             |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the LICENSE file for a full license statement.                    |
 |                                                                       |
 | Production                                                            |
 |   Date   : 03/04/2018                                                 |
 |   Time   : 05:37:54 PM                                                |
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
        $this->DataSource = new clsDBdefault();
                                                switch(strtoupper($hierarchiesType)) {
                        case 'BYNODE'          : $this->hierarchiesRequestTEST($SQL); break;
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
        switch (strtoupper($reference->tipo)) {
            case "BYQUERY" :
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

                                                        $SQL = clsCore::getSentenceByMethod($SQL);

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

                $SQL = clsCore::getSentenceByMethod($SQL);

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
            $element->{"attributes"} = $record;
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

                $SQL = clsCore::getSentenceByMethod($JSON);

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
            $branch = array();
            foreach($details as $detail) {
                                $SQL = 'SELECT * FROM ('.$detail->query.' ) '.$detail->name . ' WHERE '.$detail->joincondition;
                preg_match_all("/" . $masterAlias . "\." . "\s*([a-zA-Z0-9_]+)/ise", $SQL, $arr);

                                                foreach ($arr[0] as $i => $name) {
                                        $bind->{$name} = $master->{strtolower($arr[1][$i])};
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
                    $branch[] = $master;
                }
            }
            return $branch;
        }

        $records = array();
                while (clsCore::simplifyNextRecord($db)) {
            $master = buildMaster($db->Record);
                        $master->{'detail'} = buildDetail($Relation->name, $master, $Relation->detail, $bind);
            $records[] = $master;
        }

                clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function hierarchiesRequestTEST($JSON = '')
    {

        function buildLevel(
            $lvl                 , & $n               , & $c               , $parent         ) {
                                    $n = (!$n ? 1 : $n+1);             $level["parent_id"] = $parent;
            $level["id"] = $n;
            $level["sec"] = ++$c;                         $level["value"] = $lvl->name;
            $level["label"] = (isset($lvl->label) ? $lvl->label : $lvl->name);
            $level['shownodetype'] = (isset($lvl->shownodetype) ? $lvl->shownodetype : true);
                        $reference = new stdClass();
            $reference->{'type'} = $lvl->type;
                        $reference->{'query'} = $lvl->query;
            $reference->{'name'} = $lvl->name;
            $reference->{'joincondition'} = (isset($lvl->joincondition) ? $lvl->joincondition : "");
            $reference->{'api_name'} = 'null';
            $reference->{'value'} = 'null';
            $level["reference"] = $reference;
            return $level;
        }

        function plainLevels($Relation, & $levels, & $last_id, $parent)
        {
                        $c = 0;
            $parent = $last_id ;
            if (is_array($Relation)) {
                foreach ($Relation as $i => $lvl) {
                    
                    $levels[] = buildLevel(
                        $lvl                           ,$last_id                            ,$c                            , $parent                       );
                    if (isset($lvl->detail)) {
                        plainLevels($lvl->detail, $levels, $last_id, $parent);
                    }
                }
            } else {
                                $levels[] = buildLevel(
                    $Relation                      , $last_id                            , $c                            , $parent                        );
                plainLevels($Relation->detail, $levels, $last_id, $parent);
            }
        }

        function getChildren(
            $reference                          , $parent_record                    , $parent_node_type
            , $levels                           , $masterAlias = ''                ) {
                                                                                                
                        $reference = json_decode(clsCore::normalizeJSONObjectAttrName($reference));
                                                if (json_last_error()) {
                error_manager('getChildren in hierarchiesRequestTEST bad json '.json_last_error_msg(), -20101) ;
            }
            $branch = array();
                                                            if (!is_object($reference)) {
                return $branch;
            }
            switch (strtoupper($reference->type)) {
                case "BYQUERY" :
                                                            $bind = new stdClass();
                                                            
                                                                                                                                            $splitSQL = clsCore::extractOrderBy($reference->query);
                    $reference->query = $splitSQL[0];
                    $reference->orderby = $splitSQL[1];
                                                            $SQL = 'SELECT * FROM ('.$reference->query.' ) '.$reference->name . ($reference->joincondition ? ' WHERE '.$reference->joincondition : "");
                    $SQL = $SQL . ($reference->orderby ? ' ORDER BY '.$reference->orderby : "");
                                        
                                                            preg_match_all("/" . $masterAlias . "\." . "\s*([a-zA-Z0-9_]+)/ise", $SQL, $arr);
                                                                                foreach ($arr[0] as $i => $name) {
                                                $bind->{$name} = $parent_record[$arr[1][$i]];
                                                $SQL = str_ireplace($name, ':' . $name, $SQL);
                    }
                                                                                                                        clsCore::sqlBindVariables($SQL, $bind);

                    $db = new clsDBdefault();
                    $SQL = clsCore::sqlSetParameters(
                        $db                                         , $SQL                                      , $bind                                 );

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
                                                                                                                                                                                                                                                                        $element->{'children'} = buildNode(
                            $levels                                         ,$parent_node_type                              , $record                                       , $reference->name                          );
                        $branch[] = $element;
                    }
                    break;
                default :
                    break;
            }

            return $branch;

        }

        function getLevelInfo($levels, $parent_id = 0) {
                        $level = array();
            foreach($levels as $i => $v) {
                                                if ($v['parent_id'] === $parent_id) {
                    $level[] = $v;
                }
            }
                                    return $level;
        }

        function buildNode($levels, $parent_id, $parent_record, $nodetype) {
                                                                        $branch = array();
            $level = getLevelInfo($levels, $parent_id);                                                 
            foreach ($level as $i => $typeNode) {

                $element = new stdClass();
                $element->{"title"}          = $typeNode['label'];
                $element->{"value"}          = $typeNode['value'];

                                                $element->{"isFolder"}       = true;
                $element->{"isNode"}         = true;
                $element->{"icon"}           = false;
                $element->{"expand"}         = true;

                                                if ($typeNode['shownodetype']) {
                    $element->{'children'} = getChildren(
                        $typeNode['reference']                              , $parent_record                                    , $typeNode['id']                            , $levels                                                                   , $nodetype                          );
                    $branch[] = $element;
                } else {
                    $branch = array_merge($branch,getChildren(
                        $typeNode['reference']                              , $parent_record                                    , $typeNode['id']                            , $levels                                                                   , $nodetype                          ));
                }
                            }

            return $branch;
        }

        global $BIND;
        $bind = $BIND;

                $SQL = clsCore::getSentenceByMethod($JSON);

        $Relation = json_decode_and_validate($JSON,"hierarchiesRequestTEST in hierarchiesRequest bad json");
        $Relation = clsCore::normalizeJSONObjectAttrName($Relation);
        $Relation = json_decode_and_validate($Relation,"hierarchiesRequestMD in hierarchiesRequest bad json");

                        $levels = array();
        $n = 0;
        plainLevels($Relation, $levels, $n, 0);

        $records = array();

                        $records = buildNode(
            $levels                           , 0                               , new stdClass()                  , 'ROOT'
        );
                                        clsCore::returnJson($records, '{"CODE":"0", "MESSAGE" : "SUCCESS"}');
    }

    function Initialize()
    {
        
                                    }

    function Show()
    {

    }

}
