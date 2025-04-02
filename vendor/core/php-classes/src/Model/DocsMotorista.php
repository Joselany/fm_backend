<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class DocsMotorista extends Model{
	protected $fields = array('id','motorista','bi','registo_criminal','livrete','titulo_propriedade','carta_conducao');
	protected $values = array();
	
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbdocs_motorista ORDER BY id DESC");
		return $results;
	}
	public function searchById($motorista)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbdocs_motorista WHERE motorista =:motorista",array(':motorista'=>$motorista));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tbdocs_motorista (motorista) VALUES (:motorista)",
			array(
			':motorista'=>$this->getValue('motorista')
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
			$results = $db->query("UPDATE tbdocs_motorista SET bi=:bi,registo_criminal=:registo_criminal,livrete=:livrete,titulo_propriedade=:titulo_propriedade,carta_conducao=:carta_conducao WHERE motorista= :motorista", 
			array(
			':motorista'=>$this->getValue('motorista'),
			':bi'=>$this->getValue('bi'),
			':registo_criminal'=>$this->getValue('registo_criminal'),
			':livrete'=>$this->getValue('livrete'),
			':titulo_propriedade'=>$this->getValue('titulo_propriedade'),
			':carta_conducao'=>$this->getValue('carta_conducao')
                ));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function updateImg($campo,$caminho){
		try
		{
			$db = new Sql();	
			$db->query("UPDATE tbdocs_motorista SET $campo=:caminho WHERE id=:id",
				array(':id'=>$this->getValue('id'),':caminho'=>$caminho));
			return array('docs_motorista'=>$this->getValues(),'retorno'=>1,'msg'=>'Imagem carregada com sucesso');
		}
		catch(Exception $e)
		{
			return array('docs_motorista'=>'','retorno'=>0,'msg'=>'Erro desconhecido.');
		}
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("DELETE FROM tbdocs_motorista WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tbdocs_motorista 
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
			FROM tbdocs_motorista 
			WHERE passageiro LIKE :search 
			ORDER BY id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':search' => '%' . $search . '%',
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