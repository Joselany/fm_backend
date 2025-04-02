<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Viagens extends Model{
	protected $fields = array('id','pedido','origem','destino','inicio_viagem','termino_viagem','distancia_viagem','tempo_viagem','local_paragem','inicio_paragem','fim_paragem','tempo_paragem','motorista','passageiro','avaliacao_motorista','avaliacao_passageiro','feedback','status','desc_destino','desc_origem','empresa','cancelado_por');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviagens ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviagens WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByPedido($pedido)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviagens WHERE pedido = :pedido",array(':pedido'=>$pedido));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$num = $db->query("INSERT INTO tbviagens (pedido,origem,destino,inicio_viagem,distancia_viagem,tempo_viagem,tempo_paragem,motorista,passageiro,status,desc_destino,desc_origem,empresa) VALUES (:pedido,:origem,:destino,:inicio_viagem,:distancia_viagem,:tempo_viagem,:tempo_paragem,:motorista,:passageiro,:status,:desc_destino,:desc_origem,:empresa)",
			array(
			':pedido'=>$this->getValue('pedido'),
			':origem'=>$this->getValue('origem'),
			':destino'=>$this->getValue('destino'),
			':desc_origem'=>$this->getValue('desc_origem'),
			':desc_destino'=>$this->getValue('desc_destino'),
			':inicio_viagem'=>$this->getValue('inicio_viagem'),
			':distancia_viagem'=>$this->getValue('distancia_viagem'),
			':tempo_viagem'=>$this->getValue('tempo_viagem'),
			':tempo_paragem'=>0,
			':motorista'=>$this->getValue('motorista'),
			':passageiro'=>$this->getValue('passageiro'),
			':empresa'=>$this->getValue('empresa'),
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
			$results = $db->query("UPDATE tbviagens SET pedido=:pedido, origem=:origem, destino=:destino, desc_origem = :desc_origem, desc_destino = :desc_destino, inicio_viagem=:inicio_viagem,termino_viagem=:termino_viagem, distancia_viagem=:distancia_viagem, tempo_viagem=:tempo_viagem, local_paragem=:local_paragem, inicio_paragem=:inicio_paragem, fim_paragem=:fim_paragem, tempo_paragem=:tempo_paragem ,motorista=:motorista, passageiro=:passageiro, avaliacao_motorista=:avaliacao_motorista,avaliacao_passageiro=:avaliacao_passageiro,feedback=:feedback,status=:status WHERE id= :id AND (status=1 OR status=2)", 
			array(
				':id'=>$this->getValue('id'),
				':pedido'=>$this->getValue('pedido'),
				':origem'=>$this->getValue('origem'),
				':destino'=>$this->getValue('destino'),
				':desc_origem'=>$this->getValue('desc_origem'),
				':desc_destino'=>$this->getValue('desc_destino'),
				':inicio_viagem'=>$this->getValue('inicio_viagem'),
				':termino_viagem'=>$this->getValue('termino_viagem'),
				':distancia_viagem'=>$this->getValue('distancia_viagem'),
				':tempo_viagem'=>$this->getValue('tempo_viagem'),
				':local_paragem'=>$this->getValue('local_paragem'),
				':inicio_paragem'=>$this->getValue('inicio_paragem'),
				':fim_paragem'=>$this->getValue('fim_paragem'),
				':tempo_paragem'=>$this->getValue('tempo_paragem'),
				':motorista'=>$this->getValue('motorista'),
				':passageiro'=>$this->getValue('passageiro'),
				':avaliacao_motorista'=>$this->getValue('avaliacao_motorista'),
				':avaliacao_passageiro'=>$this->getValue('avaliacao_passageiro'),
				':feedback'=>$this->getValue('feedback'),
				':status'=>$this->getValue('status'),
			));
			return $results;
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
			$db->query("UPDATE tbviagens SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbviagens
			WHERE status = 1
			ORDER BY id DESC
			LIMIT :limit OFFSET :offset", 
			array(':limit' => $itemsPerPage, ':offset' => $start)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageFilter($status)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tbviagens WHERE status=:status ORDER BY id DESC",array(':status'=>$status));
		return $results;
	}
	public static function getPageFilterEmpresa($status,$empresa)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tbviagens WHERE status=:status AND empresa=:empresa ORDER BY id DESC",array(':status'=>$status,':empresa'=>$empresa));
		return $results;
	}
	public static function getPageSearch($de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, 
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			WHERE a.termino_viagem BETWEEN :de AND :ate
			AND a.status = 2
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset", 
			array(':de' => $de, ':ate' => $ate, ':limit' => $itemsPerPage, ':offset' => $start)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageSearchEmpresa($empresa, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, 
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			WHERE a.termino_viagem BETWEEN :de AND :ate
			AND a.status = 2
			AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset", 
			array(':de' => $de, ':ate' => $ate, ':empresa' => $empresa, ':limit' => $itemsPerPage, ':offset' => $start)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageSearchMotorista($de, $ate, $motorista, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, 
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			WHERE a.termino_viagem BETWEEN :de AND :ate
			AND a.status = 2
			AND a.motorista = :motorista
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset", 
			array(':de' => $de, ':ate' => $ate, ':motorista' => $motorista, ':limit' => $itemsPerPage, ':offset' => $start)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageSearchMotoristaEmpresa($empresa, $de, $ate, $motorista, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal, 
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			WHERE a.termino_viagem BETWEEN :de AND :ate
			AND a.status = 2
			AND a.motorista = :motorista
			AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset", 
			array(':de' => $de, ':ate' => $ate, ':motorista' => $motorista, ':empresa' => $empresa, ':limit' => $itemsPerPage, ':offset' => $start)
		);
	
		$total = count($results) > 0 ? (int)$results[0]["nrtotal"] : 0;
	
		return array(
			'data' => $results,
			'total' => $total,
			'pages' => ceil($total / $itemsPerPage)
		);
	}
	
	public static function getPageSearchViagemStatus($de, $ate, $status, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tb_pedido e ON a.pedido = e.id
			WHERE a.inicio_viagem BETWEEN :de AND :ate
			AND e.status = :status
			AND a.motorista = :motorista
			AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':status' => $status,
				':motorista' => $motorista,
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
	
	public static function getPageSearchViagemStatusEmpresa($de, $ate, $status, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tb_pedido e ON a.pedido = e.id
			WHERE a.inicio_viagem BETWEEN :de AND :ate
			AND e.status = :status
			AND a.motorista = :motorista
			AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':status' => $status,
				':motorista' => $motorista,
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
	
	public static function getPageReportViagem($de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE a.inicio_viagem BETWEEN :de AND :ate
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
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
	
	public static function getPageReportViagemEmpresa($empresa, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE a.inicio_viagem BETWEEN :de AND :ate
			AND a.empresa = :empresa
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
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
	
	public static function getPageReportViagemMotorista($motorista, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE a.motorista = :motorista
			AND a.inicio_viagem BETWEEN :de AND :ate
			ORDER BY a.id DESC
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
	
	public static function getPageReportViagemEmpresaMotorista($motorista, $empresa, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE a.inicio_viagem BETWEEN :de AND :ate
			AND a.empresa = :empresa
			AND a.motorista = :motorista
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':empresa' => $empresa,
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
	
	public static function getPageReportViagemStatus($status, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, 
				   f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE e.status = :status
			AND (a.inicio_viagem BETWEEN :de AND :ate)
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':status' => $status,
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
	
	public static function getPageReportViagemEmpresaStatus($empresa, $status, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, 
				   f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE (a.inicio_viagem BETWEEN :de AND :ate)
			AND a.empresa = :empresa
			AND e.status = :status
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':empresa' => $empresa,
				':status' => $status,
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
	
	public static function getPageReportViagemMotoristaStatus($status, $motorista, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, 
				   f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE a.motorista = :motorista
			AND e.status = :status
			AND (a.inicio_viagem BETWEEN :de AND :ate)
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':status' => $status,
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
	
	public static function getPageReportViagemEmpresaMotoristaStatus($empresa, $motorista, $status, $de, $ate, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal,
				   a.id AS id, c.nome AS nome_motorista, d.nome AS nome_passageiro, 
				   f.nome AS regiao, e.status AS status_pedido
			FROM tbviagens a
			INNER JOIN tbpagamentoviagem b ON a.id = b.viagem
			INNER JOIN tbperfil_motorista c ON a.motorista = c.id
			INNER JOIN tbperfil_passageiro d ON a.passageiro = d.id
			INNER JOIN tbpedidos e ON a.pedido = e.id
			INNER JOIN tbempresa f ON f.id = a.empresa
			WHERE (a.inicio_viagem BETWEEN :de AND :ate)
			AND a.empresa = :empresa
			AND a.motorista = :motorista
			AND e.status = :status
			ORDER BY a.id DESC
			LIMIT :limit OFFSET :offset",
			array(
				':de' => $de,
				':ate' => $ate,
				':empresa' => $empresa,
				':motorista' => $motorista,
				':status' => $status,
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