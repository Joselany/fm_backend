<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;

class Operador extends Model{
	protected $fields = array('id','nome','tipo','usuario','senha','telefone','email','foto','data_actualizacao','status','empresa');
	protected $values = array();
	
	public static function login($usuario, $senha)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tboperador WHERE usuario = :usuario and status=1", array(':usuario'=>$usuario));
		
		if (!empty($results)){
		  
			$data = $results[0];
			$user = new Operador();
        	if(password_verify($senha, $data['senha'])){
				$user->setValues($data);
				$_SESSION['fastusuario'] = $user->values;
				header('Location:'.DIR_MAE);
			}else{
			header("Location:".DIR_MAE."login/?retorno=credenciais erradas&u=1&s=0!");
			}
		}else{
			header("Location:".DIR_MAE."login?retorno=credenciais erradas&u=0&s=0!"); 
		}	
	}
	public static function logout()
	{
		unset($_SESSION['fastusuario']);
		header('Location:'.DIR_MAE.'login/');
		exit;
	}
	public static function verifyLogin()
	{
		if (!isset($_SESSION['fastusuario']) ){	
			header('Location:'.DIR_MAE.'login/');
			exit;
		}
	}
	public static function listAll()
	{
		$db = new Sql();
		return $results = $db->select("SELECT * FROM tboperador ORDER BY id ASC");
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tboperador WHERE id = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByUser($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tboperador WHERE usuario = :id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			$db->query("INSERT INTO tboperador (nome,tipo,usuario,senha,telefone,email,foto,status,empresa) VALUES (:nome,:tipo,:usuario,:senha,:telefone,:email,:foto,:status,:empresa)",
			array(
			':nome'=>$this->getValue('nome'),
			':tipo'=>$this->getValue('tipo'),
			':usuario'=>$this->getValue('usuario'),
			':senha'=>$this->getValue('senha'),
			':telefone'=>$this->getValue('telefone'),
			':email'=>$this->getValue('email'),
			':empresa'=>$this->getValue('empresa'),
			':foto'=>'img/sem imagem.jpg',
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
			$results = $db->query("UPDATE tboperador SET nome=:nome,tipo=:tipo,usuario=:usuario,senha=:senha,telefone=:telefone,email=:email,foto=:foto,status=:status,empresa=:empresa WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':nome'=>$this->getValue('nome'),
			':tipo'=>$this->getValue('tipo'),
			':usuario'=>$this->getValue('usuario'),
			':senha'=>$this->getValue('senha'),
			':telefone'=>$this->getValue('telefone'),
			':email'=>$this->getValue('email'),
			':foto'=>$this->getValue('foto'),
			':empresa'=>$this->getValue('empresa'),
			':status'=>$this->getValue('status')
			));
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function updateImg($foto){
		try
		{
			$db = new Sql();	
			$db->query("UPDATE tboperador SET foto=:foto WHERE id=:id",
				array(':id'=>$this->getValue('id'),':foto'=>$foto));
		}
		catch(Exception $e)
		{
			$_SESSION['retorno'] = "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function changePassword($usuario, $antigasenha, $novasenha)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tboperador WHERE usuario = :usuario", [':usuario' => $usuario]);
	
		if (empty($results)) {
			return ["status" => "error", "message" => "Usuário não encontrado."];
		}
	
		$data = $results[0];
	
		if (!password_verify($antigasenha, $data['senha'])) {
			return ["status" => "error", "message" => "Senha antiga incorreta."];
		}
	
		$novasenha = password_hash($novasenha, PASSWORD_DEFAULT, ["cost" => 12]);
	
		try {
			$db->query(
				"UPDATE tboperador SET senha = :senha WHERE id = :id",
				[
					':id' => $data['id'],
					':senha' => $novasenha
				]
			);
	
			return ["status" => "success", "message" => "Senha alterada com sucesso."];
		} catch (Exception $e) {
			return ["status" => "error", "message" => "Erro ao atualizar senha. Tente novamente mais tarde."];
		}
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tboperador SET status=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			FROM tboperador a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1
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
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tboperador a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE a.status = 1 AND a.empresa = :empresa
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
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tboperador a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE lower(a.nome) LIKE lower(:search) AND a.status = 1
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
			SELECT *, COUNT(*) OVER() AS nrtotal
			FROM tboperador a
			INNER JOIN tbempresa b ON a.empresa = b.id
			WHERE lower(a.nome) LIKE lower(:search) AND a.status = 1 AND a.empresa = :empresa
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