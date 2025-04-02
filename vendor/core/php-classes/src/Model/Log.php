<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Log extends Model{
	protected $fields = array('id','descricao','tipo','data','codigo','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tblog ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tblog WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public static function save($descricao, $tipo, $codigo){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tblog (descricao, tipo, codigo, status) VALUES (:descricao, :tipo, :codigo, :status)",
			array(
				':descricao'=>$descricao, 
				':tipo'=>$tipo,
			    ':codigo'=>$codigo, 
			    ':status'=>1
			));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
}

 ?>