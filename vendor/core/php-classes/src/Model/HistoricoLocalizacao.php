<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class HistoricoLocalizacao extends Model{
	protected $fields = array('id','localizacao','motorista','passageiro','data','status');
	protected $values = array();
	
	public static function listAllPassageiro()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbhistorico_localizacaopassageiro ORDER BY id DESC");
		return $results;
	}
	public static function listAllMotorista()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbhistorico_localizacaomotorista ORDER BY id DESC");
		return $results;
	}
	public function searchByIdPassageiro($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbhistorico_localizacaopassageiro WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByIdMotorista($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbhistorico_localizacaomotorista WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function savePassageiro(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbhistorico_localizacaopassageiro (localizacao,passageiro) VALUES (:localizacao,:passageiro)",
			array(
			':localizacao'=>$this->getValue('localizacao'),
			':passageiro'=>$this->getValue('passageiro')
                ));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function saveMotorista(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbhistorico_localizacaomotorista (localizacao,motorista) VALUES (:localizacao,:motorista)",
			array(
			':localizacao'=>$this->getValue('localizacao'),
			':motorista'=>$this->getValue('motorista')
                ));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	} 
}

 ?>