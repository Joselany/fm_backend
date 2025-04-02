<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Chat extends Model{
	protected $fields = array('id','data','hora','passageiro','motorista','mensagem','status_passageiro','status_motorista');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbchat ORDER BY id DESC");
		return $results;
	}
	public static function notification($id,$app)
	{
		$db = new Sql();
		if($app == 'motorista'){
			return $results = $db->select("SELECT * FROM tbchat WHERE motorista=:motorista AND status_motorista=2 ORDER BY id DESC",array(':motorista'=>$id));
		}
		if($app == 'passageiro'){
			return $results = $db->select("SELECT * FROM tbchat WHERE passageiro=:passageiro AND status_passageiro=2 ORDER BY id DESC",array(':passageiro'=>$id));
		}
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbchat WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbchat (data, hora, passageiro, motorista, mensagem, status_passageiro, status_motorista) VALUES (:data, :hora, :passageiro, :motorista, :mensagem, :status_passageiro, :status_motorista)",
			array(
			':data'=>$this->getValue('data'),
			':hora'=>$this->getValue('hora'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':mensagem'=>$this->getValue('mensagem'),
			':status_passageiro'=>$this->getValue('status_passageiro'),
			':status_motorista'=>$this->getValue('status_motorista')
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
			$results = $db->query("UPDATE tbchat SET data=:data, passageiro=:passageiro, motorista=:motorista, mensagem=:mensagem,status_passageiro=:status_passageiro,status_motorist=:status_motorista WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':data'=>$this->getValue('data'),
			':hora'=>$this->getValue('hora'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':mensagem'=>$this->getValue('mensagem'),
			':status_passageiro'=>$this->getValue('status_passageiro'),
			':status_motorista'=>$this->getValue('status_motorista')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function openMsg($app){
		try
		{
			$db = new Sql();
			if($app == 'motorista'){
				$db->query("UPDATE tbchat SET status_motorista=3 WHERE motorista= :motorista AND status_motorista=2",array(':motorista'=>$this->getValue('motorista')));	
			}
			if($app == 'passageiro'){
				$db->query("UPDATE tbchat SET status_passageiro=3 WHERE passageiro= :passageiro AND status_passageiro=2",array(':passageiro'=>$this->getValue('passageiro')));	
			}
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function delete($app){
		try
		{
			$db = new Sql();
			if($app == 'motorista'){
				$db->query("UPDATE tbchat SET status_motorista=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
			}
			if($app == 'passageiro'){
				$db->query("UPDATE tbchat SET status_passageiro=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
			}
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function getPageMotorista($motorista, $passageiro, $page = 1, $itemsPerPage = 30)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbchat
			WHERE motorista = :motorista
			  AND passageiro = :passageiro
			  AND status_motorista != 0
			ORDER BY id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':motorista' => $motorista,
				':passageiro' => $passageiro,
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
	
	public static function getPageMotoristaAll($motorista, $page = 1, $itemsPerPage = 30)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM (SELECT * FROM tbchat WHERE motorista = :motorista AND status_passageiro != 0 ORDER BY id DESC) AS x
			GROUP BY motorista
			LIMIT :limit OFFSET :offset",
			array(
				':motorista' => $motorista,
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
	
	public static function getPagePassageiro($passageiro, $motorista, $page = 1, $itemsPerPage = 30)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbchat
			WHERE passageiro = :passageiro
			  AND motorista = :motorista
			  AND status_passageiro != 0
			ORDER BY id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':passageiro' => $passageiro,
				':motorista' => $motorista,
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
	
	public static function getPagePassageiroAll($passageiro, $page = 1, $itemsPerPage = 30)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM (SELECT * FROM tbchat WHERE passageiro = :passageiro AND status_passageiro != 0 ORDER BY id DESC) AS x
			GROUP BY motorista
			LIMIT :limit OFFSET :offset",
			array(
				':passageiro' => $passageiro,
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
			FROM tbchat
			WHERE (descricao LIKE :search OR passageiro LIKE :search OR motorista LIKE :search)
			  AND status = 1
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