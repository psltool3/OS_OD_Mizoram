<?php

class District{
  public $Id;
  public $Name;
  public $Code;
  public function getCode() { return $this->Code; }
  public function setCode($code) { $this->Code = $code; return $this; }

    /**
     * Get the value of Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * Set the value of Id
     *
     * @param mixed $Id
     *
     * @return self
     */
    public function setId($Id)
    {
        $this->Id = $Id;

        return $this;
    }

    /**
     * Get the value of name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * Set the value of name
     *
     * @param mixed $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->Name = $name;

        return $this;
    }


    function insert(District $District, $con){
        $id = mysqli_real_escape_string($con, $District->getId());
        $name = mysqli_real_escape_string($con, $District->getName());
        $code = $District->getCode() === null ? "NULL" : "'" . mysqli_real_escape_string($con, $District->getCode()) . "'";
        return "INSERT INTO Districts(id,name,district_code) VALUES ('$id','$name',$code)";
    }

    function check(District $District, $con){
        $name = mysqli_real_escape_string($con, $District->getName());
        $code = mysqli_real_escape_string($con, (string)$District->getCode());
        return "SELECT * FROM Districts WHERE name='$name' OR district_code='$code'";
    }

    function delete(District $District){

        return "DELETE FROM Districts WHERE id='".$District->getId()."'";

    }

    function logname(District $District){
        return "SELECT name FROM Districts WHERE id='".$District->getId()."'";
	}

    function update(District $District){

      return  "UPDATE Districts SET name='".$District->getName()."' WHERE id = '".$District->getId()."'";

    }

}

 ?>
