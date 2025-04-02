<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Tarifas extends Model{
	protected $fields = array('id','tipo_viatura','tarifa_base','tarifa_km','hora_inicial','hora_final','inicio_cobranca','operador','data_actualizacao','status','empresa','nome_empresa');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT *, a.id as id FROM tbtarifas a INNER JOIN tbviaturas b ON a.tipo_viatura = b.tipo ORDER BY a.id ASC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT a.id, a.tipo_viatura, a.tarifa_base, a.tarifa_km, a.hora_inicial, a.hora_final, a.inicio_cobranca, a.operador, a.data_actualizacao, a.status, a.empresa, b.nome as nome_empresa FROM tbtarifas a inner join tbempresa b ON a.empresa = b.id WHERE a.status=1 AND a.id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbtarifas (tipo_viatura,tarifa_base,tarifa_km,hora_inicial,hora_final,inicio_cobranca,operador,status,empresa) VALUES (:tipo_viatura,:tarifa_base,:tarifa_km,:hora_inicial,:hora_final,:inicio_cobranca,:operador,:status,:empresa)",
			array(
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':tarifa_base'=>$this->getValue('tarifa_base'),
			':tarifa_km'=>$this->getValue('tarifa_km'),
			':hora_inicial'=>$this->getValue('hora_inicial'),
			':hora_final'=>$this->getValue('hora_final'),
			':inicio_cobranca'=>$this->getValue('inicio_cobranca'),
			':operador'=>$this->getValue('operador'),
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
			$results = $db->query("UPDATE tbtarifas SET tipo_viatura=:tipo_viatura,tarifa_base=:tarifa_base,tarifa_km=:tarifa_km,hora_inicial=:hora_inicial,hora_final=:hora_final,inicio_cobranca=:inicio_cobranca,operador=:operador, status=:status, empresa=:empresa WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':tarifa_base'=>$this->getValue('tarifa_base'),
			':tarifa_km'=>$this->getValue('tarifa_km'),
			':hora_inicial'=>$this->getValue('hora_inicial'),
			':hora_final'=>$this->getValue('hora_final'),
			':inicio_cobranca'=>$this->getValue('inicio_cobranca'),
			':operador'=>$this->getValue('operador'),
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
			$db->query("UPDATE tbtarifas SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_empresa
			FROM tbtarifas a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1
			ORDER BY a.empresa DESC
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_empresa
			FROM tbtarifas a
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_empresa
			FROM tbtarifas a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.tipo_viatura LIKE :search AND a.status = 1
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
			SELECT *, COUNT(*) OVER() AS nrtotal, a.id AS id, b.nome AS nome_empresa
			FROM tbtarifas a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.tipo_viatura LIKE :search AND a.status = 1 AND a.empresa = :empresa
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