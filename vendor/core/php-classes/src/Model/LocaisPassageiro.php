<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class LocaisPassageiro extends Model{
	protected $fields = array('id','passageiro','local','idlocal','endereco','status');
	protected $values = array();
	
	public static function listAll($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tblocais_passageiro WHERE passageiro=:passageiro AND status=1 ORDER BY id ASC",array(':passageiro'=>$passageiro));
		return $results;
	}
	public static function searchByPlace($passageiro,$local)
	{
		$db = new Sql();
		if($local == 'casa' OR $local =='trabalho'){
			$results = $db->select("SELECT * FROM tblocais_passageiro WHERE passageiro=:passageiro AND local=:local AND status=1 ORDER BY id ASC LIMIT 1",array(':passageiro'=>$passageiro,':local'=>$local));
		}else
		{
			$results = $db->select("SELECT * FROM tblocais_passageiro WHERE passageiro=:passageiro AND local!='trabalho' AND local!='casa' AND status=1 ORDER BY id ASC",array(':passageiro'=>$passageiro));
		}
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tblocais_passageiro WHERE status=1 AND id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tblocais_passageiro (passageiro,local,idlocal,endereco,status) VALUES (:passageiro,:local,:idlocal,:endereco,:status)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':local'=>$this->getValue('local'),
			':idlocal'=>$this->getValue('idlocal'),
			':endereco'=>$this->getValue('endereco'),
			':status'=>1
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
			$results = $db->query("UPDATE tblocais_passageiro SET passageiro=:passageiro,local=:local, idlocal=:idlocal,endereco=:endereco,status=:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':passageiro'=>$this->getValue('passageiro'),
			':local'=>$this->getValue('local'),
			':idlocal'=>$this->getValue('idlocal'),
			':endereco'=>$this->getValue('endereco'),
			':status'=>$this->getValue('status')
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
			$db->query("UPDATE tblocais_passageiro SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tblocais_passageiro
			WHERE status = 1
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
			FROM tblocais_passageiro
			WHERE descricao LIKE :search AND status = 1
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