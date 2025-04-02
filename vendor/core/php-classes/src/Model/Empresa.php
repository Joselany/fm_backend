<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Empresa extends Model{
	protected $fields = array('id','nome','tipo','provincia','status','telefone');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbempresa where status=1 ORDER BY id ASC");
		return $results;
	}
	public static function searchByProvincia($provincia)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbempresa WHERE status=1 AND lower(provincia) = lower(:provincia)",array(':provincia'=>$provincia));
		if (!empty($results)){
			return $results[0]['id'];
		}
	}
	public function searchByProvinciaAll($provincia)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbempresa WHERE status=1 AND lower(provincia) = lower(:provincia) AND id <> 1",array(':provincia'=>$provincia));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchById($id)
	{
		$id = (int)$id;
	
		if ($id <= 0) {
			throw new \Exception("ID inválido.");
		}
	
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbempresa WHERE status=1 AND id = :id", array(':id' => $id));
	
		if (!empty($results)) {
			$data = $results[0];
			$this->setValues($data);
		}
	}
	
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbempresa (nome,tipo,provincia,status,telefone) VALUES (:nome,:tipo,:provincia,:status,:telefone)",
			array(
			':nome'=>$this->getValue('nome'),
			':tipo'=>$this->getValue('tipo'),
			':provincia'=>$this->getValue('provincia'),
			':telefone'=>$this->getValue('telefone'),
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
			$results = $db->query("UPDATE tbempresa SET nome=:nome,tipo=:tipo,provincia=:provincia,status=:status, telefone=:telefone WHERE id= :id AND id <> 1", 
			array(
			':id'=>$this->getValue('id'),
			':nome'=>$this->getValue('nome'),
			':tipo'=>$this->getValue('tipo'),
			':provincia'=>$this->getValue('provincia'),
			':telefone'=>$this->getValue('telefone'),
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
			$db->query("UPDATE tbempresa SET status=0 WHERE id= :id AND id <> 1",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbempresa 
			WHERE status = 1 AND id <> 1 
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
			FROM tbempresa 
			WHERE nome LIKE :search AND status = 1 AND id <> 1 
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