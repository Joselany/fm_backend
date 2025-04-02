<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class CarregamentoCarteira extends Model{
	protected $fields = array('id','passsageiro','data_carregamento','metodo_carregamento','valor','comprovativo','data_aceite','aceite_por','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcarregamento_carteira ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcarregamento_carteira WHERE status=1 AND id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbcarregamento_carteira (passsageiro,data_carregamento,metodo_carregamento,valor,comprovativo,status) VALUES (:passsageiro,:data_carregamento,:metodo_carregamento,:valor,:comprovativo,status)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':data_carregamento'=>$this->getValue('data_carregamento'),
			':metodo_carregamento'=>$this->getValue('metodo_carregamento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
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
			$results = $db->query("UPDATE tbcarregamento_carteira SET passsageiro=:passsageiro,data_carregamento=:data_carregamento,metodo_carregamento=:metodo_carregamento,valor=:valor,comprovativo=:comprovativo,data_aceite=:data_aceite,aceite_por=:aceite_por WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':passageiro'=>$this->getValue('passageiro'),
			':data_carregamento'=>$this->getValue('data_carregamento'),
			':metodo_carregamento'=>$this->getValue('metodo_carregamento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
			':data_aceite'=>$this->getValue('data_aceite'),
			':aceite_por'=>$this->getValue('aceite_por'),
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
			$db->query("UPDATE tbcarregamento_carteira SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbcarregamento_carteira 
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
			FROM tbcarregamento_carteira 
			WHERE passageiro LIKE :search AND status = 1 
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