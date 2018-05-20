<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 01.05.18
 * Time: 04:03
 */

class PermissionHandler{
    /**
     * @var $dataFields array
     */
    protected $dataFields;
    /**
     * @var $stateHandler StateHandler
     */
    protected $stateHandler;
    /**
     * @var $writePermissionAll array ["stateName" => ["groups => [...], "persons" => [...], "gremium" => [...]],...]
     *                                Any match with group, gremium or person will grant write Permission!
     */
    protected $writePermissionAll;
    
    /**
     * @var $writePermissionField array [ "stateName1" =>
     *                                      [ "fieldName1" =>
     *                                          [ "groups => [...], "persons" => [...], "gremien" => [...], ]
     *                                      ,...]
     *                                  ,...]
     *                                  Any match with group, gremium or person will grant write Permission!
     *                                  If fieldName array is just true than value can be edited from anyone in this
     *                                  state
     */
    protected $writePermissionField;
    
    /**
     * @var $visibleField array [ "fieldname1" => [ "state1", "state2", ...],... ]
     *                          Any Match with any State will grant visibility
     */
    protected $visibleFields;
    protected $editMode;
    
    /**
     * PermissionHandler constructor.
     *
     * @param array        $dataFields
     * @param StateHandler $stateHandler
     * @param array        $writePermissionAll
     * @param array        $writePermissionField
     * @param array        $visibleFields
     * @param bool         $editMode
     */
    public function __construct(array $dataFields, StateHandler $stateHandler, array $writePermissionAll, array $writePermissionField, array $visibleFields, bool $editMode = false){
        $states = $stateHandler->getStates();
        foreach ($states as $stateName => $desc){
            if (!isset($writePermissionAll[$stateName])){
                die("Status $stateName is not defined in \$writePermissionAll");
            }else{
                if ($writePermissionAll[$stateName] !== true){ //could be explicit true or false
                    if (!isset($writePermissionAll[$stateName]["groups"])){
                        $writePermissionAll[$stateName]["groups"] = [];
                    }
                    if (!isset($writePermissionAll[$stateName]["persons"])){
                        $writePermissionAll[$stateName]["persons"] = [];
                    }
                    if (!isset($writePermissionAll[$stateName]["gremien"])){
                        $writePermissionAll[$stateName]["gremien"] = [];
                    }
                }
            }
            foreach ($dataFields as $dataFieldName => $content){
                if (!isset($writePermissionField[$stateName][$dataFieldName]["groups"])){
                    $writePermissionField[$stateName][$dataFieldName]["groups"] = [];
                }
                if (!isset($writePermissionField[$stateName][$dataFieldName]["persons"])){
                    $writePermissionField[$stateName][$dataFieldName]["persons"] = [];
                }
                if (!isset($writePermissionField[$stateName][$dataFieldName]["gremien"])){
                    $writePermissionField[$stateName][$dataFieldName]["gremien"] = [];
                }
            }
        }
        foreach ($dataFields as $dataFieldName => $content){
            if (!isset($visibleFields[$dataFieldName])){
                $visibleFields[$dataFieldName] = true;
            }
        }
        
        $this->dataFields = $dataFields;
        $this->stateHandler = $stateHandler;
        $this->writePermissionAll = $writePermissionAll;
        $this->writePermissionField = $writePermissionField;
        $this->visibleFields = $visibleFields;
        $this->editMode = $editMode;
    }
    
    public function isVisibleField($fieldname){
        
        $fieldname = $this->cleanFieldNameFromArrayTags($fieldname);
        if ($this->visibleFields[$fieldname] === true){
            return true;
        }
        return in_array($this->stateHandler->getActualState(), $this->visibleFields[$fieldname]);
    }
    
    private function cleanFieldNameFromArrayTags($fieldname){
        return explode("[", $fieldname)[0];
    }
    
    public function isAnyDataEditable($couldBe = false){
        $oldEditMode = $this->editMode;
        if ($couldBe === true)
            $this->editMode = true;
        $ret = false;
        $ret |= $this->checkWritePermission();
        foreach ($this->dataFields as $dataFieldName => $content){
            $ret |= $this->checkWritePermissionField($dataFieldName);
        }
        if ($couldBe === true)
            $this->editMode = $oldEditMode;
        //var_dump(["edit" =>$ret]);
        return $ret;
    }
    
    public function checkWritePermission(){
        if ($this->editMode === false)
            return false;
        // https://stackoverflow.com/questions/2715026/are-php5-objects-passed-by-reference -> yes
        $state = $this->stateHandler->getActualState();
        //var_dump($this->writePermissionAll);
        return $this->checkPermissionArray($this->writePermissionAll[$state]);
    }
    
    private function checkPermissionArray($permArray){
        //var_dump($permArray);
        if (is_bool($permArray)){
            return $permArray;
        }
        $ret = AuthHandler::getInstance()->isAdmin();
        if (isset($permArray["groups"]))
            $ret |= AuthHandler::getInstance()->hasGroup(implode(",", $permArray["groups"]));
        if (isset($permArray["gremien"]))
            $ret |= AuthHandler::getInstance()->hasGremium(implode(",", $permArray["gremien"]));
        if (isset($permArray["persons"])){
            $ret |= in_array(AuthHandler::getInstance()->getUsername(), $permArray["persons"], true);
            $ret |= in_array(AuthHandler::getInstance()->getUserFullName(), $permArray["persons"], true);
        }
        //var_dump($ret);
        return (bool)$ret;
    }
    
    public function checkWritePermissionField($fieldname){
        if ($this->editMode === false)
            return false;
        // https://stackoverflow.com/questions/2715026/are-php5-objects-passed-by-reference -> yes
        $fieldname = $this->cleanFieldNameFromArrayTags($fieldname);
        $state = $this->stateHandler->getActualState();
        //var_dump([$fieldname,$this->checkPermissionArray($this->writePermissionField[$state][$fieldname])]);
        return $this->checkPermissionArray($this->writePermissionField[$state][$fieldname]);
    }
    
    public function isEditable($names, $conjunctureWith = ""){
        $ret = [];
        if (is_array($names)){
            $ret_or = false;
            $ret_and = true;
            foreach ($names as $name){
                $tmp = $this->isEditable($name);
                $ret[] = $tmp;
                $ret_or |= $tmp;
                $ret_and &= $tmp;
            }
            if ($conjunctureWith === "")
                return $ret;
            if (strtolower($conjunctureWith) === "or")
                return $ret_or;
            if (strtolower($conjunctureWith) === "and")
                return $ret_and;
            return null;
        }else{
            if ($this->checkWritePermission() === true){
                return true;
            }
            return $this->checkWritePermissionField($names);
        }
    }
    
}