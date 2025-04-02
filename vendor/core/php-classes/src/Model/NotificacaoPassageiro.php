<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class NotificacaoPassageiro extends Model{
	protected $fields = array('id','notificacao','data','passageiro','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_passageiro WHERE status=1 ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_passageiro WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbnotificacao_passageiro (notificacao,motorista,status) VALUES (:notificacao,:motorista,:status)",
			array(
			':notificacao'=>$this->getValue('notificacao'),
			':motorista'=>$this->getValue('motorista'),
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
			$results = $db->query("UPDATE tbnotificacao_passageiro SET notificacao=:notificacao, motorista=:motorista ,status=:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':notificacao'=>$this->getValue('notificacao'),
			':motorista'=>$this->getValue('motorista'),
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
			$db->query("UPDATE tbnotificacao_passageiro SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbnotificacao_passageiro
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
			FROM tbnotificacao_passageiro
			WHERE notificacao LIKE :search
			AND status = 1
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