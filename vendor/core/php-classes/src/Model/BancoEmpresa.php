<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class BancoEmpresa extends Model{
	protected $fields = array('id','banco','conta','iban','status','empresa');
	protected $values = array();
	
	public static function listAll($empresa)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbbanco_empresa where status=1 AND empresa = :empresa ORDER BY id ASC", array(':empresa'=>$empresa));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbbanco_empresa WHERE status=1 AND id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbbanco_empresa (banco, conta, iban, status , empresa) VALUES (:banco, :conta, :iban, :status, :empresa)",
			array(
			':banco'=>$this->getValue('banco'),
			':conta'=>$this->getValue('conta'),
			':iban'=>$this->getValue('iban'),
			':empresa'=>$this->getValue('empresa'),
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
			$results = $db->query("UPDATE tbbanco_empresa SET banco=:banco, conta=:conta, iban=:iban, status=:status, empresa=:empresa WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':banco'=>$this->getValue('banco'),
			':conta'=>$this->getValue('conta'),
			':iban'=>$this->getValue('iban'),
			':empresa'=>$this->getValue('empresa'),
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
			$db->query("UPDATE tbbanco_empresa SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbbanco_empresa 
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
			FROM tbbanco_empresa 
			WHERE nome LIKE :search AND status = 1 
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