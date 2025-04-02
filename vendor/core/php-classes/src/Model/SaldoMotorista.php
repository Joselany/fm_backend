<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class SaldoMotorista extends Model{
	protected $fields = array('id','motorista','saldo','data_actualizacao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbsaldo_motorista ORDER BY id DESC");
		return $results;
	}
	public static function getCarregamentos($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmovimento_saldo_motorista WHERE motorista = :motorista AND tipo_movimento = 'Crédito' ORDER BY id DESC LIMIT 9",array(':motorista'=>$motorista));
		return $results;
	}
	public static function getMovimentosComData($motorista, $de, $ate)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmovimento_saldo_motorista WHERE motorista = :motorista AND (data_movimento BETWEEN :de AND :ate) ORDER BY id DESC LIMIT 9",array(':motorista'=>$motorista, ':de'=>$de, ':ate'=>$ate));
		return $results;
	}
	public static function getSaldo($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT saldo FROM tbsaldo_motorista WHERE motorista = :motorista ORDER BY id DESC",array(':motorista'=>$motorista));
		if (!empty($results)){
			return $results[0]['saldo'];
		}else{
			$db2 = new Sql();
			$db2->query("INSERT INTO tbsaldo_motorista (motorista, saldo) VALUES (:motorista, :saldo)", array(':motorista'=>$motorista, 'saldo' => 5000));
			$db = new Sql();
			$results = $db->select("SELECT saldo FROM tbsaldo_motorista WHERE motorista = :motorista ORDER BY id DESC",array(':motorista'=>$motorista));
			if (!empty($results)){
				return $results[0]['saldo'];
			}
		}
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbsaldo_motorista WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbsaldo_motorista (motorista, saldo) VALUES (:motorista, :saldo)",
			array(
				':motorista' => $this->getValue('motorista'),
				':saldo' => $this->getValue('saldo')
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
			$results = $db->query("UPDATE tbsaldo_motorista SET motorista =:motorista, saldo =:saldo WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':motorista'=>$this->getValue('motorista'),
			':saldo'=>$this->getValue('saldo')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function addSaldo($saldo){
		try
		{
			$db = new Sql();
			$results = $db->query("UPDATE tbsaldo_motorista SET saldo=:saldo WHERE motorista= :motorista", 
			array(':motorista'=> $this->getValue('motorista'), ':saldo'=>$saldo));
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
			$db->query("UPDATE tbsaldo_motorista SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_motorista, b.apelido AS apelido_motorista
			FROM tbsaldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
			ORDER BY a.id DESC
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_motorista, b.apelido AS apelido_motorista
			FROM tbsaldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE b.telefone LIKE :search
			ORDER BY a.id DESC
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
	
	public static function getPageFiltro($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_motorista, b.apelido AS apelido_motorista
			FROM tbsaldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE a.status = :search
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':search' => $search,
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
	
	public static function getPageFiltromotorista($search, $motorista, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_motorista, b.apelido AS apelido_motorista
			FROM tbsaldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE a.status = :search AND a.motorista = :motorista
			ORDER BY b.nome DESC
			LIMIT :limit OFFSET :offset",
			array(
				':search' => $search,
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
}

 ?>