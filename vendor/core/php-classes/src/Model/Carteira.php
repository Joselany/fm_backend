<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Carteira extends Model{
	protected $fields = array('id','passageiro','data_carregamento','metodo_carregamento','valor','comprovativo','data_aceite','aceite_por','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcarregamento_carteira ORDER BY id DESC");
		return $results;
	}
	public static function getCarregamentos($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcarregamento_carteira WHERE passageiro = :passageiro ORDER BY id DESC",array(':passageiro'=>$passageiro));
		return $results;
	}
	public static function getSaldo($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT saldo FROM tbcarteira WHERE passageiro = :passageiro ORDER BY id DESC",array(':passageiro'=>$passageiro));
		if (!empty($results)){
			return $results[0]['saldo'];
		}else{
			$db2 = new Sql();
			$db2->query("INSERT INTO tbcarteira (passageiro, saldo) VALUES (:passageiro, :saldo)", array(':passageiro'=>$passageiro, 'saldo' => 0));
			$db = new Sql();
			$results = $db->select("SELECT saldo FROM tbcarteira WHERE passageiro = :passageiro ORDER BY id DESC",array(':passageiro'=>$passageiro));
			if (!empty($results)){
				return $results[0]['saldo'];
			}
		}
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcarregamento_carteira WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbcarregamento_carteira (passageiro, data_carregamento, metodo_carregamento, valor, comprovativo, status) VALUES (:passageiro, :data_carregamento, :metodo_carregamento, :valor, :comprovativo, :status)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':data_carregamento'=>date('Y-m-d H:i:s'),
			':metodo_carregamento'=>$this->getValue('metodo_carregamento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
			':status'=>$this->getValue('status')
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
			$results = $db->query("UPDATE tbcarregamento_carteira SET passageiro =:passageiro, metodo_carregamento =:metodo_carregamento, valor =:valor, comprovativo =:comprovativo, data_aceite =:data_aceite, aceite_por =:aceite_por, status =:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':passageiro'=>$this->getValue('passageiro'),
			':metodo_carregamento'=>$this->getValue('metodo_carregamento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
			':data_aceite'=>$this->getValue('data_aceite'),
			':aceite_por'=>$_SESSION['usuario']['id'],
			':status'=>$this->getValue('status')
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
			$results = $db->query("UPDATE tbcarteira SET saldo=:saldo WHERE passageiro= :passageiro", 
			array(':passageiro'=> $this->getValue('passageiro'), ':saldo'=>$saldo));
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
			FROM tbcarregamento_carteira a
			INNER JOIN tbperfil_passageiro b ON a.passageiro = b.id
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
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbcarregamento_carteira a
			INNER JOIN tbperfil_passageiro b ON a.passageiro = b.id
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
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbcarregamento_carteira a
			INNER JOIN tbperfil_passageiro b ON a.passageiro = b.id
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
	
	public static function getPageFiltroPassageiro($search, $passageiro, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbcarregamento_carteira a
			INNER JOIN tbperfil_passageiro b ON a.passageiro = b.id
			WHERE a.status = :search AND a.passageiro = :passageiro
			ORDER BY b.nome DESC
			LIMIT :limit OFFSET :offset",
			array(
				':search' => $search,
				':passageiro' => $passageiro,
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