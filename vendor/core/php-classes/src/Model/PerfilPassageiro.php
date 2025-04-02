<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;
use \Core\Sms;
use \Core\Criptografia;

class PerfilPassageiro extends Model{
	protected $fields = array('id','nome','apelido','email','telefone','senha','foto','data_cadastro','data_activacao','data_actualizacao','status_cadastro','status_verificacao','cod_pais');
	protected $values = array();
	
	public static function login($usuario, $senha)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro WHERE (email = :usuario OR telefone=:usuario) AND status_cadastro <> -1", array(':usuario'=>$usuario));
		
		if (!empty($results)){
		  
			$data = $results[0];
			$user = new PerfilPassageiro();
        	if(password_verify($senha, $data['senha'])){
				$user->setValues($data);
				$perfil_passageiro = $user->values;
				return array('perfil_passageiro'=>$perfil_passageiro,'retorno'=>1,'msg'=>'Login efectuado com sucesso');
			}else{
				return array('perfil_passageiro'=>array(),'retorno'=>0,'msg'=>'Senha incorreta');
			}
		}else{
				return array('perfil_passageiro'=>array(),'retorno'=>0,'msg'=>'Número de telemovel ou email incorreto'); 
		}	
	}
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro ORDER BY id DESC");
		return $results;
	}
	public static function listAllTelEmail()
	{
		$db = new Sql();
		$results = $db->select("SELECT telefone, email FROM tbperfil_passageiro WHERE status_cadastro=1");
		return $results;
	}
	public static function listAllEmail()
	{
		$db = new Sql();
		$results = $db->select("SELECT email FROM tbperfil_passageiro WHERE status_cadastro=1");
		return $results;
	}
	public static function listAllTelEmailOff()
	{
		$db = new Sql();
		$results = $db->select("SELECT telefone, email FROM tbperfil_passageiro WHERE status_cadastro=0");
		return $results;
	}
	public static function listAllEmailOff()
	{
		$db = new Sql();
		$results = $db->select("SELECT email FROM tbperfil_passageiro WHERE status_cadastro=0");
		return $results;
	}
	public static function avaliacao($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT AVG(avaliacao_motorista) as avaliacao FROM tbviagens Where passageiro = :id AND status=2",array(':id'=>$id));
		return $results[0];
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro WHERE id = :id AND status_cadastro <> -1",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
		}
	}
	public function searchByIdGeneral($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro tbperfil_motorista WHERE id =:id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
			$this->setValue('senha','');
		}
	}
	public static function searchByTel($tel)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro WHERE telefone = :tel AND status_cadastro <> -1", array(':tel'=>$tel));
		if (!empty($results)){
			return array('retorno'=>1,'dados'=>$results['0']);
		}else{
			return array('retorno'=>0,'dados'=>'');
		}
	}
	public static function searchByEmail($email)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro WHERE email = :email AND status_cadastro <> -1", array(':email'=>$email));
		if (!empty($results)){
			return array('retorno'=>1,'dados'=>$results['0']);
		}else{
			return array('retorno'=>0,'dados'=>'');
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbperfil_passageiro (nome,apelido,email,telefone,senha,foto,status_cadastro,cod_pais, status_verificacao,data_cadastro) VALUES (:nome,:apelido,:email,:telefone,:senha,:foto,:status_cadastro,:cod_pais, :status_verificacao,:data_cadastro)",
			array(
			':nome'=>$this->getValue('nome'),
			':apelido'=>$this->getValue('apelido'),
			':email'=>$this->getValue('email'),
			':telefone'=>$this->getValue('telefone'),
			':senha'=>$this->getValue('senha'),
			':foto'=>'img/sem imagem.jpg',
			':status_cadastro'=>1,
			':data_cadastro'=>date('Y-m-d'),
			':status_verificacao'=>0,
			':cod_pais'=>$this->getValue('cod_pais')

                ));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function salvarJustificativa($passageiro, $justificativa){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbjustificativapassageiro (passageiro,descricao) VALUES (:passageiro, :descricao)",
			array(
			':passageiro'=>$passageiro,
			':descricao'=>$justificativa

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
			$results = $db->query("UPDATE tbperfil_passageiro SET nome=:nome,apelido=:apelido,email=:email,telefone=:telefone,senha=:senha,foto=:foto,status_cadastro=:status_cadastro,cod_pais=:cod_pais WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':nome'=>$this->getValue('nome'),
			':apelido'=>$this->getValue('apelido'),
			':email'=>$this->getValue('email'),
			':telefone'=>$this->getValue('telefone'),
			':senha'=>$this->getValue('senha'),
			':foto'=>$this->getValue('foto'),
			':status_cadastro'=>$this->getValue('status_cadastro'),
			':cod_pais'=>$this->getValue('cod_pais')
                ));

		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function changePassword($id, $antigasenha, $novasenha)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_passageiro WHERE id = :id", array(':id'=>$id));
		if (!empty($results)){
		  
			$data = $results[0];
        	if(password_verify($antigasenha, $data['senha'])){
				$this->setValues($data);
				$novasenha = password_hash($novasenha, PASSWORD_DEFAULT, array("cost"=>12));
				$this->setValue('senha',$novasenha);
				try
				{
						$results = $db->query("UPDATE tbperfil_passageiro SET senha= :senha WHERE id= :id", 
						array(
						':id'=>$this->getValue('id'),
						':senha'=>$this->getValue('senha'),
						));
						return array('retorno'=>1,'msg'=>'Senha alterada com sucesso');
				}catch(Exception $e){
					
					return array('perfil_passageiro'=>'','retorno'=>0,'msg'=>'Erro desconhecido.');
				}
			}else{
			      return array('retorno'=>0,'msg'=>'Senha incorrecta.');   
			}
			}else{
				  return array('retorno'=>0,'msg'=>'Email ou contacto incorrecto.'); 
			}	
	}
	public function changePassword2($id, $novasenha)
	{
		try
		{
			$db = new Sql();	
			$db->query("UPDATE tbperfil_passageiro SET senha=:senha WHERE id=:id",
				array(':id'=>$id,':senha'=>$novasenha));
			return array('retorno'=>1,'msg'=>'Senha alterada com sucesso.');
		}
		catch(Exception $e)
		{
			return array('retorno'=>0,'msg'=>'Erro.');
		}
	}
	public function updateImg($foto){
		try
		{
			$db = new Sql();	
			$db->query("UPDATE tbperfil_passageiro SET foto=:foto WHERE id=:id",
				array(':id'=>$this->getValue('id'),':foto'=>$foto));
			return array('retorno'=>1,'msg'=>'Imagem carregada com sucesso');
		}
		catch(Exception $e)
		{
			return array('retorno'=>0,'msg'=>'Erroo');
		}
	}
	public function confirmTelephoneStep2($id, $codigo){
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbotp WHERE passageiro = :id ORDER BY passageiro DESC LIMIT 1", array(':id'=>$id));
		if (!empty($results))
		{
		  
			$data = $results[0];
        	if(password_verify($codigo, $data['codigo'])){
				try
				{
					$db->query("UPDATE tbperfil_passageiro SET status_verificacao=1, data_activacao=:data_activacao WHERE id= :id", 
					array(':id'=>$id,'data_activacao'=>date('Y-m-d')));
					return array('retorno'=>1,'msg'=>"Número confirmado com sucesso");
				}catch(Exception $e){
					return array('retorno'=>0,'msg'=>"Erro desconhecido.");
				}
			}else{
				  return array('retorno'=>0,'msg'=>"Código errado.");   
			}
			

		}else{
			return array('retorno'=>0,'msg'=>"Erro na autenticação.");
		}
	}
	public function confirmTelephoneStep1($id){
		try
		{
				$senharecuperacao = random_int(10000, 100000);
                $senharecuperacaocifrada = password_hash($senharecuperacao, PASSWORD_DEFAULT, array("cost"=>12));
                $db = new Sql();
			    $db->query("INSERT INTO tbotp (passageiro,codigo) values(:passageiro,:codigo)",
				array(':passageiro'=>$id,':codigo'=>$senharecuperacaocifrada));
				//envio da mensagem
				Sms::send($this->getValue('telefone'),'O código para confirmar o seu número de telefone é: '.$senharecuperacao);
                return array('retorno'=>1,'msg'=>"Mensagem enviada com sucesso.");
		}
		catch(Exception $e)
		{
			return array('retorno'=>0,'msg'=>"Erro desconhecido.");
		}
	}
	public function reset2($id, $codigo){
		$db = new Sql();
		$results = $db->select("SELECT a.id, a.passageiro, a.codigo, a.data, b.telefone FROM tbotp a INNER JOIN tbperfil_passageiro b ON a.passageiro=b.id WHERE a.passageiro = :id ORDER BY a.id DESC LIMIT 1", array(':id'=>$id));
		if (!empty($results))
		{  
			$data = $results[0];
			$verificador = Criptografia::descriptografar($data['codigo'], $codigo); 
        	if($data['telefone'] == $verificador){
				try
				{
					$db->query("UPDATE tbperfil_passageiro SET status_verificacao=1, data_activacao=:data_activacao WHERE id= :id", 
					array(':id'=>$id,':data_activacao'=>date('Y-m-d')));
					return array('retorno'=>1,'msg'=>"Conta recuperada com sucesso!");
				}catch(Exception $e){
					return array('retorno'=>0,'msg'=>"Erro desconhecido.");
				}
			}else{
				  	return array('retorno'=>0,'msg'=>"Código errado.");   
			}

		}else{
			return array('retorno'=>0,'msg'=>"Erro na autenticação.");
		}
	}
	public function reset1($id, $telefone){ 
		try
		{
			$chavePublica = random_int(10000, 100000); // OTP
			$senhaRecuperacaoCifrada = Criptografia::criptografar($telefone, $chavePublica.$telefone.'11');
			$db = new Sql();
			$db->query("INSERT INTO tbotp (passageiro, codigo, data) values(:passageiro, :codigo, :data)", 
				array(':passageiro'=>$id, ':codigo'=> $senhaRecuperacaoCifrada, ':data'=>date('Y-m-d H:i:s')));
	
			// Envio da mensagem
			Sms::send($telefone, 'O código para validar a sua conta é: '.$chavePublica);
			
			return array('retorno'=>1, 'msg'=>"Mensagem enviada com sucesso.");
		}
		catch(Exception $e)
		{
			return array('retorno'=>0, 'msg'=>"Erro desconhecido.");
		}
	}
	
	public function activate(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tbperfil_passageiro SET status_cadastro=1 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function deactivate(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tbperfil_passageiro SET status_cadastro=0 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public function banir(){
		try
		{
			$db = new Sql();
			$db->query("UPDATE tbperfil_passageiro SET status_cadastro=-1 WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function getPage($page = 1, $itemsPerPage = 50)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_passageiro 
			WHERE status_cadastro <> -1 
			ORDER BY nome ASC 
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
	
	public static function getPageFilter($page = 1, $itemsPerPage = 50, $filter)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_passageiro 
			WHERE status_cadastro = :filter 
			ORDER BY id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':filter' => $filter,
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
	
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 50)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_passageiro 
			WHERE (LOWER(nome) LIKE LOWER(:search) OR telefone LIKE :search) 
			AND status_cadastro <> -1 
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
	
	public static function getPageViagens($passageiro, $page = 1, $itemsPerPage = 50)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT *, COUNT(*) OVER() AS nrtotal 
			FROM tbviagens 
			WHERE passageiro = :search 
			ORDER BY id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':search' => $passageiro,
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
	
	public static function getViagem($dtinicio, $dtfinal, $passageiro)
	{	
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviagens a INNER JOIN tbpagamentoviagem b ON a.id=b.viagem WHERE a.termino_viagem BETWEEN :d1 AND :d2 AND a.passageiro=:passageiro AND a.status=2 ORDER BY a.id DESC",array(':d1'=>$dtinicio,':d2'=>$dtfinal,':passageiro'=>$passageiro));
		return $results;
	}
}

 ?>