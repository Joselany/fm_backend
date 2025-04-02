<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class PagamentoCarteira extends Model{
	protected $fields = array('id','viagem','passageiro','motorista','data_pagamento','valor','hash','status');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamento_carteira ORDER BY id DESC");
		return $results;
	}
	public static function getPagamentoPassageiro($passageiro)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamento_carteira WHERE passageiro = :passageiro ORDER BY id DESC",array(':passageiro'=>$passageiro));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamento_carteira WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByViagem($viagem)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbpagamento_carteira WHERE viagem = :viagem",array(':viagem'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbpagamento_carteira (viagem, passageiro, motorista, valor, hash, status) VALUES (:viagem, :passageiro, :motorista, :valor, :hash, :status)",
			array(
			':viagem'=>$this->getValue('viagem'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':valor'=>$this->getValue('valor'),
			':hash'=>$this->getValue('hash'),
			':status'=>2
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
			$results = $db->query("UPDATE tbpagamento_carteira SET viagem=:viagem, passageiro=:passageiro, motorista=:motorista, valor=:valor, hash=:hash, status=:status WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':viagem'=>$this->getValue('viagem'),
			':passageiro'=>$this->getValue('passageiro'),
			':motorista'=>$this->getValue('motorista'),
			':valor'=>$this->getValue('valor'),
			':hash'=>$this->getValue('hash'),
			':status'=>$this->getValue('status')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function finalizar($viagem)
	{
		$db = new Sql();
		$results = $db->select("UPDATE tbpagamento_carteira SET status=2 WHERE viagem=:viagem",array(':viagem'=>$viagem));
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tbpagamento_carteira SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));
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
			FROM tbpagamento_carteira
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
			FROM tbpagamento_carteira
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
	
	public static function getPageFiltro($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tbpagamento_carteira
			WHERE status = :search
			ORDER BY id DESC
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
			FROM tbpagamento_carteira
			WHERE status = :search AND passageiro = :passageiro
			ORDER BY id DESC
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