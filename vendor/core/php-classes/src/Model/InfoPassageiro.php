<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class InfoPassageiro extends Model{
	protected $fields = array('id','passageiro','indicacao','status_viagem','localizacao_actual','data_actualizacao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbinfo_passageiro ORDER BY id DESC limit 100");
		return $results;
	}
	public function searchById($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbinfo_passageiro WHERE passageiro=:passageiro",array(':passageiro'=>$passageiro));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbinfo_passageiro (passageiro,status_viagem,localizacao_actual) VALUES (:passageiro,0,:localizacao_actual)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':localizacao_actual'=>$this->getValue('localizacao_actual')
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
			$results = $db->query("UPDATE tbinfo_passageiro SET indicacao=:indicacao,status_viagem=:status_viagem,localizacao_actual=:localizacao_actual WHERE passageiro= :passageiro", 
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':indicacao'=>$this->getValue('indicacao'),
			':status_viagem'=>$this->getValue('status_viagem'),
			':localizacao_actual'=>$this->getValue('localizacao_actual')
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
			$db->query("DELETE FROM tbinfo_passageiro WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbinfo_passageiro
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
			FROM tbinfo_passageiro
			WHERE passageiro LIKE :search
			ORDER BY id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':search' => '%'.$search.'%',
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