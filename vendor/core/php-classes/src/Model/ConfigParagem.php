<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class ConfigParagem extends Model{
	protected $fields = array('id','tempo_limite','taxa','data_actualizacao','operador');
	protected $values = array();
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbconfig_paragem ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbconfig_paragem WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchLast()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbconfig_paragem ORDER BY id DESC ");
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbconfig_paragem (tempo_limite,taxa,operador) VALUES (:tempo_limite,:taxa,:operador)",
			array(
			':tempo_limite'=>$this->getValue('tempo_limite'),
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
			$results = $db->query("UPDATE tbconfig_paragem SET tempo_limite=:tempo_limite,taxa=:taxa,operador=:operador WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':tempo_limite'=>$this->getValue('tempo_limite'),
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
			$db->query("UPDATE tbconfig_paragem SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbconfig_paragem 
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
			FROM tbconfig_paragem 
			WHERE CAST(id AS TEXT) LIKE :search 
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