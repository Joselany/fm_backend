<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Mensagem extends Model{
	protected $fields = array('id','data','passageiro','motorista','descricao','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmensagem WHERE status='1' ORDER BY id DESC");
		return $results;
	}
	public function searchById($num)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tb_msg WHERE status='1' AND id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbmensagem (data,passageiro,motorista,descricao,status) VALUES (:data,:passageiro,:motorista,:descricao,:status)",
			array(
			':data'=>$this->getValue('data'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':descricao'=>$this->getValue('descricao'),
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
			$results = $db->query("UPDATE tbmensagem SET data=:data,passageiro=:passageiro,motorista=:motorista,descricao=:descricao,status=:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':data'=>$this->getValue('data'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':descricao'=>$this->getValue('descricao'),
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
			$db->query("UPDATE tbmensagem SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbmensagem
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
			FROM tbmensagem
			WHERE (descricao LIKE :search OR passageiro LIKE :search OR motorista LIKE :search) 
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