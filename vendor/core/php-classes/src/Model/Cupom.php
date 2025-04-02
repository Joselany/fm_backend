<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Cupom extends Model{
	protected $fields = array('id','codigo','data_emissao','data_validade','desconto','operador','status','data_actualizacao','tipo','empresa','nome_empresa');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcupom ORDER BY id DESC");
		return $results;
	}
	public static function listCupomAutomatico($d1, $d2, $empresa)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcupom WHERE status=1 AND tipo = 'Automatico' AND empresa=:empresa AND (data_validade > :d2 OR (data_validade BETWEEN :d1 AND :d2)) ORDER BY id DESC limit 1", array(':d1' => $d1, ':d2' => $d2,':empresa'=>$empresa));
		if (!empty($results)){
			return $results[0];
		}
	}
	public static function VerifyPassageiro($passageiro, $cupom)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbcupons_passageiro WHERE passageiro = :passageiro AND cupom = :cupom and status = 0 ORDER BY id DESC limit 1", array(':passageiro' => $passageiro, ':cupom' => $cupom));
		if (!empty($results)){
			return $results[0];
		}
	}
	public static function getDesconto($cupom, $d1, $d2) : float
	{
		$db = new Sql();
		$results = $db->select("SELECT desconto FROM tbcupom WHERE status=1 AND codigo = :codigo AND (data_validade > :d2 OR (data_validade BETWEEN :d1 AND :d2)) ORDER BY id DESC",array(':codigo' => $cupom, ':d1' => $d1, ':d2' => $d2));
		if (!empty($results)){
			return $results[0]['desconto'];
		}else{ return 0.00; }
	}
	public static function getDesconto2($cupom, $d1, $d2, $empresa) : float
	{
		$db = new Sql();
		$results = $db->select("SELECT desconto FROM tbcupom WHERE status=1 AND codigo = :codigo AND empresa=:empresa AND (data_validade > :d2 OR (data_validade BETWEEN :d1 AND :d2)) ORDER BY id DESC",array(':codigo' => $cupom, ':d1' => $d1, ':d2' => $d2,':empresa'=>$empresa));
		if (!empty($results)){
			return $results[0]['desconto'];
		}else{ return 0.00; }
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT a.id, a.codigo, a.data_emissao, a.data_validade, a.desconto, a.operador, a.status, a.data_actualizacao, a.tipo, a.empresa, b.nome as nome_empresa FROM tbcupom a inner join tbempresa b ON a.empresa = b.id WHERE a.id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByCodigo($codigo)
	{
		$db = new Sql();
		$results = $db->select("SELECT codigo, desconto, data_emissao, data_validade,tipo  FROM tbcupom WHERE status=1 AND codigo = :codigo",array(':codigo'=>$codigo));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbcupom (codigo, data_emissao, data_validade, desconto, operador, status, tipo,empresa) VALUES (:codigo, :data_emissao, :data_validade, :desconto, :operador, :status, :tipo, :empresa)",
			array(
			':codigo'=>$this->getValue('codigo'),
			':data_emissao'=>date('Y-m-d'),
			':data_validade'=>$this->getValue('data_validade'),
			':desconto'=>$this->getValue('desconto'),
			':operador'=>$_SESSION['fastusuario']['usuario'],
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
			$results = $db->query("UPDATE tbcupom SET codigo=:codigo, data_validade=:data_validade, desconto=:desconto, operador=:operador, status=:status, tipo=:tipo, empresa=:empresa WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':codigo'=>$this->getValue('codigo'),
			':data_validade'=>$this->getValue('data_validade'),
			':desconto'=>$this->getValue('desconto'),
			':operador'=>$_SESSION['fastusuario']['usuario'],
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
			$db->query("UPDATE tbcupom SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, a.tipo AS tipo, b.nome AS nome_empresa
			FROM tbcupom a
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, a.tipo AS tipo, b.nome AS nome_empresa
			FROM tbcupom a
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, a.tipo AS tipo, b.nome AS nome_empresa
			FROM tbcupom a
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, a.tipo AS tipo, b.nome AS nome_empresa
			FROM tbcupom a
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