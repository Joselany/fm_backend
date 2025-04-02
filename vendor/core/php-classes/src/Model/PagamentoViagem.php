<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class PagamentoViagem extends Model{
	protected $fields = array('id','viagem','data','data_pagamento','descricao','valor','desconto','imposto','taxa_adicional','valor_pago','metodo_pagamento','moeda','comissao','hash','status','status_comissao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamentoviagem WHERE status=1 ORDER BY id DESC");
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamentoviagem WHERE status=1 AND id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByViagem($viagem)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamentoviagem WHERE viagem = :viagem",array(':viagem'=>$viagem));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbpagamentoviagem (viagem, data_pagamento, descricao, valor, desconto, imposto, taxa_adicional, valor_pago, metodo_pagamento, moeda, comissao, hash, status) VALUES (:viagem, :data_pagamento, :descricao, :valor, :desconto, :imposto, :taxa_adicional, :valor_pago, :metodo_pagamento, :moeda, :comissao, :hash, :status)",
			array(
			':viagem'=>$this->getValue('viagem'),
			':data_pagamento'=>$this->getValue('data_pagamento'),
			':descricao'=>$this->getValue('descricao'),
			':valor'=>$this->getValue('valor'),
			':desconto'=>$this->getValue('desconto'),
			':imposto'=>$this->getValue('imposto'),
			':taxa_adicional'=>$this->getValue('taxa_adicional'),
			':valor_pago'=>$this->getValue('valor_pago'),
			':metodo_pagamento'=>$this->getValue('metodo_pagamento'),
			':moeda'=>$this->getValue('moeda'),
			':comissao'=>$this->getValue('comissao'),
			':hash'=>$this->getValue('hash'),
			':status'=> 0
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
			$results = $db->query("UPDATE tbpagamentoviagem SET viagem=:viagem, data_pagamento=:data_pagamento, descricao=:descricao, valor=:valor, desconto=:desconto, imposto=:imposto, taxa_adicional=:taxa_adicional, valor_pago=:valor_pago, metodo_pagamento=:metodo_pagamento, moeda=:moeda, comissao=:comissao, hash=:hash, status=:status, status_comissao=:status_comissao WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':viagem'=>$this->getValue('viagem'),
			':data_pagamento'=>$this->getValue('data_pagamento'),
			':descricao'=>$this->getValue('descricao'),
			':valor'=>$this->getValue('valor'),
			':desconto'=>$this->getValue('desconto'),
			':imposto'=>$this->getValue('imposto'),
			':taxa_adicional'=>$this->getValue('taxa_adicional'),
			':valor_pago'=>$this->getValue('valor_pago'),
			':metodo_pagamento'=>$this->getValue('metodo_pagamento'),
			':moeda'=>$this->getValue('moeda'),
			':comissao'=>$this->getValue('comissao'),
			':hash'=>$this->getValue('hash'),
			':status'=>$this->getValue('status'),
			':status_comissao'=>$this->getValue('status_comissao')
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
			$db->query("UPDATE tbpagamentoviagem SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbpagamentoviagem 
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
			FROM tbpagamentoviagem 
			WHERE viagem LIKE :search 
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
}

 ?>