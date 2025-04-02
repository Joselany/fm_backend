<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Comissao extends Model{
	protected $fields = array('id','taxa','tipo_viatura','operador','data_actualizacao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcomissao ORDER BY id DESC");
		return $results;
	}
	public static function getTaxa($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT a.taxa FROM tbcomissao a INNER JOIN tbinfo_motorista b ON a.tipo_viatura = b.categoria WHERE b.motorista=:motorista",array(':motorista'=>$motorista));
		return $results[0];
	}
	public static function getTaxaPiloto()
	{
		$db = new Sql();
		$results = $db->select("SELECT taxa FROM tbcomissao WHERE id = 2");
		return $results[0];
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcomissao WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbcomissao (tipo_viatura,taxa,operador) VALUES (:tipo_viatura,:taxa,:operador)",
			array(
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':taxa'=>$this->getValue('taxa'),
			':operador'=>$this->getValue('operador')
                ));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function update(){
		try
		{
			$db = new Sql();
			$results = $db->query("UPDATE tbcomissao SET tipo_viatura=:tipo_viatura,taxa=:taxa,operador=:operador WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':taxa'=>$this->getValue('taxa'),
			':operador'=>$this->getValue('operador')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("DELETE FROM tbcomissao WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function getPage($page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbcomissao 
			ORDER BY id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':limit' => $itemsPerPage,
				':offset' => $start
			)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbcomissao 
			WHERE tipo_viatura LIKE :search 
			ORDER BY id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':search' => '%' . $search . '%',
				':limit' => $itemsPerPage,
				':offset' => $start
			)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
}

 ?>