<?php 

namespace Core\Model;

use \Core\DB\Sql;
use \Core\Model;
use \Core\Sms;
use \Core\Criptografia;

class PerfilMotorista extends Model{
	protected $fields = array('id','nome','apelido','email','cod_pais','telefone','senha','foto','status_cadastro','data_cadastro','data_activacao','activado_por','status_servico');
	protected $values = array();
	
	public static function login($usuario, $senha)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE (email =:usuario OR telefone=:usuario) AND status_cadastro <> -1", array(':usuario'=>$usuario));
		
		if (!empty($results)){
		  
			$data = $results[0];
			$user = new PerfilMotorista();
        	if(password_verify($senha, $data['senha'])){
				$user->setValues($data);
				$user->setValue('senha','');
				$perfil_motorista = $user->getValues();
				return array('perfil_motorista'=>$perfil_motorista,'retorno'=>1,'msg'=>'Login efectuado com sucesso');
			}else{
				return array('perfil_motorista'=>array(),'retorno'=>0,'msg'=>'Senha incorreta');
			}
		}else{
				return array('perfil_motorista'=>array(),'retorno'=>0,'msg'=>'Número de telemovel ou email incorreto'); 
		}	
	}
	public static function listAll()
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista ORDER BY nome DESC");
		return $results;
	}
	public static function listAllEmpresa($empresa)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista a INNER JOIN tbinfo_motorista b ON a.id=b.motorista WHERE b.provincia=:empresa ORDER BY a.nome ASC",array(':empresa'=>$empresa));
		return $results;
	}
	public static function listAllTelEmail()
	{
		$db = new Sql();
		$results = $db->select("SELECT telefone, email FROM tbperfil_motorista WHERE status_cadastro=1");
		return $results;
	}
	public static function listAllEmail()
	{
		$db = new Sql();
		$results = $db->select("SELECT email FROM tbperfil_motorista WHERE status_cadastro=1");
		return $results;
	}
	public static function listAllTelEmailOff()
	{
		$db = new Sql();
		$results = $db->select("SELECT telefone, email FROM tbperfil_motorista WHERE status_cadastro=0");
		return $results;
	}
	public static function listAllEmailOff()
	{
		$db = new Sql();
		$results = $db->select("SELECT email FROM tbperfil_motorista WHERE status_cadastro=0");
		return $results;
	}
	public static function listName($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE id=:id", array(':id'=>$id));
		return $results;
	}
	public static function listNameEmpresa($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE id=:id", array(':id'=>$id));
		return $results;
	}
	public static function listTarifa($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT b.tarifa_base, b.tarifa_km, b.hora_inicial, b.hora_final, b.inicio_cobranca, a.id as id FROM tbinfo_motorista a INNER JOIN tbtarifas b ON a.categoria=b.tipo_viatura INNER JOIN tbempresa c ON b.empresa=c.id WHERE a.motorista=:id AND b.status = 1",array(':id'=>$id));
		return $results;
	}
	public static function avaliacao($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT AVG(avaliacao_passageiro) as avaliacao FROM tbviagens Where motorista = :id AND status=2",array(':id'=>$id));
		return $results[0];
	}
	public static function listAllOn()
	{
		$db = new Sql();
		$results = $db->select("SELECT *, a.id as id FROM tbperfil_motorista a INNER JOIN tbinfo_motorista b ON a.id=b.motorista WHERE a.status_cadastro=1 AND a.status_servico=1 ORDER BY a.id ASC");
		return $results;
	}
	public static function listAllOnByCategory($categoria)
	{
		$db = new Sql();
		$results = $db->select("SELECT *, a.id as id FROM tbperfil_motorista a INNER JOIN tbinfo_motorista b ON a.id=b.motorista WHERE a.status_cadastro=1 AND a.status_servico=1 AND b.categoria ilike :categoria AND b.status_viagem = 0 ORDER BY a.id ASC",array(':categoria'=>$categoria));
		return $results;
	}
	public function searchById($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE id =:id AND status_cadastro <> -1",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
			$this->setValue('senha','');
		}
	}
	public function searchByIdGeneral($id)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE id =:id",array(':id'=>$id));
		if (!empty($results)){
			$data = $results[0];
			$this->setValues($data);
			$this->setValue('senha','');
		}
	}
	public static function searchByTel($tel)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE telefone =:tel AND status_cadastro <> -1", array(':tel'=>$tel));
		if (!empty($results)){
			return array('retorno'=>1,'dados'=>$results['0']);
		}else{
			return array('retorno'=>0,'dados'=>array('id'=>'','nome'=>'','apelido'=>''));
		}
	}
	public static function searchByEmail($email)
	{
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE email =:email AND status_cadastro <> -1", array(':email'=>$email));
		if (!empty($results)){
			return 1;
		}else{
			return 0;
		}
	}
	public function save(){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbperfil_motorista (nome,apelido,email,telefone,senha,foto,status_cadastro,cod_pais, status_servico,data_cadastro) VALUES (:nome,:apelido,:email,:telefone,:senha,:foto,:status_cadastro,:cod_pais, :status_servico,:data_cadastro)",
			array(
			':nome'=>$this->getValue('nome'),
			':apelido'=>$this->getValue('apelido'),
			':email'=>$this->getValue('email'),
			':telefone'=>$this->getValue('telefone'),
			':senha'=>$this->getValue('senha'),
			':foto'=>'img/sem imagem.jpg',
			':status_servico'=>0,
			':data_cadastro'=>date('Y-m-d'),
			':status_cadastro'=>0,
			':cod_pais'=>$this->getValue('cod_pais')

                ));
		
		}
		catch(Exception $e)
		{
			echo "Ocorreu um erro! Tente novamente e se o erro persistir contacte o administrador.";
		}
	}
	public static function salvarJustificativa($motorista, $justificativa){
		try
		{
			$db = new Sql();
			return $db->query("INSERT INTO tbjustificativamotorista (motorista,descricao) VALUES (:motorista, :descricao)",
			array(
			':motorista'=>$motorista,
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
			$results = $db->query("UPDATE tbperfil_motorista SET nome=:nome,apelido=:apelido,status_cadastro=:status_cadastro,cod_pais=:cod_pais, data_activacao=:data_activacao, activado_por=:activado_por, status_servico=:status_servico WHERE id= :id", 
			array(
			':id'=>$this->getValue('id'),
			':nome'=>$this->getValue('nome'),
			':apelido'=>$this->getValue('apelido'),
			':status_cadastro'=>$this->getValue('status_cadastro'),
			':cod_pais'=>$this->getValue('cod_pais'),
			':data_activacao'=>$this->getValue('data_activacao'),
			':activado_por'=>$this->getValue('activado_por'),
			':status_servico'=>$this->getValue('status_servico')
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
		$results = $db->select("SELECT * FROM tbperfil_motorista WHERE id =:id", array(':id'=>$id));
		if (!empty($results)){
		  
			$data = $results[0];
        	if(password_verify($antigasenha, $data['senha'])){
				$this->setValues($data);
				$novasenha = password_hash($novasenha, PASSWORD_DEFAULT, array("cost"=>12));
				$this->setValue('senha',$novasenha);
				try
				{
						$results = $db->query("UPDATE tbperfil_motorista SET senha= :senha WHERE id= :id", 
						array(
						':id'=>$this->getValue('id'),
						':senha'=>$this->getValue('senha'),
						));
						return array('retorno'=>1,'msg'=>'Senha alterada com sucesso');
				}catch(Exception $e){
					
					return array('perfil_motorista'=>'','retorno'=>0,'msg'=>'Erro desconhecido.');
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
			$db->query("UPDATE tbperfil_motorista SET senha=:senha WHERE id=:id",
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
			$db->query("UPDATE tbperfil_motorista SET foto=:foto WHERE id=:id",
				array(':id'=>$this->getValue('id'),':foto'=>$foto));
			return array('retorno'=>1,'msg'=>'Imagem carregada com sucesso');
		}
		catch(Exception $e)
		{
			return array('retorno'=>0,'msg'=>'Erroo');
		}
	}
	public function reset2($id, $codigo){
		$db = new Sql();
		$results = $db->select("SELECT a.id, a.motorista, a.codigo, a.data, b.telefone FROM tbotp_motorista a INNER JOIN tbperfil_motorista b ON a.motorista=b.id WHERE a.motorista = :id ORDER BY a.id DESC LIMIT 1", array(':id'=>$id));
		if (!empty($results))
		{ 
			$data = $results[0];
			$verificador = Criptografia::descriptografar($data['codigo'], $codigo);
        	if($data['telefone'] == $verificador){
				try
				{
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
			$db->query("INSERT INTO tbotp_motorista (motorista, codigo, data) values(:motorista, :codigo, :data)", array(':motorista'=>$id, ':codigo'=>$senhaRecuperacaoCifrada, ':data'=>date('Y-m-d H:i:s')));
			
			Sms::send($telefone, 'O código para validar a sua conta é: '.$chavePublica);
			
			return array('retorno'=>1, 'msg'=>"Mensagem enviada com sucesso.");
		}
		catch (Exception $e)
		{
			return array('retorno'=>0, 'msg'=>"Erro desconhecido.");
		}
	}
	public function delete(){
		try
		{
			$db = new Sql();
			$db->query("DELETE FROM tbperfil_passageiro WHERE id= :id",array(':id'=>$this->getValue('id')));	
		
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
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id=b.motorista 
			WHERE a.status_cadastro <> -1 
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
	
	public static function getPageEmpresa($empresa, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id = b.motorista 
			WHERE b.provincia = :empresa AND a.status_cadastro <> -1 
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
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id=b.motorista 
			WHERE (LOWER(a.nome) LIKE LOWER(:search) OR a.telefone LIKE :search) 
			AND a.status_cadastro <> -1 
			ORDER BY a.id DESC 
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
	
	public static function getPageSearchEmpresa($search, $empresa, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id = b.motorista 
			WHERE b.provincia = :empresa 
			AND (LOWER(a.nome) LIKE LOWER(:search) OR a.telefone LIKE :search) 
			ORDER BY a.id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':search' => '%' . $search . '%',
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
	
	public static function getPageFilter($filter, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id = b.motorista 
			WHERE a.status_cadastro = :filter 
			ORDER BY a.id DESC 
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
	
	public static function getPageFilterEmpresa($filter, $empresa, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT a.*, a.id as id, COUNT(*) OVER() AS nrtotal 
			FROM tbperfil_motorista a 
			INNER JOIN tbinfo_motorista b ON a.id = b.motorista 
			WHERE a.status_cadastro = :filter 
			AND b.provincia = :empresa 
			ORDER BY a.id DESC 
			LIMIT :limit OFFSET :offset",
			array(
				':filter' => $filter,
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
	public static function getGanho($dtinicio, $dtfinal, $motorista)
	{	
		$db = new Sql();
		$results = $db->select("SELECT * FROM tbviagens a INNER JOIN tbpagamentoviagem b ON a.id=b.viagem WHERE a.termino_viagem BETWEEN :d1 AND :d2 AND a.motorista=:motorista ORDER BY a.id DESC",array(':d1'=>$dtinicio,':d2'=>$dtfinal,':motorista'=>$motorista));
		return $results;
	} 
}

 ?>