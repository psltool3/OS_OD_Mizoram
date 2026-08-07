<?php

class Warehouse {
    public $district;
    public $name;
    public $id;
    public $warehousetype;
    public $type;
    public $latitude;
    public $longitude;
    public $storage;
    public $uniqueid;
    public $active;
	public $procurement;
	public $inventory;
	public $procurement_rice;
	public $inventory_rice;
	public $sur_def;

    // Getter methods

    public function getDistrict() {
        return $this->district;
    }

    public function getName() {
        return $this->name;
    }

    public function getId() {
        return $this->id;
    }

    public function getWarehousetype() {
        return $this->warehousetype;
    }

    public function getType() {
        return $this->type;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function getStorage() {
        return $this->storage;
    }
	
	public function getUniqueid() {
        return $this->uniqueid;
    }
	
	public function getActive() {
        return $this->active;
    }
	public function getProcurement() {
        return $this->procurement;
    }
	public function getInventory() {
        return $this->inventory;
    }
	public function getProcurementRice() {
        return $this->procurement_rice;
    }
	public function getInventoryRice() {
        return $this->inventory_rice;
    }
	public function getSurplusDeficite() {
        return $this->sur_def;
    }


    // Setter methods

    public function setDistrict($district) {
        $this->district = $district;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setWarehousetype($warehousetype) {
        $this->warehousetype = $warehousetype;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    public function setStorage($storage) {
        $this->storage = $storage;
    }
	
	public function setUniqueid($uniqueid) {
        $this->uniqueid = $uniqueid;
    }
	
	public function setActive($active) {
        $this->active = $active;
    }
	public function setProcurement($procurement) {
        $this->procurement = $procurement;
    }
	public function setInventory($inventory) {
        $this->inventory = $inventory;
    }
	public function setProcurementRice($procurement_rice) {
        $this->procurement_rice = $procurement_rice;
    }
	public function setInventoryRice($inventory_rice) {
        $this->inventory_rice = $inventory_rice;
    }
	public function setSurplusDeficite($sur_def) {
        $this->sur_def = $sur_def;
    }
	
	function insert(Warehouse $warehouse){
        return "INSERT INTO warehouse (district, name, id, warehousetype, type, latitude, longitude, storage, uniqueid, active, procurement, inventory, procurement_rice, inventory_rice, sur_def) VALUES ('".$warehouse->getDistrict()."','".$warehouse->getName()."','".$warehouse->getId()."','".$warehouse->getWarehousetype()."','".$warehouse->getType()."','".$warehouse->getLatitude()."','".$warehouse->getLongitude()."','".$warehouse->getStorage()."','".$warehouse->getUniqueid()."','".$warehouse->getActive()."','".$warehouse->getProcurement()."','".$warehouse->getInventory()."','".$warehouse->getProcurementRice()."','".$warehouse->getInventoryRice()."','".$warehouse->getSurplusDeficite()."')";
    }

    function delete(Warehouse $warehouse){
        return "DELETE FROM warehouse WHERE uniqueid='".$warehouse->getUniqueid()."'";
    }
	
	function deleteall(Warehouse $warehouse){
        return "DELETE FROM warehouse WHERE 1";
    }
	
	function check(Warehouse $warehouse){
        return "SELECT * FROM warehouse WHERE uniqueid='".$warehouse->getUniqueid()."'";
    }
	
	function logname(Warehouse $warehouse){
        return "SELECT name FROM warehouse WHERE uniqueid='".$warehouse->getUniqueid()."'";
    }
	
	function checkInsert(Warehouse $warehouse){
        return "SELECT * FROM warehouse WHERE LOWER(id)=LOWER('".$warehouse->getId()."')";
    }
	
	function checkEdit(Warehouse $warehouse){
        return "SELECT * FROM warehouse WHERE LOWER(id)=LOWER('".$warehouse->getId()."')";
    }

    function update(Warehouse $warehouse){
      return  "UPDATE warehouse SET district = '".$warehouse->getDistrict()."',name = '".$warehouse->getName()."',id = '".$warehouse->getId()."',warehousetype = '".$warehouse->getWarehousetype()."',type = '".$warehouse->getType()."',latitude = '".$warehouse->getLatitude()."',longitude = '".$warehouse->getLongitude()."',storage = '".$warehouse->getStorage()."',active = '".$warehouse->getActive()."',procurement = '".$warehouse->getProcurement()."',inventory = '".$warehouse->getInventory()."',procurement_rice = '".$warehouse->getProcurementRice()."',inventory_rice = '".$warehouse->getInventoryRice()."',sur_def = '".$warehouse->getSurplusDeficite()."' WHERE uniqueid = '".$warehouse->getUniqueid()."'";
    }
	
	function updateEdit(Warehouse $warehouse){
      return  "UPDATE warehouse SET district = '".$warehouse->getDistrict()."',name = '".$warehouse->getName()."',warehousetype = '".$warehouse->getWarehousetype()."',type = '".$warehouse->getType()."',latitude = '".$warehouse->getLatitude()."',longitude = '".$warehouse->getLongitude()."',storage = '".$warehouse->getStorage()."',active = '".$warehouse->getActive()."',procurement = '".$warehouse->getProcurement()."',inventory = '".$warehouse->getInventory()."',procurement_rice = '".$warehouse->getProcurementRice()."',inventory_rice = '".$warehouse->getInventoryRice()."',sur_def = '".$warehouse->getSurplusDeficite()."' WHERE id = '".$warehouse->getId()."'";
    }
}

?>