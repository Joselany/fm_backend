<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Viaturas extends Model{
	protected $fields = array('id','tipo','icon','descricao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviaturas ORDER BY descricao ASC");
		return $results;
	}
	public static function listAllComplete($provincia)
	{
		$db = new Sql();
		$results = $db->select("SELECT *, a.id as id, a.tipo as tipo, c.tipo as tipo_empresa FROM tbviaturas a INNER JOIN tbtarifas b ON a.tipo = b.tipo_viatura INNER JOIN tbempresa c ON c.id=b.empresa WHERE b.status=1 AND lower(c.provincia) = lower(:provincia) ORDER BY a.id ASC",array(':provincia'=>$provincia));
		return $results;
	}
	public static function listAllComplete1($id,$provincia)
	{
		$db = new Sql();
		$results = $db->select("SELECT *, a.id as id, a.tipo as tipo, c.tipo as tipo_empresa FROM tbviaturas a INNER JOIN tbtarifas b ON a.tipo = b.tipo_viatura INNER JOIN tbempresa c ON c.id=b.empresa WHERE b.status=1 AND a.id=:id AND lower(c.provincia) = lower(:provincia) ORDER BY a.id ASC",array(':id' => $id,':provincia'=>$provincia));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviaturas WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbviaturas (tipo,icon,descricao) VALUES (:tipo,:icon,:descricao)",
			array(
			':tipo'=>$this->getValue('tipo'),
			':icon'=>'img/sem imagem.jpg',
			':descricao'=>$this->getValue('descricao')
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
			$results = $db->query("UPDATE tbviaturas SET tipo=:tipo,icon=:icon,descricao=:descricao WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':tipo'=>$this->getValue('tipo'),
			':icon'=>$this->getValue('icon'),
			':descricao'=>$this->getValue('descricao')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function updateImg($icon){
		try
		{
			$db = new Sql();	
			$db->query("UPDATE tbviaturas SET icon=:icon WHERE id=:id",
				array(':id'=>$this->getValue('id'),':icon'=>$icon));
		}
		catch(Exception $e)
		{
			$_SESSION['retorno'] = "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("DELETE FROM tbviaturas WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbviaturas 
			ORDER BY id ASC 
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
			FROM tbviaturas 
			WHERE descricao LIKE :search 
			ORDER BY id ASC 
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