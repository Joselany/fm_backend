<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class EmergenciaMotorista extends Model{
	protected $fields = array('id','motorista','descricao','numero');
	protected $values = array();
	
	public static function listAll($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbemergencia_motorista WHERE motorista=:motorista ORDER BY id ASC",array(':motorista'=>$motorista));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbemergencia_motorista WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbemergencia_motorista (motorista,descricao,numero) VALUES (:motorista,:descricao,:numero)",
			array(
			':motorista'=>$this->getValue('motorista'),
			':descricao'=>$this->getValue('descricao'),
			':numero'=>$this->getValue('numero')
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
			$results = $db->query("UPDATE tbemergencia_motorista SET motorista=:motorista,descricao=:descricao,numero=:numero WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':motorista'=>$this->getValue('motorista'),
			':descricao'=>$this->getValue('descricao'),
			':numero'=>$this->getValue('numero')
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
			$db->query("DELETE FROM tbemergencia_motorista WHERE id= :id AND motorista=:motorista",array(':id'=>$this->getValue('id'),':motorista'=>$this->getValue('motorista')));	
		
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
			FROM tbemergencia_motorista  
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
			FROM tbemergencia_motorista 
			WHERE descricao LIKE :search 
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