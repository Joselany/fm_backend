<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class NotificacaoMotorista extends Model{
	protected $fields = array('id','notificacao','data','motorista','pedido','tipo','status');
	protected $values = array();
	
	public static function listAll($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_motorista WHERE status=1 AND motorista = :motorista AND tipo !='pedido de viagem' ORDER BY id ASC",array(':motorista'=>$motorista));
		return $results;
	}
	public static function searchByPedido($motorista,$pedido)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_motorista WHERE (status=1 OR status=2) AND motorista=:motorista AND pedido=:pedido  ORDER BY id ASC",array(':motorista'=>$motorista,':pedido'=>$pedido));
		if(empty($results)){return 0; }else{ return 1;}
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_motorista WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbnotificacao_motorista (notificacao, motorista, pedido,tipo, status) VALUES (:notificacao, :motorista, :pedido,:tipo, :status)",
			array(
			':notificacao'=>$this->getValue('notificacao'),
			':motorista'=>$this->getValue('motorista'),
			':pedido'=>$this->getValue('pedido'),
			':tipo'=>$this->getValue('tipo'),
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
			$results = $db->query("UPDATE tbnotificacao_motorista SET notificacao=:notificacao, motorista=:motorista ,status=:status WHERE id= :id", 
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
	public static function updateAll($pedido,$status){
		try
		{
			$db = new Sql();
			$results = $db->query("UPDATE tbnotificacao_motorista SET status=:status WHERE pedido= :pedido", 
			array(':pedido'=>$pedido,':status'=>$status));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function deleteByOrderId($pedido){
		try
		{
			$db = new Sql();
			$results = $db->query("UPDATE tbnotificacao_motorista SET status=0 WHERE pedido= :pedido", array(':pedido'=>$pedido));
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
			$db->query("UPDATE tbnotificacao_motorista SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function deleteAll($motorista){
		try
		{
			$db = new Sql();
			$db->query("DELETE FROM tbnotificacao_motorista WHERE motorista= :motorista",array(':motorista'=>$motorista));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}public static function getPage($page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbnotificacao_motorista
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
			FROM tbnotificacao_motorista
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
	
	public static function getPedido($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbnotificacao_motorista WHERE status=1 AND motorista=:motorista AND tipo='pedido de viagem'  ORDER BY id DESC limit 1",array(':motorista'=>$motorista));
		return $results;
	}
}

 ?>