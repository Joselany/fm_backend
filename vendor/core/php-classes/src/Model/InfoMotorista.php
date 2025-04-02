<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class InfoMotorista extends Model{
	protected $fields = array('id','motorista','provincia','cidade','endereco','marca_viatura','modelo_viatura','matricula','cor','categoria','livrete','titulo_propriedade','carta_conducao','validade_carta_conducao','bi','validade_bi','endereco_partida','localizacao_actual','status_viagem');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbinfo_motorista ORDER BY id DESC");
		return $results;
	}
	public function searchById($motorista)
	{
		if (!is_numeric($motorista)) {
			throw new \InvalidArgumentException("ID inválido: $motorista");
		}
		
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbinfo_motorista WHERE motorista = :motorista",array(':motorista'=>$motorista));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
		return $results;
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbinfo_motorista (motorista,status_viagem,localizacao_actual) VALUES (:motorista,0,:localizacao_actual)",
			array(
			':motorista'=>$this->getValue('motorista'),
			':localizacao_actual'=>$this->getValue('localizacao_actual')
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
			$results = $db->query("UPDATE tbinfo_motorista SET provincia=:provincia, cidade=:cidade, endereco=:endereco, marca_viatura=:marca_viatura, modelo_viatura=:modelo_viatura, matricula=:matricula, cor=:cor, categoria=:categoria, livrete=:livrete, titulo_propriedade=:titulo_propriedade, carta_conducao=:carta_conducao, validade_carta_conducao=:validade_carta_conducao, bi=:bi, validade_bi=:validade_bi, endereco_partida=:endereco_partida, localizacao_actual=:localizacao_actual, status_viagem=:status_viagem WHERE motorista=:motorista", 
			array(
			':motorista'=>$this->getValue('motorista'),
			':provincia'=>$this->getValue('provincia'),
			':cidade'=>$this->getValue('cidade'),
			':endereco'=>$this->getValue('endereco'),
			':marca_viatura'=>$this->getValue('marca_viatura'),
			':modelo_viatura'=>$this->getValue('modelo_viatura'),
			':matricula'=>$this->getValue('matricula'),
			':cor'=>$this->getValue('cor'),
			':categoria'=>$this->getValue('categoria'),
			':livrete'=>$this->getValue('livrete'),
			':titulo_propriedade'=>$this->getValue('titulo_propriedade'),
			':carta_conducao'=>$this->getValue('carta_conducao'),
			':validade_carta_conducao'=>$this->getValue('validade_carta_conducao'),
			':bi'=>$this->getValue('bi'),
			':validade_bi'=>$this->getValue('validade_bi'),
			':endereco_partida'=>$this->getValue('endereco_partida'),
			':localizacao_actual'=>$this->getValue('localizacao_actual'),
			':status_viagem'=>$this->getValue('status_viagem')
			
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
			$db->query("DELETE FROM tbinfo_motorista WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbinfo_motorista
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
			FROM tbinfo_motorista
			WHERE passageiro LIKE :search
			ORDER BY id DESC
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