<?php 
namespace Core\Model;
use \Core\DB\Sql;
use \Core\Model;
class Permissao extends Model{
	protected $fields = array('id','operador','operacao','ver','modificar');
	protected $values = array();
	public static function listAll($operador)
	{
		$db = new Sql();
		return $results = $db->select("SELECT *, a.id as id FROM tbpermissoes a INNER JOIN tboperacoes b ON b.id = a.operacao WHERE a.operador = :operador ORDER BY a.id ASC",array(':operador'=>$operador));
	}
	public static function verifyPermission($operador,$operacao)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpermissoes WHERE operador = :operador AND operacao=:operacao AND ver=1",array(':operador'=>$operador,'operacao'=>$operacao));
		if (empty($results)){
			echo "Área restrita | <a href='/'> Voltar ao inicio </a> |"; die;
		}
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpermissoes WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbpermissoes (operador,operacao,ver,modificar) VALUES (:operador, :operacao, :ver, :modificar)",
			array(
			':operador'=>$this->getValue('operador'),
			':operacao'=>$this->getValue('operacao'),
			':ver'=>$this->getValue('ver'),
			':modificar'=>$this->getValue('modificar')
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
			$results = $db->query("UPDATE tbpermissoes SET operador = :operador, operacao = :operacao, ver =:ver, modificar =:modificar WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':operador'=>$this->getValue('operador'),
			':operacao'=>$this->getValue('operacao'),
			':ver'=>$this->getValue('ver'),
			':modificar'=>$this->getValue('modificar')
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
			$db->query("UPDATE tboperador SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tboperador 
			WHERE status = 1
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
			FROM tboperador 
			WHERE nome LIKE :search 
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