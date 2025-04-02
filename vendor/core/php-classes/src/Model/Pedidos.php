<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Pedidos extends Model{
	protected $fields = array('id','status_passageiro','passageiro','motorista','data_pedido','data_aceite','origem','paragem','destino','viajante','contacto','local_viatura','tipo_viatura','metodo_pagamento','status','desc_origem','desc_destino','empresa', 'estimativa','cancelado_por','motivo');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpedidos ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpedidos WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByPassageiro($id, $passageiro)
	{	
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpedidos WHERE id = :id AND passageiro = :passageiro",array(':id'=>$id,':passageiro'=>$passageiro));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByMotorista($id, $motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpedidos WHERE id = :id AND motorista = :motorista",array(':id'=>$id,':motorista'=>$motorista));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$num = $db->query("INSERT INTO tbpedidos (passageiro, origem, desc_origem, paragem, destino, desc_destino, viajante,contacto,tipo_viatura,metodo_pagamento,status,empresa, estimativa) VALUES (:passageiro, :origem, :desc_origem, :paragem, :destino, :desc_destino, :viajante, :contacto, :tipo_viatura, :metodo_pagamento, :status,:empresa, :estimativa)",
			array(
			':passageiro'=>$this->getValue('passageiro'),
			':origem'=>$this->getValue('origem'),
			':paragem'=>$this->getValue('paragem'),
			':destino'=>$this->getValue('destino'),
			':desc_origem'=>$this->getValue('desc_origem'),
			':desc_destino'=>$this->getValue('desc_destino'),
			':viajante'=>$this->getValue('viajante'),
			':contacto'=>$this->getValue('contacto'),
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':metodo_pagamento'=>$this->getValue('metodo_pagamento'),
			':empresa'=>$this->getValue('empresa'),
			':estimativa'=>$this->getValue('estimativa'),
			':status'=>1
                ));
			return $num;
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
			$results = $db->query("UPDATE tbpedidos SET status_passageiro=:status_passageiro, passageiro=:passageiro, data_pedido=:data_pedido, origem=:origem, desc_origem=:desc_origem, paragem=:paragem, destino=:destino, desc_destino=:desc_destino, viajante=:viajante, contacto=:contacto, tipo_viatura=:tipo_viatura, metodo_pagamento=:metodo_pagamento, status=:status, motorista=:motorista, data_aceite=:data_aceite, local_viatura=:local_viatura, empresa=:empresa, estimativa=:estimativa, cancelado_por= :cancelado_por, motivo = :motivo WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':status_passageiro'=>$this->getValue('status_passageiro'),
			':passageiro'=>$this->getValue('passageiro'),
			':data_pedido'=>$this->getValue('data_pedido'),
			':origem'=>$this->getValue('origem'),
			':paragem'=>$this->getValue('paragem'),
			':destino'=>$this->getValue('destino'),
			':desc_origem'=>$this->getValue('desc_origem'),
			':desc_destino'=>$this->getValue('desc_destino'),
			':viajante'=>$this->getValue('viajante'),
			':contacto'=>$this->getValue('contacto'),
			':tipo_viatura'=>$this->getValue('tipo_viatura'),
			':metodo_pagamento'=>$this->getValue('metodo_pagamento'),
			':status'=>$this->getValue('status'),
			':motorista'=>$this->getValue('motorista'),
			':data_aceite'=>$this->getValue('data_aceite'),
			':local_viatura'=>$this->getValue('local_viatura'),
			':estimativa'=>$this->getValue('estimativa'),
			':empresa'=>$this->getValue('empresa'),
			':cancelado_por'=>$this->getValue('cancelado_por'),
			':motivo'=>$this->getValue('motivo')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function zerarAllPedidos($status_actual = 1, $status_novo = 0){
		try
		{
			$db = new Sql();
			$results = $db->query("UPDATE tbpedidos SET status=:status_novo WHERE status=:status_actual", 
			array(':status_actual'=> $status_actual, ':status_novo'=> $status_novo));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function atualizarStatusAposAceitar($idPedido, $status_novo = 2){
		try {
			error_log("Atualizando pedido ID: " . $idPedido . " para status: " . $status_novo);
	
			$db = new Sql();
			$results = $db->query(
				"UPDATE tbpedidos SET status = :status_novo WHERE id = :idPedido", 
				array(
					':status_novo' => $status_novo,
					':idPedido' => $idPedido
				)
			);
	
			if ($results === false) {
				throw new Exception("Falha na execução da query ou nenhum registro atualizado.");
			}
	
			error_log("Query executada com sucesso para pedido ID: " . $idPedido);
			return true;
		} catch(Exception $e) {
			error_log("Erro ao atualizar pedido: " . $e->getMessage());
			echo json_encode(["msg" => "Ocorreu um erro! Tente novamente e se o erro persistir, contacte o administrador."]);
			exit;
		}
	}
	
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tbpedidos SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbpedidos
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
			FROM tbpedidos
			WHERE passsageiro LIKE :search
			  AND status = 1
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
	
	public static function getByStatus($status)
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbpedidos
			WHERE status = :status",
			array(':status' => $status)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total
		);
	}
	
	public static function getPageReportPedido($de, $ate, $page = 1, $itemsPerPage = 1000)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.motorista, b.telefone, b.nome, b.apelido, COUNT(*) OVER() AS nrtotal
			FROM tbpedidos a
			LEFT JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE (a.data_pedido BETWEEN :de AND :ate)
			GROUP BY a.motorista, b.telefone, b.nome, b.apelido
			ORDER BY b.nome DESC
			LIMIT :itemsPerPage OFFSET :start",
			array(
				':de' => $de,
				':ate' => $ate,
				':itemsPerPage' => $itemsPerPage,
				':start' => $start
			)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageReportPedidoMotorista($motorista, $de, $ate, $page = 1, $itemsPerPage = 1000)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.motorista, b.telefone, b.nome, b.apelido, COUNT(*) OVER() AS nrtotal
			FROM tbpedidos a
			LEFT JOIN tbperfil_motorista b ON a.motorista = b.id
			WHERE (a.data_pedido BETWEEN :de AND :ate)
			  AND b.telefone = :motorista
			GROUP BY a.motorista, b.telefone, b.nome, b.apelido
			ORDER BY b.nome DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':motorista' => $motorista,
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
	
	public static function getPageReportPedidoStatus($motorista, $status, $de, $ate, $page = 1, $itemsPerPage = 1000)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("SELECT count(*) as total 
								 FROM tbpedidos 
								 WHERE (data_pedido BETWEEN :de AND :ate) 
								   AND motorista = :motorista 
								   AND status = :status 
								 GROUP BY motorista 
								 ORDER BY MAX(id) DESC
								 LIMIT :itemsPerPage OFFSET :start", 
								 array(
									 ':de' => $de,
									 ':ate' => $ate,
									 ':motorista' => $motorista,
									 ':status' => $status,
									 ':itemsPerPage' => $itemsPerPage,
									 ':start' => $start
								 ));
		if (!empty($results)){
			return $results[0]['total'];
		} else {
			return 0;
		}
	}
}

 ?>