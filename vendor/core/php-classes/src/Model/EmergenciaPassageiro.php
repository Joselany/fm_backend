<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class EmergenciaPassageiro extends Model{
	protected $fields = array('id','passageiro','descricao','numero');
	protected $values = array();
	
	public static function listAll($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbemergencia_passageiro WHERE passageiro=:passageiro ORDER BY id ASC",array(':passageiro'=>$passageiro));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbemergencia_passageiro WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbemergencia_passageiro (passageiro,descricao,numero) VALUES (:passageiro,:descricao,:numero)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
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
			$results = $db->query("UPDATE tbemergencia_passageiro SET passageiro=:passageiro,descricao=:descricao,numero=:numero WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':passageiro'=>$this->getValue('passageiro'),
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
			$db->query("DELETE FROM tbemergencia_passageiro WHERE id= :id AND passageiro=:passageiro",array(':id'=>$this->getValue('id'),':passageiro'=>$this->getValue('passageiro')));	
		
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
			FROM tbemergencia_passageiro  
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
			FROM tbemergencia_passageiro 
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