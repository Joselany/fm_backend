<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class MovimentoSaldoMotorista extends Model{
	protected $fields = array('id','motorista','data_movimento','metodo_movimento','valor', 'tipo_comprovativo','comprovativo','data_aceite','aceite_por','tipo_movimento','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmovimento_saldo_motorista ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmovimento_saldo_motorista WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByIdMotorista($id, $motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbmovimento_saldo_motorista WHERE id = :id and motorista = :motorista",array(':id'=>$id, ':motorista'=>$motorista));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbmovimento_saldo_motorista (motorista, data_movimento, metodo_movimento, valor, comprovativo, tipo_comprovativo, data_aceite, aceite_por , tipo_movimento, status) VALUES (:motorista, :data_movimento, :metodo_movimento, :valor, :comprovativo, :tipo_comprovativo, :data_aceite, :aceite_por , :tipo_movimento, :status)",
			array(
			':motorista'=>$this->getValue('motorista'),
			':data_movimento'=>$this->getValue('data_movimento'),
			':metodo_movimento'=>$this->getValue('metodo_movimento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
			':tipo_comprovativo'=>$this->getValue('tipo_comprovativo'),
            ':data_aceite'=>$this->getValue('aceite_por'),
            ':aceite_por'=>$this->getValue('aceite_por'),
            ':tipo_movimento'=>$this->getValue('tipo_movimento'),
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
			$results = $db->query("UPDATE tbmovimento_saldo_motorista SET motorista = :motorista, data_movimento=:data_movimento, metodo_movimento=:metodo_movimento,valor=:valor, comprovativo=:comprovativo, tipo_comprovativo= :tipo_comprovativo, data_aceite=:data_aceite, aceite_por=:aceite_por, tipo_movimento=:tipo_movimento, status=:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':motorista'=>$this->getValue('motorista'),
			':data_movimento'=>$this->getValue('data_movimento'),
			':metodo_movimento'=>$this->getValue('metodo_movimento'),
			':valor'=>$this->getValue('valor'),
			':comprovativo'=>$this->getValue('comprovativo'),
			':tipo_comprovativo'=>$this->getValue('tipo_comprovativo'),
            ':data_aceite'=>$this->getValue('aceite_por'),
            ':aceite_por'=>$this->getValue('aceite_por'),
            ':tipo_movimento'=>$this->getValue('tipo_movimento'),
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
			$db->query("UPDATE tbmovimento_saldo_motorista SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function getPage($page = 1, $itemsPerPage = 9)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.id, a.motorista, a.data_movimento, a.metodo_movimento, a.valor, a.tipo_comprovativo, 
				   a.comprovativo, a.data_aceite, a.aceite_por, a.tipo_movimento, a.status, 
				   b.nome, b.apelido, COUNT(*) OVER() AS nrtotal
			FROM tbmovimento_saldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
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
	
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 9)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.id, a.motorista, a.data_movimento, a.metodo_movimento, a.valor, a.tipo_comprovativo, 
				   a.comprovativo, a.data_aceite, a.aceite_por, a.tipo_movimento, a.status, 
				   b.nome, b.apelido, COUNT(*) OVER() AS nrtotal
			FROM tbmovimento_saldo_motorista a
			INNER JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE a.motorista LIKE :search
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
}

 ?>