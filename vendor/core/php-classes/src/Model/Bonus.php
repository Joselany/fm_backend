<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Bonus extends Model{
	protected $fields = array( 'id', 'data_emissao', 'data_validade', 'valor', 'operador', 'data_actualizacao', 'status', 'codigo', 'tipo', 'empresa');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbbonus ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbbonus WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByCodigo($codigo)
	{
		$db = new Sql();
		$results = $db->select("SELECT *  FROM tbbonus WHERE codigo = :codigo",array(':codigo'=>$codigo));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbbonus (data_emissao, data_validade, valor, operador, status, codigo, tipo, empresa) VALUES (:data_emissao, :data_validade, :valor, :operador, :status, :codigo, :tipo, :empresa)",
			array(
			':data_emissao'=>date('Y-m-d'),
			':data_validade'=>$this->getValue('data_validade'),
			':valor'=>$this->getValue('valor'),
			':codigo'=>$this->getValue('codigo'),
			':operador'=>$_SESSION['usuario']['usuario'],
			':tipo'=>$this->getValue('tipo'),
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
			$results = $db->query("UPDATE tbbonus SET codigo=:codigo, data_emissao=:data_emissao,data_validade=:data_validade, valor=:valor, operador=:operador, status=:status, tipo=:tipo, empresa=:empresa WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':data_emissao'=>$this->getValue('data_emissao'),
			':data_validade'=>$this->getValue('data_validade'),
			':valor'=>$this->getValue('valor'),
			':codigo'=>$this->getValue('codigo'),
			':operador'=>$_SESSION['usuario']['usuario'],
			':tipo'=>$this->getValue('tipo'),
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
			$db->query("UPDATE tbbonus SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbbonus a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1
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
	
	public static function getPageEmpresa($empresa, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbbonus a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1 AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':empresa' => $empresa,
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
			FROM tbbonus a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1 AND a.codigo LIKE :search
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
	
	public static function getPageSearchEmpresa($search, $empresa, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbbonus a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1 AND a.codigo LIKE :search AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':search' => '%'.$search.'%',
				':empresa' => $empresa,
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