<?php 
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();
date_default_timezone_set("Africa/Luanda");
require_once("vendor/autoload.php");

include_once('dir.php');

use \Slim\Slim;
use \Core\Email;
use \Core\Sms;
use \Core\Geolocalizacao;
use \Core\Page;
use \Core\PageAdmin;
use \Core\UploadImg;
use \Core\UploadPdf;
use \Core\Recaptcha;
use \Core\Criptografia;
use \Core\Model\BancoEmpresa;
use \Core\Model\Bonus;
use \Core\Model\CarregamentoCarteira;
use \Core\Model\Carteira;
use \Core\Model\Chat;
use \Core\Model\Comissao;
use \Core\Model\ConfigParagem;
use \Core\Model\Cupom;
use \Core\Model\CupomPassageiro;
use \Core\Model\DocsMotorista;
use \Core\Model\EmergenciaPassageiro;
use \Core\Model\EmergenciaMotorista;
use \Core\Model\Empresa;
use \Core\Model\FilaChamada;
use \Core\Model\FormasPagamento;
use \Core\Model\HistoricoLocalizacao;
use \Core\Model\InfoPassageiro;
use \Core\Model\InfoMotorista;
use \Core\Model\LocaisPassageiro;
use \Core\Model\Log;
use \Core\Model\Mensagem;
use \Core\Model\Moeda;
use \Core\Model\MovimentoSaldoMotorista;
use \Core\Model\NotificacaoMotorista;
use \Core\Model\NotificacaoPassageiro;
use \Core\Model\Operacoes;
use \Core\Model\Operador;
use \Core\Model\PagamentoCarteira;
use \Core\Model\PagamentoViagem;
use \Core\Model\Pedidos;
use \Core\Model\Permissao;
use \Core\Model\PerfilMotorista;
use \Core\Model\PerfilPassageiro;
use \Core\Model\Pesquisa;
use \Core\Model\SaldoMotorista;
use \Core\Model\Tarifas;
use \Core\Model\Viagens;
use \Core\Model\Viaturas;

$app = new Slim();
$app->config('debug', true);

$app->get('/hora', function(){
	echo date('Y-m-d H:i:s');
});
$app->get('/dados-empresa-:provincia', function($provincia){
	$empresa = new Empresa();
	$empresa->searchByProvinciaAll($provincia);
	$contas_bancarias = BancoEmpresa::listAll($empresa->getValue('id'));
	header('Content-Type: application/json');
	echo json_encode(array('empresa'=>$empresa->getValues(), 'contas_bancarias'=> $contas_bancarias));
	exit;
});
$app->get('/mapa', function(){
	Operador::verifyLogin();

	function parseToXML($htmlStr){
		$xmlStr=str_replace('<','&lt;',$htmlStr);
		$xmlStr=str_replace('>','&gt;',$xmlStr);
		$xmlStr=str_replace('"','&quot;',$xmlStr);
		$xmlStr=str_replace("'",'&#39;',$xmlStr);
		$xmlStr=str_replace("&",'&amp;',$xmlStr);
		return $xmlStr;
	}
	// Select all the rows in the markers table
	$motoristas = PerfilMotorista::listAllOn();
	// Start XML file, echo parent node
	echo '<markers>';
	// Iterate through the rows, printing XML nodes for each
	foreach ($motoristas as $value) {
	  // Add to XML document node
	  echo '<marker ';
	  echo 'name="'. parseToXML($value['nome'].' '.$value['apelido']).'" ';
	  echo 'address="'.parseToXML('localização actual').'" ';
	  $endereco = explode(",", $value['localizacao_actual']);
	  echo 'lat="' . $endereco['0'] . '" '; 
	  echo 'lng="' . $endereco['1'] . '" ';
	  echo 'type="' .'Motoqueiro'. '" ';
	  echo '/>';
	}

	// End XML file
	echo '</markers>';
	header("Content-type: text/xml");
	exit;
});

$app->get('/mapa2', function(){
	Operador::verifyLogin();
	function parseToXML($htmlStr2){
		$xmlStr=str_replace('<','&lt;',$htmlStr2);
		$xmlStr=str_replace('>','&gt;',$xmlStr);
		$xmlStr=str_replace('"','&quot;',$xmlStr);
		$xmlStr=str_replace("'",'&#39;',$xmlStr);
		$xmlStr=str_replace("&",'&amp;',$xmlStr);
		return $xmlStr;
	}
	// Select all the rows in the markers table
	$passageiros = InfoPassageiro::listAll();
	// Start XML file, echo parent node
	echo '<markers>';
	// Iterate through the rows, printing XML nodes for each
	foreach ($passageiros as $value) {
	  	if(!empty($value['localizacao_actual']) AND $value['localizacao_actual'] != 'null'){
		  // Add to XML document node
		  echo '<marker ';
		  echo 'name="'.parseToXML('Passageiro').'" ';
		  echo 'address="'.parseToXML('localização actual').'" ';
		  $endereco = explode(",", $value['localizacao_actual']);
		  echo 'lat="' . $endereco['0'] . '" '; 
		  echo 'lng="' . $endereco['1'] . '" ';
		  echo 'type="' .'Passageiro'. '" ';
		  echo '/>';
		}
	}
	// End XML file
	echo '</markers>';
	header("Content-type: text/xml");
	exit;
});
//motoristas on
$app->get('/motoristasonline', function(){
	// Select all the rows in the markers table
	$motoristas = PerfilMotorista::listAllOn();
	$localizacao_passageiro = isset($_GET['localizacao_passageiro'])?$_GET['localizacao_passageiro']:0; 
	//info pré cadastradas da pesquisa
	$distancia_pesquisa = new Pesquisa();
	$distancia_pesquisa->searchById(1);
	$distancia_pesquisa = $distancia_pesquisa->getValues();	
	$dados = array();            
	// Iterate through the rows, printing XML nodes for each
	foreach ($motoristas as $value) {
		// Add to XML document node
		if($localizacao_passageiro == 0 OR $localizacao_passageiro == ''){
			$dados[] = array('matricula'=>$value['matricula'],'apelido'=>$value['apelido'],'localizacao_actual'=>$value['localizacao_actual']);
		}else{
			if($value['localizacao_actual'] != ''){
				$localizacao_actualp = explode(",", $localizacao_passageiro);
				$localizacao_actualm = explode(",", $value['localizacao_actual']);
				
				//distancia entre o passageiro e o motorista
				$distancia = Geolocalizacao::distanciaKm($localizacao_actualp[0],$localizacao_actualp[1],$localizacao_actualm[0],$localizacao_actualm[1]);
				
				$distanciaKm = number_format($distancia, 2, '.', '');
				$distanciaMin = intval((($distanciaKm / 70)*60));
				if($distancia <= $distancia_pesquisa['raio']){
					$dados[] = array('matricula'=>$value['matricula'],'apelido'=>$value['apelido'],'localizacao_actual'=>$value['localizacao_actual'],'distancia_minuto'=>$distanciaMin,'distancia_km'=>$distanciaKm);
				}	
			}
		}
	}
	
	header('Content-Type: application/json');
	echo json_encode($dados);
	exit;
});
$app->post('/motoristaonlinemaisproximo', function(){
	
	$motoristas = PerfilMotorista::listAllOn();
	$localizacao_passageiro = isset($_POST['localizacao_passageiro'])?$_POST['localizacao_passageiro']:0;
	
	$tempo_menor = 3600;
	foreach ($motoristas as $value) {
	  // Add to XML document node
	  if($localizacao_passageiro == 0 OR $localizacao_passageiro == ''){
	  	$dados[] = array('matricula'=>$value['matricula'],'apelido'=>$value['apelido'],'localizacao_actual'=>$value['localizacao_actual']);
	  }else{
	  	if($value['localizacao_actual'] != ''){
		  	$localizacao_actualp = explode(",", $localizacao_passageiro);
		  	$localizacao_actualm = explode(",", $value['localizacao_actual']);
		  	$distanciaKm = number_format(Geolocalizacao::distanciaKm($localizacao_actualp[0],$localizacao_actualp[1],$localizacao_actualm[0],$localizacao_actualm[1]),2,'.','');
		  	$distanciaMin = intval((($distanciaKm / 70)*60));
		  	if($distanciaMin < $tempo_menor){
		  		$tempo_menor = $distanciaMin;
		  	}
		}
	  }
	}
	header('Content-Type: application/json');
	echo json_encode(array('tempo'=>$tempo_menor));
	exit;
});
//API
//PASSAGEIRO ===================================================================
$app->post('/passageiroapi/login', function(){
	$usuario = isset($_POST['usuario'])?filter_var($_POST['usuario'], FILTER_SANITIZE_STRING):'';
	$senha = isset($_POST['senha'])?filter_var($_POST['senha'], FILTER_SANITIZE_STRING):'';
	$dados = PerfilPassageiro::login($usuario,$senha);
	$chave_publica = random_int(1000000000000000,9999999999999999);
	if($dados['retorno']==1){
		$dados['perfil_passageiro']['id']= Criptografia::criptografar($dados['perfil_passageiro']['id'],$chave_publica);
	}
	$dados['chave_publica'] = $chave_publica;
	unset($dados['perfil_passageiro']['senha']);
	header('Content-Type: application/json');
	echo json_encode($dados);
	exit;
});
$app->post('/passageiroapi/cadastro', function(){
	$retorno = array();
	//limpeza
	$_POST['nome'] = isset($_POST['nome'])?filter_var($_POST['nome'], FILTER_SANITIZE_STRING):'';
	$_POST['apelido'] = isset($_POST['apelido'])?filter_var($_POST['apelido'], FILTER_SANITIZE_STRING):'';
	$_POST['telefone'] = isset($_POST['telefone'])?filter_var($_POST['telefone'], FILTER_SANITIZE_NUMBER_INT):'';
	$_POST['email'] = isset($_POST['email'])?filter_var($_POST['email'], FILTER_SANITIZE_EMAIL):'';
	$senha = isset($_POST['senha'])?filter_var($_POST['senha'], FILTER_SANITIZE_STRING):'';
    $_POST['senha'] = password_hash($senha, PASSWORD_DEFAULT, array("cost"=>12));
	$_POST['cod_pais'] =  isset($_POST['cod_pais'])?filter_var($_POST['cod_pais'], FILTER_SANITIZE_NUMBER_INT):'244'; 
	$_POST['localizacao_actual'] =  isset($_POST['localizacao_actual'])?filter_var($_POST['localizacao_actual'], FILTER_SANITIZE_NUMBER_INT):''; 

	$verifytel = PerfilPassageiro::searchByTel($_POST['telefone']);
	$verifygeneral ='';
	if($verifytel['retorno']){
		
		if($verifytel['retorno']){
        	$verifygeneral .= ' {telefone}';
		}
		$retorno = array('perfil_passageiro'=>'','retorno'=>0,'msg'=>'O(s) dado(s):'.$verifygeneral.' já foram registado(s).');
	}
	else if($_POST['nome'] != '' AND $_POST['apelido'] != '' AND $_POST['telefone'] != ''){
		$perfil_passageiro = new PerfilPassageiro();
		$perfil_passageiro->setValues($_POST);
		$id = $perfil_passageiro->save();

		$info_passageiro = new InfoPassageiro();
		$info_passageiro->setValue('passageiro',$id);
		$info_passageiro->setValue('localizacao_actual',$_POST['localizacao_actual']);
		$info_passageiro->save();
		
		$retorno_passageiro = new PerfilPassageiro();
		$retorno_passageiro->searchById($id);
		$chave_publica = random_int(1000000000000000,9999999999999999);;
		$idcifrado= Criptografia::criptografar($retorno_passageiro->getValue('id'),$chave_publica);
		$retorno_passageiro->setValue('id',$idcifrado);
		$retorno = array('perfil_passageiro'=>$retorno_passageiro->getValues(),'retorno'=>1,'msg'=>'Dados cadastrados com sucesso','chave_publica'=>$chave_publica);
		unset($retorno['perfil_passageiro']['senha']);
	}
	echo json_encode($retorno);
	header('Content-Type: application/json');
	exit;
});
$app->post('/passageiroapi/actualizar', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$passageiro = new PerfilPassageiro();
		$passageiro->searchById($num);
		$passageiro->setValues($_POST);
		$passageiro->update();
        $passageiro->setValue('id',$numcriptografado);
		$retorno = array('perfil_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		unset($retorno['perfil_passageiro']['senha']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/dados', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$passageiro = new PerfilPassageiro();
		$passageiro->searchById($num);
		if(!empty($passageiro->getValues())){
			$info_passageiro = new InfoPassageiro();
			$info_passageiro->searchById($num);
	        $passageiro->setValue('id',$numcriptografado);
	        $avaliacao = PerfilPassageiro::avaliacao($num);
			$retorno = array('perfil_passageiro'=>$passageiro->getValues(),'info_passageiro'=>$info_passageiro->getValues(), 'avaliacao'=>number_format($avaliacao['avaliacao'],2,'.',''),'retorno'=>1,'msg'=>'Dados encontrados com sucesso','chave_publica'=>$_POST['chave_publica']);
			unset($retorno['perfil_passageiro']['senha']);
	    }else{
	    	$retorno = array('perfil_passageiro'=>'','info_passageiro'=>'','retorno'=>0,'msg'=>'Dados não encontrados!','chave_publica'=>$_POST['chave_publica']);
	    }
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
//Apagar conta
$app->post('/passageiroapi/conta/excluir', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$passageiro = new PerfilPassageiro();
		$passageiro->searchById($num);
		$passageiro->setValue('status_cadastro',-1);
		$passageiro->update();
		$retorno = array('retorno'=>1,'msg'=>'Usuario excluído com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/localizacao/enviar', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$passageiro = new InfoPassageiro();
		$passageiro->searchById($num);
		$passageiro->setValue('localizacao_actual',$_POST['localizacao_actual']);
		$passageiro->update();
		$historico_localizacao = new HistoricoLocalizacao();
		$historico_localizacao->setValue('passageiro',$num);
		$historico_localizacao->setValue('localizacao',$_POST['localizacao_actual']);
		$historico_localizacao->savePassageiro();
		$retorno = array('msg'=>'Localização gravada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/senha', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
        $passageiro = new PerfilPassageiro();
		$retorno = $passageiro->changePassword($num,$_POST['antigasenha'],$_POST['novasenha']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/passageiroapi/activar/passo1', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
        $passageiro = new PerfilPassageiro();
        $passageiro->searchById($num);
		$retorno = $passageiro->confirmTelephoneStep1($num);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/passageiroapi/activar/passo2', function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['codigo'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
        $passageiro = new PerfilPassageiro();
        $passageiro->searchById($num);
		$retorno = $passageiro->confirmTelephoneStep2($num,$_POST['codigo']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/passageiroapi/reset/passo1', function(){
	if(isset($_POST['telefone'])){
        $passageiro = new PerfilPassageiro();
        $retorno_passageiro = PerfilPassageiro::searchByTel($_POST['telefone']);
        if($retorno_passageiro['retorno']){
			$retorno = $passageiro->Reset1($retorno_passageiro['dados']['id'],$_POST['telefone']);
		}else{
			$retorno = array('retorno'=>0,'msg'=>"Número inválido");
		}
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/passageiroapi/reset/passo2', function(){
	if(isset($_POST['telefone']) AND isset($_POST['codigo'])){
        $passageiro = new PerfilPassageiro();
        $retorno_passageiro = PerfilPassageiro::searchByTel($_POST['telefone']);
		$remontadorDoCodigo= $_POST['codigo'].$_POST['telefone'].'11';
		$retorno = $passageiro->Reset2($retorno_passageiro['dados']['id'], $remontadorDoCodigo);
		if($retorno['retorno']){
			$chave_publica = random_int(1000000000000000,9999999999999999);
		    $idcifrado= Criptografia::criptografar($retorno_passageiro['dados']['id'],$chave_publica);
	        $retorno_passageiro['dados']['id'] = $idcifrado;
			$retorno = array('perfil_passageiro'=>$retorno_passageiro['dados'],'retorno'=>1,'msg'=>'Dados encontrados com sucesso','chave_publica'=>$chave_publica);
		}
	}else{
	    	$retorno = array('retorno'=>0,'msg'=>"Dados obrigatórios não passados.");
	    }
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
});
$app->post('/passageiroapi/reset/passo3', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		if(isset($_POST['novasenha1']) AND isset($_POST['novasenha2']) AND ($_POST['novasenha1'] === $_POST['novasenha2'])){
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$senha = password_hash($_POST['novasenha1'], PASSWORD_DEFAULT, array("cost"=>12));
        $passageiro = new PerfilPassageiro();
		$retorno = $passageiro->changePassword2($num,$senha);
        }else{
        $retorno = array('retorno'=>0,'msg'=>'As senhas inseridas não coinscidem.');
        }
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/passageiroapi/alterarfoto', function(){
	if(isset($_POST['chave_publica']) AND !empty($_FILES['foto']['tmp_name']) && !empty($_FILES['foto']['name'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadImg::upload('img/passageiro/','foto');
        $passageiro = new PerfilPassageiro();
		$passageiro->searchById($num);
		$retorno = $passageiro->updateImg($_POST['foto']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }else{
    	echo "Imagem não enviada";
    }
});
//locais
$app->post('/passageiroapi/locais/cadastro',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = new LocaisPassageiro();
		$passageiro->setValues($_POST);
		$passageiro->setValue('passageiro',$num);
		$passageiro->save();
        $passageiro->setValue('passageiro',$numcriptografado);
		$retorno = array('local_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/locais/atualizar',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = new LocaisPassageiro();
		$passageiro->searchById($_POST['id']);
		$passageiro->setValues($_POST);
		$passageiro->update();
        $passageiro->setValue('passageiro',$numcriptografado);
		$retorno = array('local_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/locais/remover',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = new LocaisPassageiro();
		$passageiro->searchById($_POST['id']);
		$passageiro->setValues($_POST);
		$passageiro->delete();
        $passageiro->setValue('passageiro',$numcriptografado);
		$retorno = array('local_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados removidos com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/locais/ver-todos',function(){
	if(isset($_POST['chave_publica'])){ 
		$passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
        $local = isset($_GET['local'])?$_GET['local']:'outros';
		$passageiro = LocaisPassageiro::listAll($passageiro);
		$retorno = array('locais_passageiro'=>$passageiro,'retorno'=>1,'msg'=>'Dados encontrados com sucesso','total'=>count($passageiro),'chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/locais/ver',function(){
	if(isset($_POST['chave_publica'])){ 
		$passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
        $local = isset($_GET['local'])?$_GET['local']:'outros';
		$passageiro = LocaisPassageiro::searchByPlace($passageiro,$local);
		$retorno = array('locais_passageiro'=>$passageiro,'retorno'=>1,'msg'=>'Dados encontrados com sucesso','total'=>count($passageiro),'chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
//emergencia
$app->post('/passageiroapi/emergencia/cadastro',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = new EmergenciaPassageiro();
		$passageiro->setValues($_POST);
		$passageiro->setValue('passageiro',$num);
		$passageiro->save();
        $passageiro->setValue('passageiro',$numcriptografado);
		$retorno = array('emergencia_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/emergencia/remover',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = new EmergenciaPassageiro();
		$passageiro->searchById($_POST['id']);
		$passageiro->setValues($_POST);
		$passageiro->delete();
        $passageiro->setValue('passageiro',$numcriptografado);
		$retorno = array('emergencia_passageiro'=>$passageiro->getValues(),'retorno'=>1,'msg'=>'Dados removidos com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/emergencia/ver',function(){
	if(isset($_POST['chave_publica'])){
		$passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		$passageiro = EmergenciaPassageiro::listAll($passageiro);
		$retorno = array('emergencia_passageiro'=>$passageiro,'retorno'=>1,'msg'=>'Dados encontrados com sucesso','total'=>count($passageiro),'chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/listar/viagens-recentes', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$d1 = date('Y-m-d H:i:s',strtotime(date("Y-m-d")."-1 week")); 
		$d2 = date('Y-m-d H:i:s');
		$viagens = PerfilPassageiro::getViagem($d1,$d2,$num);
		$valor_total = 0;
		foreach ($viagens as $value) {
			$valor_total = $valor_total + $value['valor'];
		}
		$retorno = array('viagens'=>$viagens,'valor_total'=>$valor_total);
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/listar/viagens', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$d1 = $_POST['data1']; 
		$d2 = $_POST['data2'];
		$viagens = PerfilPassageiro::getViagem($d1,$d2,$num);
		$valor_total = 0;
		foreach ($viagens as $value) {
			$valor_total = $valor_total + $value['valor'];
		}
		$retorno = array('viagens'=>$viagens,'valor_total'=>$valor_total);
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/passageiroapi/viagem/actualizar', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$viagem = new Viagens();
		$viagem->searchByPedido($_POST['pedido']);
		$viagem->setValues($_POST); 
		$viagem->update();
		$retorno = array('viagem'=>$viagem->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/passageiroapi/pedido/status-passageiro', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$pedido = new Pedidos();
		$pedido->searchById($_POST['pedido']);
		$pedido->setValue('status_passageiro', $_POST['status_passageiro']); 
		$pedido->update();
		$retorno = array('pedido'=>$pedido->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('retorno'=>0, 'msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/passageiroapi/carteira/carregar', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND !empty($_FILES['comprovativo']['tmp_name']) && !empty($_FILES['comprovativo']['name'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadImg::upload('img/carregamento_carteira/','comprovativo');
		$carteira = new Carteira();
		$carteira->setValue('passageiro',$num);
		$carteira->setValue('status',1);
		$carteira->setValues($_POST);
		$carteira->save();
		$carregamentos = Carteira::getCarregamentos($num);
		$retorno = array('carregamentos'=>$carregamentos, 'retorno'=>1, 'msg'=>'Dados registados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/passageiroapi/carteira/consultar-saldo', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$carregamentos = Carteira::getCarregamentos($num);
		$aprovados = 0;
		$revisao = 0;
		$rejeitados = 0;
		foreach($carregamentos as $valor){
			if($valor['status'] == 1){
				$revisao = $revisao + $valor['valor'];
			}
			if($valor['status'] == 2){
				$aprovados = $aprovados + $valor['valor'];
			}
			if($valor['status'] == 0){
				$rejeitados = $rejeitados + $valor['valor'];
			}
		}
		$todos = $revisao + $aprovados + $rejeitados;
		$saldo = Carteira::getSaldo($num);
		$retorno = array('carregamentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'todos' => number_format($todos, 2, '.', ''), 'retorno'=>1, 'revisao'=> number_format($revisao, 2, '.', ''), 'aprovados'=> number_format($aprovados, 2, '.', ''), 'rejeitados'=> number_format($rejeitados, 2, '.', ''),'msg'=>'Dados encontrados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/passageiroapi/carteira/consultar-pagamento', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$pagamentos = PagamentoCarteira::getPagamentoPassageiro($num);
		$aprovados = 0;
		$revisao = 0;
		$rejeitados = 0;
		foreach($pagamentos as $valor){
			if($valor['status'] == 1){
				$revisao = $revisao + $valor['valor'];
			}
			if($valor['status'] == 2){
				$aprovados = $aprovados + $valor['valor'];
			}
			if($valor['status'] == 0){
				$rejeitados = $rejeitados + $valor['valor'];
			}
		}
		$todos = $revisao + $aprovados + $rejeitados;
		$retorno = array('retorno'=>1, 'pagamentos'=>$pagamentos,'todos' => number_format($todos, 2, '.', ''), 'revisao'=> number_format($revisao, 2, '.', ''), 'aprovados'=> number_format($aprovados, 2, '.', ''), 'rejeitados'=> number_format($rejeitados, 2, '.', ''),'msg'=>'Dados encontrados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
//MOTORISTA ====================================================================
$app->post('/motoristaapi/login', function(){
	$usuario = isset($_POST['usuario'])?filter_var($_POST['usuario'], FILTER_SANITIZE_STRING):'';
	$senha = isset($_POST['senha'])?filter_var($_POST['senha'], FILTER_SANITIZE_STRING):'';
	$dados = PerfilMotorista::login($_POST['usuario'],$_POST['senha']);
	$chave_publica = random_int(1000000000000000,9999999999999999);
	if($dados['retorno']==1){
		$dados['perfil_motorista']['id']= Criptografia::criptografar($dados['perfil_motorista']['id'],$chave_publica);
	}
	$dados['chave_publica'] = $chave_publica;
	unset($dados['perfil_motorista']['senha']);
	header('Content-Type: application/json');
	echo json_encode($dados);
	exit;
});
$app->post('/motoristaapi/cadastro', function(){
	$retorno = array();
	//limpeza
	$_POST['nome'] = isset($_POST['nome'])?filter_var($_POST['nome'], FILTER_SANITIZE_STRING):'';
	$_POST['apelido'] = isset($_POST['apelido'])?filter_var($_POST['apelido'], FILTER_SANITIZE_STRING):'';
	$_POST['telefone'] = isset($_POST['telefone'])?filter_var($_POST['telefone'], FILTER_SANITIZE_NUMBER_INT):'';
	$_POST['email'] = isset($_POST['email'])?filter_var($_POST['email'], FILTER_SANITIZE_EMAIL):'';
	$senha = isset($_POST['senha'])?filter_var($_POST['senha'], FILTER_SANITIZE_STRING):'';
    $_POST['senha'] = password_hash($senha, PASSWORD_DEFAULT, array("cost"=>12));
	$_POST['cod_pais'] =  isset($_POST['cod_pais'])?filter_var($_POST['cod_pais'], FILTER_SANITIZE_NUMBER_INT):'244'; 
	$_POST['localizacao_actual'] =  isset($_POST['localizacao_actual'])?filter_var($_POST['localizacao_actual'], FILTER_SANITIZE_NUMBER_INT):''; 
	
	$verifytel = PerfilMotorista::searchByTel($_POST['telefone']);
	$verifygeneral ='';
	if($verifytel['retorno']){
		
		if($verifytel['retorno']){
        	$verifygeneral .= ' {telefone}';
		}
		$retorno = array('perfil_motorista'=>'','retorno'=>0,'msg'=>'O(s) dado(s):'.$verifygeneral.' já foram registado(s).');
	}
	else if($_POST['nome'] != '' AND $_POST['apelido'] != '' AND $_POST['telefone'] != ''){
		$perfil_motorista = new PerfilMotorista();
		$perfil_motorista->setValues($_POST);
		$id = $perfil_motorista->save();

		$saldo_motorista = new SaldoMotorista();
		$saldo_motorista->setValue('motorista', $id);
		$saldo_motorista->setValue('saldo', 5000);
		$saldo_motorista->save();

		$info_motorista = new InfoMotorista();
		$info_motorista->setValue('motorista',$id);
		$info_motorista->setValue('localizacao_actual',$_POST['localizacao_actual']);
		$info_motorista->save();

		$docs_motorista = new DocsMotorista();
		$docs_motorista->setValue('motorista',$id);
		$docs_motorista->save();

		$retorno_motorista = new PerfilMotorista();
		$retorno_motorista->searchById($id);
		$chave_publica = random_int(1000000000000000,9999999999999999);;
		$idcifrado= Criptografia::criptografar($retorno_motorista->getValue('id'),$chave_publica);
		$retorno_motorista->setValue('id',$idcifrado);
		$retorno = array('perfil_motorista'=>$retorno_motorista->getValues(),'retorno'=>1,'msg'=>'Dados cadastrados com sucesso','chave_publica'=>$chave_publica);
		unset($retorno['perfil_motorista']['senha']);
	}
	echo json_encode($retorno);
	header('Content-Type: application/json');
	exit;
});
$app->post('/motoristaapi/dados', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$info_motorista = new InfoMotorista();
		$info_motorista->searchById($num);
		$docs_motorista = new DocsMotorista();
		$docs_motorista->searchById($num);
		if(!empty($motorista->getValues())){
	        $motorista->setValue('id',$numcriptografado);
	        $avaliacao = PerfilMotorista::avaliacao($num);
			$retorno = array('perfil_motorista'=>$motorista->getValues(),'info_motorista'=>$info_motorista->getValues(),'docs_motorista'=>$docs_motorista->getValues(),'avaliacao'=>number_format($avaliacao['avaliacao'],2,'.',''),'retorno'=>1,'msg'=>'Dados encontrados com sucesso','chave_publica'=>$_POST['chave_publica']);
			unset($retorno['perfil_motorista']['senha']);
	    }else{
	    	$retorno = array('perfil_motorista'=>'','retorno'=>0,'msg'=>'Dados não encontrados!','chave_publica'=>$_POST['chave_publica']);
	    }
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/app/atualizar', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$info_motorista = new InfoMotorista();
		$info_motorista->searchById($num);
		$info_motorista->setValue('status_viagem', 0);
		$info_motorista->update();
		
		if(!empty($motorista->getValues())){
	        $motorista->setValue('id',$numcriptografado);
			$retorno = array('perfil_motorista'=>$motorista->getValues(),'info_motorista'=>$info_motorista->getValues(),'retorno'=>1,'msg'=>'Dados encontrados com sucesso','chave_publica'=>$_POST['chave_publica']);
			unset($retorno['perfil_motorista']['senha']);
	    }else{
	    	$retorno = array('perfil_motorista'=>'','retorno'=>0,'msg'=>'Dados não encontrados!','chave_publica'=>$_POST['chave_publica']);
	    }
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/actualizar/perfil', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$motorista->setValues($_POST);
		$motorista->update();
        $motorista->setValue('id',$numcriptografado);
		$retorno = array('perfil_motorista'=>$motorista->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		unset($retorno['perfil_motorista']['senha']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/actualizar/info', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new InfoMotorista(); 
		$motorista->searchById($num);
		$motorista->setValues($_POST);
		$motorista->update();
        $motorista->setValue('id',$numcriptografado);
		$retorno = array('info_motorista'=>$motorista->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
//Apagar conta
$app->post('/motoristaapi/conta/excluir', function(){
	
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$motorista->setValue('status_cadastro',-1);
		$motorista->update();
		$retorno = array('retorno'=>1,'msg'=>'Usuário excluído com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	
	}
});
$app->post('/motoristaapi/localizacao/enviar', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$motorista = new InfoMotorista();
		$motorista->searchById($num);
		$motorista->setValue('localizacao_actual',$_POST['localizacao_actual']);
		$motorista->update();
		$historico_localizacao = new HistoricoLocalizacao();
		$historico_localizacao->setValue('motorista',$num);
		$historico_localizacao->setValue('localizacao',$_POST['localizacao_actual']);
		$historico_localizacao->saveMotorista();
		$retorno = array('msg'=>'Localização gravada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/actualizar/:campo', function($campo){
	if(isset($_POST['chave_publica']) AND !empty($_FILES[$campo]['tmp_name']) && !empty($_FILES[$campo]['name'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadImg::upload('img/docsmotorista/',$campo);
		$motorista = new DocsMotorista();
		$motorista->searchById($num);
		$motorista->setValues($_POST);
		$retorno = $motorista->updateImg($campo,$_POST[$campo]);
        $motorista->setValue('id',$numcriptografado);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/senha', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
        $motorista = new PerfilMotorista();
		$retorno = $motorista->changePassword($num,$_POST['antigasenha'],$_POST['novasenha']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/motoristaapi/reset/passo1', function(){
	if(isset($_POST['telefone'])){
        $motorista = new PerfilMotorista();
        $retorno_motorista = PerfilMotorista::searchByTel($_POST['telefone']);
        if($retorno_motorista['retorno']){
			$retorno = $motorista->Reset1($retorno_motorista['dados']['id'],$_POST['telefone']);
		}else{
			$retorno = array('retorno'=>0,'msg'=>"Número inválido");
		}
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/motoristaapi/reset/passo2', function(){
	if(isset($_POST['telefone']) AND isset($_POST['codigo'])){
        $motorista = new PerfilMotorista();
        $retornoMotorista = $motorista->searchByTel($_POST['telefone']);
		$remontadorDoCodigo= $_POST['codigo'].$_POST['telefone'].'11';
		$retorno = $motorista->Reset2($retornoMotorista['dados']['id'], $remontadorDoCodigo);
		if($retorno['retorno']){
			$chave_publica = random_int(1000000000000000,9999999999999999);
		    $retornoMotorista['dados']['id']= Criptografia::criptografar($retornoMotorista['dados']['id'],$chave_publica);
			$retorno = array('perfil_motorista'=>$retornoMotorista['dados'],'retorno'=>1,'msg'=>'Dados encontrados com sucesso','chave_publica'=>$chave_publica);
		}
	}else{
	    	$retorno = array('retorno'=>0,'msg'=>"Dados obrigatórios não passados.");
	    }
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
});
$app->post('/motoristaapi/reset/passo3', function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		if(isset($_POST['novasenha1']) AND isset($_POST['novasenha2']) AND ($_POST['novasenha1'] === $_POST['novasenha2'])){
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$senha = password_hash($_POST['novasenha1'], PASSWORD_DEFAULT, array("cost"=>12));
        $motorista = new PerfilMotorista();
		$retorno = $motorista->changePassword2($num,$senha);
        }else{
        $retorno = array('retorno'=>0,'msg'=>'As senhas inseridas não coinscidem.');
        }
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }
});
$app->post('/motoristaapi/alterarfoto', function(){
	if(isset($_POST['chave_publica']) AND !empty($_FILES['foto']['tmp_name']) && !empty($_FILES['foto']['name'])){
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadImg::upload('img/motorista/','foto');
        $motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$retorno = $motorista->updateImg($_POST['foto']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
    }else{
    	echo "Imagem não enviada";
    }
});
//emergencia motorista
//emergencia
$app->post('/motoristaapi/emergencia/cadastro',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
		$motorista = new EmergenciaMotorista();
		$motorista->setValues($_POST);
		$motorista->setValue('motorista',$num);
		$motorista->save();
        $motorista->setValue('motorista',$numcriptografado);
		$retorno = array('emergencia_passageiro'=>$motorista->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/emergencia/remover',function(){
	if(isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
		$motorista = new EmergenciaMotorista();
		$motorista->searchById($_POST['id']);
		$motorista->setValues($_POST);
		$motorista->delete();
        $motorista->setValue('motorista',$numcriptografado);
		$retorno = array('emergencia_motorista'=>$motorista->getValues(),'retorno'=>1,'msg'=>'Dados removidos com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/emergencia/ver',function(){
	if(isset($_POST['chave_publica'])){
		$motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
		$motoristas = EmergenciaMotorista::listAll($motorista);
		$retorno = array('emergencia_motorista' => $motoristas, 'retorno'=> 1 , 'msg' => 'Dados encontrados com sucesso','total'=> count($motoristas),'chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/notificacoes/ver',function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
		$motorista = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$notificacoes_motorista = NotificacaoMotorista::listAll($motorista);
		$retorno = array('notificacoes_motorista'=>$notificacoes_motorista,'retorno'=>1,'msg'=>'Dados encontrados com sucesso','total'=>count($notificacoes_motorista),'chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/notificacoes/apagar',function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
		$motorista = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id']; 
		unset($_POST['id']);
		$notificacoesMotorista = new NotificacaoMotorista();
		$notificacoesMotorista->deleteAll($motorista);
		$retorno = array('retorno'=>1,'msg'=>'Dados eliminados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/chamada/ver', function () {
    if (isset($_POST['chave_publica']) && isset($_POST['id'])) {
        $motorista = Criptografia::descriptografar($_POST['id'], $_POST['chave_publica']);
        
        $notificacoes_motorista = NotificacaoMotorista::getPedido((int) $motorista);
        
        if (!empty($notificacoes_motorista)) {
            $minuto = strtotime(date('Y-m-d H:i:s')) - strtotime($notificacoes_motorista[0]['data']);
            if (date('i', $minuto) > 90) {
                $notificacoes_motorista = [];
            }
        }
        
        $retorno = [
            'notificacoes_motorista' => $notificacoes_motorista,
            'retorno' => 1,
            'msg' => 'Dados encontrados com sucesso',
            'total' => count($notificacoes_motorista),
            'chave_publica' => $_POST['chave_publica']
        ];
        
        header('Content-Type: application/json');
        echo json_encode($retorno);
        exit;
    }
});

$app->post('/motoristaapi/listar/tarifa', function(){
    if(isset($_POST['chave_publica']) && isset($_POST['id'])){
        $id_motorista = $_POST['id'];

        if (!ctype_digit($id_motorista)) {
            $id_motorista = Criptografia::descriptografar($id_motorista, $_POST['chave_publica']);
        } else {
            $id_motorista = intval($id_motorista);
        }

        // Agora temos um ID válido
        $provincia = isset($_POST['provincia']) ? $_POST['provincia'] : 'Luanda';
        $objtarifa = PerfilMotorista::listTarifa($id_motorista, $provincia);

        $tarifa = array();
        if (!empty($objtarifa)) {
            foreach ($objtarifa as $value) {
                $hora_inicio = strtotime($value['hora_inicial']);
                $hora_final = strtotime($value['hora_final']);
                $hora_actual = strtotime(date('H:i:s'));
                if (($hora_inicio <= $hora_actual) && ($hora_final >= $hora_actual)) {
                    $tarifa[] = $value;
                }
            }
        }

        $retorno = array(
            'id_motorista' => $id_motorista, 
            'tarifa' => $tarifa
        );

        header('Content-Type: application/json');
        echo json_encode($retorno);
        exit;
    }
});


$app->post('/motoristaapi/listar/ganhos-diarios', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$d1 = date('Y-m-d '.'0:0:0');
		$d2 = date('Y-m-d '.'23:59:59');
		$viagens = PerfilMotorista::getGanho($d1,$d2,$num);
		$ganhos=0;
		$comissao=0;
		foreach ($viagens as $value) {
			$ganhos = $ganhos + $value['valor'];
			$comissao = $comissao  + ($value['valor'] * $value['comissao']/100);
		}
		$retorno = array('viagens'=>$viagens,'ganhos'=>$ganhos,'comissao'=>$comissao);
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/listar/ganhos-recentes', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$d1 = date('Y-m-d H:i:s',strtotime(date("Y-m-d")."-1 week")); 
		$d2 = date('Y-m-d H:i:s');
		$viagens = PerfilMotorista::getGanho($d1,$d2,$num); 
		$ganhos=0;
		$comissao=0;
		foreach ($viagens as $value) {
			$ganhos = $ganhos + $value['valor'];
			$comissao = $comissao  + ($value['valor'] * $value['comissao']/100);
		}
		$retorno = array('viagens'=>$viagens, 'ganhos'=>$ganhos,'comissao'=>$comissao);
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/listar/ganhos', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$d1 = $_POST['data1']; 
		$d2 = $_POST['data2'];
		$viagens = PerfilMotorista::getGanho($d1,$d2,$num);
		$ganhos=0;
		$comissao=0;
		foreach ($viagens as $value) {
			$ganhos = $ganhos + $value['valor'];
			$comissao = $comissao  + ($value['valor'] * $value['comissao']/100);
		}
		$retorno = array('viagens'=>$viagens, 'ganhos'=>$ganhos,'comissao'=>$comissao);
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
});
$app->post('/motoristaapi/saldo-carregar', function(){
    //if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND !empty($_FILES['comprovativo']['tmp_name']) && !empty($_FILES['comprovativo']['name'])){
		if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	      
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		//UploadImg::upload('img/saldo_motorista/','comprovativo');
		$movsaldomotorista = new MovimentoSaldoMotorista();
		$movsaldomotorista->setValue('motorista',$num);
		$movsaldomotorista->setValue('comprovativo','img/sem imagem.jpg');
		$movsaldomotorista->setValue('tipo_comprovativo','Nulo');
		$movsaldomotorista->setValue('status',1);
		$movsaldomotorista->setValue('data_aceite', null);
		$movsaldomotorista->setValue('aceite_por', null);
		$movsaldomotorista->setValue('tipo_movimento', 'Crédito');
		$movsaldomotorista->setValue('data_movimento', date('Y-m-d H:i:s'));
		$movsaldomotorista->setValues($_POST);
		$movsaldomotorista->save();
		$carregamentos = SaldoMotorista::getCarregamentos($num);
		$saldo = SaldoMotorista::getSaldo($num);
		$retorno = array('movimentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'retorno'=>1, 'msg'=>'Dados registados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/motoristaapi/saldo-carregar-img', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND !empty($_FILES['comprovativo']['tmp_name']) && !empty($_FILES['comprovativo']['name'])){
			      
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadImg::upload('img/saldo_motorista/','comprovativo');
		$movsaldomotorista = new MovimentoSaldoMotorista();
		$movsaldomotorista->searchByIdMotorista($_POST['id_movimento'], $num);
		$movsaldomotorista->setValue('comprovativo', $_POST['comprovativo']);
		$movsaldomotorista->setValue('tipo_comprovativo','Img');
		$movsaldomotorista->update();
		$carregamentos = SaldoMotorista::getCarregamentos($num);
		$saldo = SaldoMotorista::getSaldo($num);
		$retorno = array('movimentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'retorno'=>1, 'msg'=>'Dados registados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/motoristaapi/saldo-carregar-pdf', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND !empty($_FILES['comprovativo']['tmp_name']) && !empty($_FILES['comprovativo']['name'])){
			      
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		UploadPdf::upload('documentos/saldo_motorista/','comprovativo');
		$movsaldomotorista = new MovimentoSaldoMotorista();
		$movsaldomotorista->searchByIdMotorista($_POST['id_movimento'], $num);
		$movsaldomotorista->setValue('comprovativo', $_POST['comprovativo']);
		$movsaldomotorista->setValue('tipo_comprovativo','Pdf');
		$movsaldomotorista->update();
		$carregamentos = SaldoMotorista::getCarregamentos($num);
		$saldo = SaldoMotorista::getSaldo($num);
		$retorno = array('movimentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'retorno'=>1, 'msg'=>'Dados registados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/motoristaapi/saldo-extrato', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);

		$de = (isset($_POST['de'])) ? $_POST['de'] : date('Y-m-d H:i:s',strtotime(date("Y-m-d")."-1 week"));
		$ate = (isset($_POST['ate'])) ? $_POST['ate'] : date('Y-m-d H:i:s');

		$carregamentos = SaldoMotorista::getMovimentosComData($num, $de, $ate);
		$aprovados = 0;
		$revisao = 0;
		$rejeitados = 0;
		foreach($carregamentos as $valor){
			if($valor['status'] == 1){
				$revisao = $revisao + $valor['valor'];
			}
			if($valor['status'] == 2){
				$aprovados = $aprovados + $valor['valor'];
			}
			if($valor['status'] == 0){
				$rejeitados = $rejeitados + $valor['valor'];
			}
		}
		$todos = $revisao + $aprovados + $rejeitados;
		$saldo = SaldoMotorista::getSaldo($num);
		$retorno = array('movimentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'todos' => number_format($todos, 2, '.', ''), 'retorno'=>1, 'revisao'=> number_format($revisao, 2, '.', ''), 'aprovados'=> number_format($aprovados, 2, '.', ''), 'rejeitados'=> number_format($rejeitados, 2, '.', ''),'msg'=>'Dados encontrados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/motoristaapi/saldo', function(){
    if(isset($_POST['chave_publica']) AND isset($_POST['id'])){
	    $num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$carregamentos = SaldoMotorista::getCarregamentos($num);
		$aprovados = 0;
		$revisao = 0;
		$rejeitados = 0;
		foreach($carregamentos as $valor){
			if($valor['status'] == 1){
				$revisao = $revisao + $valor['valor'];
			}
			if($valor['status'] == 2){
				$aprovados = $aprovados + $valor['valor'];
			}
			if($valor['status'] == 0){
				$rejeitados = $rejeitados + $valor['valor'];
			}
		}
		$todos = $revisao + $aprovados + $rejeitados;
		$saldo = SaldoMotorista::getSaldo($num);
		$retorno = array('movimentos'=>$carregamentos, 'saldo'=> number_format($saldo, 2, '.', ''), 'todos' => number_format($todos, 2, '.', ''), 'retorno'=>1, 'revisao'=> number_format($revisao, 2, '.', ''), 'aprovados'=> number_format($aprovados, 2, '.', ''), 'rejeitados'=> number_format($rejeitados, 2, '.', ''),'msg'=>'Dados encontrados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});

//Corrida ================================================
$app->get('/corridaapi/listar/viaturas', function(){
	$provincia = isset($_GET['provincia'])?$_GET['provincia']:"Luanda";
	//Luanda
	if( 
		strtolower($provincia) == strtolower('Luanda Province') || 
		strtolower($provincia) == strtolower('Luanda') ||
		strtolower($provincia) == strtolower('Ingombota') ||
		strtolower($provincia) == strtolower('Maianga') || 
		strtolower($provincia) == strtolower('Kilamba-Kiaxi') ||
		strtolower($provincia) == strtolower('Rangel') ||
		strtolower($provincia) == strtolower('Samba') || 
		strtolower($provincia) == strtolower('Sambizanga') ||
		strtolower($provincia) == strtolower('Neves Bendinha') ||
		strtolower($provincia) == strtolower('Ngola Kiluanje') ||
		strtolower($provincia) == strtolower('Belas') || 
		strtolower($provincia) == strtolower('Talatona') ||
		strtolower($provincia) == strtolower('Kilamba') || 
		strtolower($provincia) == strtolower('Benfica') ||
		strtolower($provincia) == strtolower('Barra do Kwanza') ||
		strtolower($provincia) == strtolower('Mussulo') || 
		strtolower($provincia) == strtolower('Cazenga') || 
		strtolower($provincia) == strtolower('Sofene') ||
		strtolower($provincia) == strtolower('Tala Hady') || 
		strtolower($provincia) == strtolower('Hoji Ya Henda') ||
		strtolower($provincia) == strtolower('Kima Kieza') ||
		strtolower($provincia) == strtolower('Kalawenda') ||
		strtolower($provincia) == strtolower('Cacuaco') ||
		strtolower($provincia) == strtolower('Mulenvos de Baixo') ||
		strtolower($provincia) == strtolower('Sequele') ||
		strtolower($provincia) == strtolower('11 de Novembro') ||
		strtolower($provincia) == strtolower('Cidade Universitária') ||
		strtolower($provincia) == strtolower('Kicolo') || 
		strtolower($provincia) == strtolower('Funda') ||
		strtolower($provincia) == strtolower('Viana') ||
		strtolower($provincia) == strtolower('Mbaia') || 
		strtolower($provincia) == strtolower('Zango') ||
		strtolower($provincia) == strtolower('Cabiri') || 
		strtolower($provincia) == strtolower('Calumbo') ||
		strtolower($provincia) == strtolower('Catete') || 
		strtolower($provincia) == strtolower('Bom Jesus') ||
		strtolower($provincia) == strtolower('Icolo e Bengo') ||
		strtolower($provincia) == strtolower('Catete') ||
		strtolower($provincia) == strtolower('Cassoneca') ||
		strtolower($provincia) == strtolower('Caculo Cahango') || 
		strtolower($provincia) == strtolower('Muxima') ||
		strtolower($provincia) == strtolower('Quiçama') || 
		strtolower($provincia) == strtolower('Demba-Chio') ||
		strtolower($provincia) == strtolower('Mumbondo') || 
		strtolower($provincia) == strtolower('Quixinge') ||
		strtolower($provincia) == strtolower('Cabo Ledo') || 
		strtolower($provincia) == strtolower('Cabu Ledo') ||
		strtolower($provincia) == strtolower('Estalagem') ||
		strtolower($provincia) == strtolower('Kikuxi') ||
		strtolower($provincia) == strtolower('Baía') ||
		strtolower($provincia) == strtolower('Vila Flôr') ||
		strtolower($provincia) == strtolower('Quenguela') ||
		strtolower($provincia) == strtolower('Morro dos Veados') ||
		strtolower($provincia) == strtolower('Ramiros') ||
		strtolower($provincia) == strtolower('Vila Verde ') ||
		strtolower($provincia) == strtolower('Cabolombo') ||
		strtolower($provincia) == strtolower('Kilamba') ||
		strtolower($provincia) == strtolower('Golfe') ||
		strtolower($provincia) == strtolower('Cabolombo ') ||
		strtolower($provincia) == strtolower('Sapú') ||
		strtolower($provincia) == strtolower('Palanca') ||
		strtolower($provincia) == strtolower('Nova Vida') ||
		strtolower($provincia) == strtolower('Futungo de Belas') ||
		strtolower($provincia) == strtolower('Lar do Patriota') ||
		strtolower($provincia) == strtolower('Camama') 
	){
       $provincia = 'Luanda';
	}
	//Huambo
	if( 
		strtolower($provincia) == strtolower('Huambo Province') || 
		strtolower($provincia) == strtolower('Huambo') ||
		strtolower($provincia) == strtolower('Bailundo') ||
		strtolower($provincia) == strtolower('Caála') || 
		strtolower($provincia) == strtolower('Ecunha') ||
		strtolower($provincia) == strtolower('Londuimbale') ||
		strtolower($provincia) == strtolower('Katchiungo') || 
		strtolower($provincia) == strtolower('Tchinjenje') ||
		strtolower($provincia) == strtolower('Mungo') ||
		strtolower($provincia) == strtolower('Ucuma') ||
		strtolower($provincia) == strtolower('Tchicala-Tcholohanga') || 
		strtolower($provincia) == strtolower('Longonjo')  
	){
       $provincia = 'Huambo';
	} 
    $objviaturas = Viaturas::listAllComplete($provincia);
    $viaturas = array();
    $retorno = 0;
    if(!empty($objviaturas)){
    	foreach($objviaturas as $value){
    		$hora_inicio = strtotime($value['hora_inicial']);
    		$hora_final = strtotime($value['hora_final']);
    		$hora_actual = strtotime(date('H:i:s'));
    		if(($hora_inicio <= $hora_actual) && ($hora_final >= $hora_actual)){
    			$viaturas[]= $value;
    		}
    	}
    }
    if(!empty($viaturas)){
    	$retorno = 1;
    }else{
    	$objviaturas = Viaturas::listAllComplete('Huambo');
	    $viaturas = array();
	    $retorno = 0;
	    if(!empty($objviaturas)){
	    	foreach($objviaturas as $value){
	    		$hora_inicio = strtotime($value['hora_inicial']);
	    		$hora_final = strtotime($value['hora_final']);
	    		$hora_actual = strtotime(date('H:i:s'));
	    		if(($hora_inicio <= $hora_actual) && ($hora_final >= $hora_actual)){
	    			$viaturas[]= $value;
	    		}
	    	}
	    }	
    }
    $retorno = array('viaturas'=>$viaturas,'retorno'=>$retorno);
    header('Content-Type: application/json');
	echo json_encode($retorno);
	exit;
});
$app->get('/corridaapi/listar/viaturas/:num', function($num){
	$provincia = isset($_GET['provincia'])?$_GET['provincia']:"Luanda";
	//Luanda
    if( 
		strtolower($provincia) == strtolower('Luanda Province') || 
		strtolower($provincia) == strtolower('Luanda') ||
		strtolower($provincia) == strtolower('Ingombota') ||
		strtolower($provincia) == strtolower('Maianga') || 
		strtolower($provincia) == strtolower('Kilamba-Kiaxi') ||
		strtolower($provincia) == strtolower('Rangel') ||
		strtolower($provincia) == strtolower('Samba') || 
		strtolower($provincia) == strtolower('Sambizanga') ||
		strtolower($provincia) == strtolower('Neves Bendinha') ||
		strtolower($provincia) == strtolower('Ngola Kiluanje') ||
		strtolower($provincia) == strtolower('Belas') || 
		strtolower($provincia) == strtolower('Talatona') ||
		strtolower($provincia) == strtolower('Kilamba') || 
		strtolower($provincia) == strtolower('Benfica') ||
		strtolower($provincia) == strtolower('Barra do Kwanza') ||
		strtolower($provincia) == strtolower('Mussulo') || 
		strtolower($provincia) == strtolower('Cazenga') || 
		strtolower($provincia) == strtolower('Sofene') ||
		strtolower($provincia) == strtolower('Tala Hady') || 
		strtolower($provincia) == strtolower('Hoji Ya Henda') ||
		strtolower($provincia) == strtolower('Kima Kieza') ||
		strtolower($provincia) == strtolower('Kalawenda') ||
		strtolower($provincia) == strtolower('Cacuaco') ||
		strtolower($provincia) == strtolower('Mulenvos de Baixo') ||
		strtolower($provincia) == strtolower('Sequele') ||
		strtolower($provincia) == strtolower('11 de Novembro') ||
		strtolower($provincia) == strtolower('Cidade Universitária') ||
		strtolower($provincia) == strtolower('Kicolo') || 
		strtolower($provincia) == strtolower('Funda') ||
		strtolower($provincia) == strtolower('Viana') ||
		strtolower($provincia) == strtolower('Mbaia') || 
		strtolower($provincia) == strtolower('Zango') ||
		strtolower($provincia) == strtolower('Cabiri') || 
		strtolower($provincia) == strtolower('Calumbo') ||
		strtolower($provincia) == strtolower('Catete') || 
		strtolower($provincia) == strtolower('Bom Jesus') ||
		strtolower($provincia) == strtolower('Icolo e Bengo') ||
		strtolower($provincia) == strtolower('Catete') ||
		strtolower($provincia) == strtolower('Cassoneca') ||
		strtolower($provincia) == strtolower('Caculo Cahango') || 
		strtolower($provincia) == strtolower('Muxima') ||
		strtolower($provincia) == strtolower('Quiçama') || 
		strtolower($provincia) == strtolower('Demba-Chio') ||
		strtolower($provincia) == strtolower('Mumbondo') || 
		strtolower($provincia) == strtolower('Quixinge') ||
		strtolower($provincia) == strtolower('Cabo Ledo') || 
		strtolower($provincia) == strtolower('Cabu Ledo') ||
		strtolower($provincia) == strtolower('Estalagem') ||
		strtolower($provincia) == strtolower('Kikuxi') ||
		strtolower($provincia) == strtolower('Baía') ||
		strtolower($provincia) == strtolower('Vila Flôr') ||
		strtolower($provincia) == strtolower('Quenguela') ||
		strtolower($provincia) == strtolower('Morro dos Veados') ||
		strtolower($provincia) == strtolower('Ramiros') ||
		strtolower($provincia) == strtolower('Vila Verde ') ||
		strtolower($provincia) == strtolower('Cabolombo') ||
		strtolower($provincia) == strtolower('Kilamba') ||
		strtolower($provincia) == strtolower('Golfe ') ||
		strtolower($provincia) == strtolower('Cabolombo ') ||
		strtolower($provincia) == strtolower('Sapú') ||
		strtolower($provincia) == strtolower('Palanca') ||
		strtolower($provincia) == strtolower('Nova Vida') ||
		strtolower($provincia) == strtolower('Futungo de Belas') ||
		strtolower($provincia) == strtolower('Lar do Patriota') ||
		strtolower($provincia) == strtolower('Camama') 
	){
       $provincia = 'Luanda';
	}
	//Huambo
	if( 
		strtolower($provincia) == strtolower('Huambo Province') || 
		strtolower($provincia) == strtolower('Huambo') ||
		strtolower($provincia) == strtolower('Bailundo') ||
		strtolower($provincia) == strtolower('Caála') || 
		strtolower($provincia) == strtolower('Ecunha') ||
		strtolower($provincia) == strtolower('Londuimbale') ||
		strtolower($provincia) == strtolower('Katchiungo') || 
		strtolower($provincia) == strtolower('Tchinjenje') ||
		strtolower($provincia) == strtolower('Mungo') ||
		strtolower($provincia) == strtolower('Ucuma') ||
		strtolower($provincia) == strtolower('Tchicala-Tcholohanga') || 
		strtolower($provincia) == strtolower('Longonjo')  
	){
       $provincia = 'Huambo';
	}
    $objviaturas = Viaturas::listAllComplete1($num,$provincia);
    $viaturas=array();
    $retorno = 0;
    if(!empty($objviaturas)){
    	foreach($objviaturas as $value){
    		$hora_inicio = strtotime($value['hora_inicial']);
    		$hora_final = strtotime($value['hora_final']);
    		$hora_actual = strtotime(date('H:i:s'));
    		if(($hora_inicio <= $hora_actual) && ($hora_final >= $hora_actual)){
    			$viaturas[]= $value;
    		}
    	}
    }
    if(!empty($viaturas)){
    	$retorno = 1;
    }else{
    	$objviaturas = Viaturas::listAllComplete1($num,'Huambo');
	    $viaturas = array();
	    $retorno = 0;
	    if(!empty($objviaturas)){
	    	foreach($objviaturas as $value){
	    		$hora_inicio = strtotime($value['hora_inicial']);
	    		$hora_final = strtotime($value['hora_final']);
	    		$hora_actual = strtotime(date('H:i:s'));
	    		if(($hora_inicio <= $hora_actual) && ($hora_final >= $hora_actual)){
	    			$viaturas[]= $value;
	    		}
	    	}
	    }	
    }
    $retorno = array('viaturas'=>$viaturas,'retorno'=>$retorno);
    header('Content-Type: application/json');
	echo json_encode($retorno);
	exit;
});
$app->get('/corridaapi/listar/viaturas-motorista', function(){
    $viaturas = Viaturas::listAll();
    $retorno = array('viaturas'=>$viaturas);
    header('Content-Type: application/json');
	echo json_encode($retorno);
	exit;
});
$app->get('/corridaapi/listar/config-paragem', function(){
    $config_paragem = new ConfigParagem();
    $config_paragem->searchLast();
    $retorno = array('config_paragem'=>$config_paragem->getValues());
    header('Content-Type: application/json');
	echo json_encode($retorno);
	exit;
});
$app->post('/corridaapi/listar/tempo-paragem', function(){
    if(isset($_POST['viagem'])){
    	$viagem = new Viagens();
		$viagem->searchById($_POST['viagem']);
		$inicio_paragem = !empty($viagem->getValue('inicio_paragem'))?$viagem->getValue('inicio_paragem'):0;
		$fim_paragem = !empty($viagem->getValue('fim_paragem'))?$viagem->getValue('fim_paragem'):0;
		if($inicio_paragem != 0 AND $fim_paragem != 0){
			$tempo_paragem = strtotime($fim_paragem) - strtotime($inicio_paragem);
			$tempo_paragem = date('i', $tempo_paragem);
			$viagem->setValue('tempo_paragem',$tempo_paragem);
			$viagem->update();
		}
		$retorno = array('viagem'=>$viagem->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
	    header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;

	}else{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->get('/corridaapi/listar/fpagamentos', function(){
    $fpagamentos = FormasPagamento::listAll();
    $retorno = array('fpagamentos'=>$fpagamentos);
    header('Content-Type: application/json');
	echo json_encode($retorno);
	exit;
});
// $app->post('/corridaapi/pedido/dados-pedido-save-for-later', function() {
//     if (!isset($_POST['pedido'], $_POST['chave_publica'], $_GET['app'])) {
//         header('Content-Type: application/json');
//         echo json_encode(['msg' => 'Dados obrigatórios não preenchidos!']);
//         exit;
//     }

//     $pedido = new Pedidos();
//     $pedido->searchById($_POST['pedido']);

//     $usuarioID = null;
//     $tipoUsuario = $_GET['app'];
    
//     if ($tipoUsuario === 'passageiro') {
// 		// $passageiro = new PerfilPassageiro();
// 		// $passageiro->searchById("4362");
// 		$usuarioID = "4362";

//         // $usuarioID = Criptografia::descriptografar($_POST['passageiro'], $_POST['chave_publica']);
//     } elseif ($tipoUsuario === 'motorista' && isset($_POST['motorista'])) {
// 		$passageiro = new PerfilPassageiro();
// 		$passageiro->searchById("4362");
//         $usuarioID = Criptografia::descriptografar($_POST['motorista'], $_POST['chave_publica']);
//     }

//     if (!$usuarioID) {
//         header('Content-Type: application/json');
//         echo json_encode(['msg' => 'Usuário inválido!']);
//         exit;
//     }

//     if ($tipoUsuario === 'passageiro') {
//         $motoristaID = '909';
//         if (!empty($motoristaID)) {
//             $motorista = new PerfilMotorista();
//             $motorista->searchById($motoristaID);
//             $infoMotorista = new InfoMotorista();
//             $infoMotorista->searchById($motoristaID);
//             $avaliacao = PerfilMotorista::avaliacao($motoristaID);

//             $retorno = [
//                 'pedido' => $pedido->getValues(),
//                 'motorista' => $motorista->getValues(),
//                 'info_motorista' => $infoMotorista->getValues(),
//                 'avaliacao' => number_format($avaliacao['avaliacao'], 2, '.', ''),
//                 'retorno' => 1,
//                 'msg' => 'Dados encontrados com sucesso!'
//             ];
//         } else {
//             $retorno = [
//                 'pedido' => $pedido->getValues(),
//                 'motorista' => '',
//                 'info_motorista' => '',
//                 'retorno' => 1,
//                 'msg' => 'Dados não encontrados!'
//             ];
//         }
//     } elseif ($tipoUsuario === 'motorista') {
//         $passageiroID = "4362";
//         if (!empty($passageiroID)) {
//             $passageiro = new PerfilPassageiro();
//             $passageiro->searchById($passageiroID);
//             $avaliacao = PerfilPassageiro::avaliacao($passageiroID);

//             $retorno = [
//                 'pedido' => $pedido->getValues(),
//                 'passageiro' => $passageiro->getValues(),
//                 'avaliacao' => number_format($avaliacao['avaliacao'], 2, '.', ''),
//                 'retorno' => 1,
//                 'msg' => 'Dados encontrados com sucesso!'
//             ];
//         } else {
//             $retorno = [
//                 'pedido' => $pedido->getValues(),
//                 'passageiro' => '',
//                 'retorno' => 1,
//                 'msg' => 'Dados não encontrados!'
//             ];
//         }
//     }

//     header('Content-Type: application/json');
//     echo json_encode($retorno);
//     exit;
// });
$app->post('/corridaapi/pedido/dados-pedido', function(){
	if(isset($_POST['pedido']) AND isset($_POST['chave_publica']) AND isset($_GET['app'])){
		
		$pedido = new Pedidos();
		if($_GET['app'] == 'passageiro'){
			$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		   $numcriptografado = $_POST['passageiro'];
		   unset($_POST['passageiro']);
		   $pedido->searchByPassageiro($_POST['pedido'], $num); 
		   if(!empty($pedido->getValue('motorista'))){
			   $motorista = new PerfilMotorista(); 
			   $motorista->searchById($pedido->getValue('motorista'));
			   $info_motorista = new InfoMotorista();
			   $info_motorista->searchById($pedido->getValue('motorista'));
			   $avaliacao = PerfilMotorista::avaliacao($pedido->getValue('motorista'));//estrelas

			   $retorno = array('pedido'=>$pedido->getValues(),'motorista'=>$motorista->getValues(),'info_motorista'=>$info_motorista->getValues(),'avaliacao'=>number_format($avaliacao['avaliacao'],2,'.',''),'retorno'=>1,'msg'=>'Dados encontrados com sucesso!');
		   }else{ 
			   $retorno = array('pedido'=>$pedido->getValues(),'motorista'=>'','info_motorista'=>'','retorno'=>1,'msg'=>'Dados não encontrados!');
		   }
		}
	   if($_GET['app'] == 'motorista'){
		   $num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		   $numcriptografado = $_POST['motorista'];
		   unset($_POST['motorista']);
		   $pedido->searchById($_POST['pedido']); 
		   
		   
		   if(!empty($pedido->getValue('passageiro'))){
			   $passageiro = new PerfilPassageiro();
			   $passageiro->searchById($pedido->getValue('passageiro'));
			   $avaliacao = PerfilPassageiro::avaliacao($pedido->getValue('passageiro'));

			   $retorno = array('pedido'=>$pedido->getValues(),'passageiro'=>$passageiro->getValues(),'avaliacao'=>number_format($avaliacao['avaliacao'],2,'.',''),'retorno'=>1,'msg'=>'Dados encontrados com sucesso!');
		   }else{ 
			   $retorno = array('pedido'=>$pedido->getValues(),'passageiro'=>'','retorno'=>1,'msg'=>'Dados não encontrados !');
		   }
	   }
	   header('Content-Type: application/json');
	   echo json_encode($retorno);
	   exit;
   }
   else
   {
	   header('Content-Type: application/json');
	   echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
	   exit;
   }
});

$app->post('/corridaapi/pedido/cancelar', function(){
    if(isset($_POST['pedido']) AND isset($_POST['passageiro']) AND isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_publica']);
		$numcriptografado = $_POST['passageiro'];
		unset($_POST['passageiro']);
		
		$pedido = new Pedidos();
		$pedido->searchById($_POST['pedido']);
		$pedido->setValue('status',0);
		$pedido->setValue('cancelado_por','Passageiro');
		$motivo = isset($_POST['motivo'])? $_POST['motivo'] : "";
		$pedido->setValue('motivo',$motivo);
		$pedido->update();
		if($pedido->getValue('motorista') != null){
			$info_motorista = new InfoMotorista();
			$info_motorista->searchById($pedido->getValue('motorista'));
			$info_motorista->setValue('status_viagem', 0);
			$info_motorista->update();
		}
		NotificacaoMotorista::updateAll($pedido->getValue('id'),2);
		$retorno = array('pedido'=>$pedido->getValues(),'retorno'=>1,'msg'=>'Pedido cancelado com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/pedido/cancelar-motorista', function(){
    if(isset($_POST['pedido']) AND isset($_POST['motorista']) AND isset($_POST['chave_publica'])){
		$num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
		$pedido = new Pedidos();
		$pedido->searchById($_POST['pedido']);
		$pedido->setValue('status',0);
		$pedido->setValue('cancelado_por','Motorista');
		$motivo = isset($_POST['motivo'])? $_POST['motivo'] : "";
		$pedido->setValue('motivo',$motivo);
		$pedido->update();
		$info_motorista = new InfoMotorista();
		$info_motorista->searchById($num);
		$info_motorista->setValue('status_viagem', 0);
		$info_motorista->update();
		NotificacaoMotorista::updateAll($pedido->getValue('id'),0);
		$retorno = array('pedido'=>$pedido->getValues(),'retorno'=>1,'msg'=>'Pedido cancelado com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});     
$app->post('/corridaapi/pedido/aceitar', function() {
    if (!isset($_POST['pedido'], $_POST['motorista'], $_POST['chave_publica'])) {
        header('Content-Type: application/json');
        echo json_encode(['msg' => 'Dados obrigatórios não preenchidos!']);
        exit;
    }

    $_POST['data_aceite'] = date('Y-m-d H:i:s');
    $_POST['status'] = 2;

    $idMotorista = ctype_digit($_POST['motorista']) 
        ? intval($_POST['motorista']) 
        : Criptografia::descriptografar($_POST['motorista'], $_POST['chave_publica']);

    if (!$idMotorista || !is_numeric($idMotorista)) {
        header('Content-Type: application/json');
        echo json_encode(['msg' => 'Motorista inválido!']);
        exit;
    }

    $pedido = new Pedidos();
    $pedido->searchById($_POST['pedido']);

    if ($pedido->getValue('status') == 1) {
        $_POST['motorista'] = $idMotorista;
        $pedido->setValues($_POST);
        $pedido->update();

        NotificacaoMotorista::deleteByOrderId($pedido->getValue('id'));

        $passageiro = new PerfilPassageiro();
        $passageiro->searchById($pedido->getValue('passageiro'));

        $retorno = [
            'pedido' => $pedido->getValues(),
            'passageiro' => $passageiro->getValues(),
            'retorno' => 1,
            'msg' => 'Pedido aceite com sucesso'
        ];

        $info_motorista = new InfoMotorista();
        $info_motorista->searchById($idMotorista);
        $info_motorista->setValue('status_viagem', 1);
        $info_motorista->update();

        header('Content-Type: application/json');
        echo json_encode($retorno);
        exit;
    } else {
        $retorno = [
            'pedido' => $pedido->getValues(),
            'retorno' => 2,
            'msg' => 'Pedido já aceite'
        ];
        header('Content-Type: application/json');
        echo json_encode($retorno);
        exit;
    }
});

$app->post('/corridaapi/notificacao/regeitar', function(){
    if(isset($_POST['notificacao']) AND isset($_POST['motorista']) AND isset($_POST['chave_publica'])){
    	$num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
        $notificacao = new NotificacaoMotorista();
        $notificacao->searchById($_POST['notificacao']);
        $notificacao->setValue('status',2);
        $notificacao->update();	
			$retorno = array('pedido'=>$notificacao->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
//corrida passageiro
$app->post('/corridaapi/pedido/cadastro', function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND isset($_POST['origem']) AND isset($_POST['destino']) AND isset($_POST['metodo_pagamento']) AND isset($_POST['tipo_viatura'])){
		$_POST['paragem']=isset($_POST['paragem'])?$_POST['paragem']:'';
		$_POST['viajante']=isset($_POST['viajante'])?$_POST['viajante']:'';
		$_POST['contacto']=isset($_POST['contacto'])?$_POST['contacto']:'';

		$_POST['provincia']=isset($_POST['provincia'])?$_POST['provincia']:'Luanda';
		$_POST['provincia'] = 'Luanda';

		//pegar id da provincia ou regiao
		$_POST['empresa'] = Empresa::searchByProvincia($_POST['provincia']);

		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$pedido = new Pedidos();
		$pedido->setValues($_POST);
		$pedido->setValue('passageiro',$num);
		$pedido->setValue('data_pedido', date('Y-m-d H:m:s'));
		$num_pedido = $pedido->save(); 
		$retorno_pedido = new Pedidos();
		$retorno_pedido->searchById($num_pedido);
        $retorno_pedido->setValue('passageiro',$numcriptografado);
		$retorno = array('pedido'=>$retorno_pedido->getValues(),'retorno'=>1,'msg'=>'Dados cadastrados com sucesso','chave_publica'=>$_POST['chave_publica']);
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/pedido/actualizar', function(){
    if(isset($_POST['pedido'])){
		$pedido = new Pedidos();
		$pedido->searchById($_POST['pedido']);
		$pedido->setValues($_POST);
		$pedido->update();
		$retorno = array('pedido'=>$pedido->getValues(),'retorno'=>1,'msg'=>'Pedido Atualizado com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/pesquisa-2', function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND isset($_POST['categoria']) AND isset($_POST['pedido'])){
       	//pesquisa da comissao
		$comissao = Comissao::getTaxaPiloto(); 
		$_POST['comissao'] = $comissao['taxa'];

		//info pré cadastradas da pesquisa
		$distancia_pesquisa = new Pesquisa();
		$distancia_pesquisa->searchById(1);
		// $distancia_pesquisa = $distancia_pesquisa->getValues();

		//verificar se o pedido existe
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$pedido = new Pedidos(); 
		$pedido->searchById($_POST['pedido']);
		// $localizacao_actualp = explode(",", $pedido->getValue('origem'));

        //localizar motoristas em serviço;
        $motorista = PerfilMotorista::listAllOnByCategory('Piloto');
        $motorista_disponiveis = array();

        foreach($motorista as $value){
        	$notificacao_verify = NotificacaoMotorista::searchByPedido($value['id'],$pedido->getValue('id'));

			//Verificar o saldo do motorista
			$saldo = SaldoMotorista::getSaldo($value['id']);
			$calc_comissao = $pedido->getValue('estimativa') * ($_POST['comissao'] / 100);

        	//Enviar a notificacao apenas para quem não recebeu
        	if(!$notificacao_verify){
        		if($value['localizacao_actual'] != ''){

		        	// $localizacao_actualm = explode(",", $value['localizacao_actual']);
		        	// //distancia entre o passageiro e o motorista
		            // $distancia = Geolocalizacao::distanciaKm($localizacao_actualp[0],$localizacao_actualp[1],$localizacao_actualm[0],$localizacao_actualm[1]); 

		            // if($distancia <= $distancia_pesquisa['raio'] && $saldo >= $calc_comissao){
		            	$minuto = strtotime(date('Y-m-d H:i:s')) - strtotime($pedido->getValue('data_pedido'));
						// if(date('i',$minuto) < 120){
		            		$notificacao_motorista = new NotificacaoMotorista();
		            		$notificacao_motorista->setValue('notificacao','Pedido de Viagem');
		            		$notificacao_motorista->setValue('motorista',$value['id']);
		            		$notificacao_motorista->setValue('pedido',$_POST['pedido']);
		            		$notificacao_motorista->setValue('tipo','pedido de viagem');
		            		$notificacao_motorista->save();
		            		$motorista_disponiveis[] = array('motorista'=>$value['id'],'nome'=>$value['nome']);
		            	// }
		            // }
		        }
	        }
        }

	header('Content-Type: application/json');
	echo json_encode(array('motorista_disponiveis'=>$motorista_disponiveis,'total'=>count($motorista_disponiveis)));
	exit;
   
    }else{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
    } 
});

$app->post('/corridaapi/pesquisa-3', function(){
	if(isset($_POST['chave_publica']) AND isset($_POST['id']) AND isset($_POST['categoria']) AND isset($_POST['pedido'])){
       	//pesquisa da comissao
		$comissao = Comissao::getTaxaPiloto(); 
		$_POST['comissao'] = $comissao['taxa'];
		//info pré cadastradas da pesquisa
		$distancia_pesquisa = new Pesquisa();
		$distancia_pesquisa->searchById(1);
		$distancia_pesquisa = $distancia_pesquisa->getValues();
		//verificar se o pedido existe
		$num = Criptografia::descriptografar($_POST['id'],$_POST['chave_publica']);
		$numcriptografado = $_POST['id'];
		unset($_POST['id']);
		$pedido = new Pedidos(); 
		$pedido->searchById($_POST['pedido']);
		$localizacao_actualp = explode(",", $pedido->getValue('origem'));
        //localizar motoristas em serviço;
        $motorista = PerfilMotorista::listAllOnByCategory($_POST['categoria']);
        $motorista_disponiveis = array();
        foreach($motorista as $value){ 
        	$notificacao_verify = NotificacaoMotorista::searchByPedido($value['id'],$pedido->getValue('id'));

			//Verificar o saldo do motorista
			$saldo = SaldoMotorista::getSaldo($value['id']);
			$calc_comissao = $pedido->getValue('estimativa') * ($_POST['comissao'] / 100);

        	//Enviar a notificacao apenas para quem não recebeu
        	if(!$notificacao_verify){
        		if($value['localizacao_actual'] != ''){
					
		        	$localizacao_actualm = explode(",", $value['localizacao_actual']);
		        	//distancia entre o passageiro e o motorista
		            $distancia = Geolocalizacao::distanciaKm($localizacao_actualp[0],$localizacao_actualp[1],$localizacao_actualm[0],$localizacao_actualm[1]); 
		            
		            if($distancia <= $distancia_pesquisa['raio'] && $saldo >= $calc_comissao){
		            	$minuto = strtotime(date('Y-m-d H:i:s')) - strtotime($pedido->getValue('data_pedido')); 
						if(date('i',$minuto) < 120){
		            		$notificacao_motorista = new NotificacaoMotorista();
		            		$notificacao_motorista->setValue('notificacao','Pedido de Viagem');
		            		$notificacao_motorista->setValue('motorista',$value['id']);
		            		$notificacao_motorista->setValue('pedido',$_POST['pedido']);
		            		$notificacao_motorista->setValue('tipo','pedido de viagem');
		            		$notificacao_motorista->save();
		            		$motorista_disponiveis[] = array('motorista'=>$value['id'],'nome'=>$value['nome']);
							header('Content-Type: application/json');
							echo json_encode(array('motorista_disponiveis'=>$motorista_disponiveis,'total'=>count($motorista_disponiveis)));
							exit; 
							break;
		            	}
		            }
		        }
	        }
			
        } 
		header('Content-Type: application/json');
		echo json_encode(array('motorista_disponiveis'=>$motorista_disponiveis,'total'=>count($motorista_disponiveis)));
		exit;
   
    }else{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
    } 
});
//Viagem
$app->post('/corridaapi/viagem/iniciar', function(){
    if (
        isset($_POST['chave_publica']) && 
        isset($_POST['id']) && 
        isset($_POST['origem']) && 
        isset($_POST['destino']) && 
        isset($_POST['pedido']) && 
        isset($_POST['distancia_viagem']) && 
        isset($_POST['tempo_viagem'])
    ){
        $_POST['inicio_viagem'] = date('Y-m-d H:i:s');
        $num = Criptografia::descriptografar($_POST['id'], $_POST['chave_publica']);
        $numcriptografado = $_POST['id'];
        unset($_POST['id']);

        $pedido = new Pedidos();
        $pedido->searchById($_POST['pedido']);
		$pedido->setValue('status', 4);
		$pedido->update();

        $viagem = new Viagens();
        $viagem->setValues($_POST);
        $viagem->setValue('passageiro', $pedido->getValue('passageiro'));
        $viagem->setValue('empresa', $pedido->getValue('empresa'));
        $viagem->setValue('motorista', $num);
        $num_viagem = $viagem->save();
        $viagem->searchById($num_viagem);
        $viagem->setValue('passageiro', $numcriptografado);

        $pagamento = new PagamentoViagem();
        $pagamento->setValue('viagem', $num_viagem);
        $pagamento->setValue('valor', '0');
        $pagamento->setValue('valor_pago', '0');
        $pagamento->setValue('comissao', '0');
        $pagamento->setValue('metodo_pagamento', 'Cash');
        $pagamento->setValue('data_pagamento', date('Y-m-d H:i:s'));
        $pagamento->setValue('descricao', 'Pagamento de Viagem');

        $moeda = Moeda::getMoeda();
        $pagamento->setValue('moeda', $moeda['moeda']);

        $cupom = Cupom::listCupomAutomatico(date('Y-m-d'), date('Y-m-d'), $pedido->getValue('empresa'));
        if (empty($cupom)) {
            $pagamento->setValue('desconto', 0);  
        } else {
            $verifica_estado_cupom = Cupom::VerifyPassageiro($pedido->getValue('passageiro'), $cupom['id']);
            if (empty($verifica_estado_cupom)) {
                $desconto = Cupom::getDesconto($cupom['codigo'], date('Y-m-d'), date('Y-m-d'));
                $pagamento->setValue('desconto', $desconto);
                $cupons_passageiro = new CupomPassageiro();
                $cupons_passageiro->setValue('passageiro', $pedido->getValue('passageiro'));
                $cupons_passageiro->setValue('cupom', $cupom['id']);
                $cupons_passageiro->setValue('status', 0);
                $cupons_passageiro->save();
            } else { 
                $pagamento->setValue('desconto', 0);  
            }
        }
        $pagamento->setValue('imposto', 0);
        $pagamento->setValue('taxa_adicional', 0);
        $pagamento->setValue('hash', '');
        $pagamento->save();
        $retorno = array('viagem' => $viagem->getValues(), 'retorno' => 1, 'msg' => 'Dados cadastrados com sucesso', 'chave_publica' => $_POST['chave_publica']);
        
        $info_motorista = new InfoMotorista();
        $info_motorista->searchById($num);
        $info_motorista->setValue('status_viagem', 1);
        $info_motorista->update();

        header('Content-Type: application/json');
        echo json_encode($retorno);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(array('msg' => 'Dados obrigatórios não preenchidos!'));
        exit;
    }
});

$app->post('/corridaapi/viagem/actualizar', function(){
    if(isset($_POST['id'])){
		$viagem = new Viagens();
		$viagem->searchById($_POST['id']);
		$viagem->setValues($_POST); 
		$viagem->update();
		$pagamento = new PagamentoViagem();
		$pagamento->searchByViagem($_POST['id']);
		$retorno = array('viagem'=>$viagem->getValues(),'pagamento'=>$pagamento->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/viagem/pagamento-actualizar', function(){
    if(isset($_POST['viagem']) AND isset($_POST['valor'])){
    	$viagem = new Viagens();
		$viagem->searchById($_POST['viagem']);
		$valor = doubleval($_POST['valor']);

		$pagamento = new PagamentoViagem();
		$pagamento->searchByViagem($_POST['viagem']);
		$pagamento->setValue('valor',$valor);
		$pagamento->update();

		$retorno = array('viagem'=>$viagem->getValues(),'pagamento'=>$pagamento->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/viagem/ver-dados', function(){
    if(isset($_POST['pedido'])){
		$viagem = new Viagens();
		$viagem->searchByPedido($_POST['pedido']);
		if(!empty($viagem->getValues())){
			$pagamento = new PagamentoViagem();
			$pagamento->searchByViagem($viagem->getValue('id'));
			$retorno = array('viagem'=>$viagem->getValues(), 'pagamento'=>$pagamento->getValues(),'retorno'=>1,'msg'=>'Dados alterados com sucesso');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}
	} else {
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/viagem/verificar-cupom', function(){
	if(isset($_POST['cupom'])){
		$desconto = Cupom::getDesconto($_POST['cupom'],date('Y-m-d'),date('Y-m-d'));
		if($desconto != 0.00){
			$retorno = array('desconto'=>$desconto, 'retorno'=>1, 'msg'=>'Dados encontrados com sucesso.');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}else{
			$retorno = array('desconto'=>0, 'retorno'=>0, 'msg'=>'Dados não encontrados.');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/viagem/aplicar-cupom', function(){
	if(isset($_POST['cupom']) AND isset($_POST['viagem'])){
		$viagem = new Viagens();
		$viagem->searchById($_POST['viagem']);
		$desconto = Cupom::getDesconto2($_POST['cupom'],date('Y-m-d'),date('Y-m-d'),$viagem->getValue('empresa'));
		if($desconto != 0.00){
			$pagamento = new PagamentoViagem();
			$pagamento->searchByViagem($_POST['viagem']);
			$pagamento->setValue('desconto',$desconto);
			$pagamento->update();
			$retorno = array('desconto'=>$desconto, 'retorno'=>1, 'msg'=>'Dados encontrados com sucesso.');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}else{
			$retorno = array('desconto'=>0, 'retorno'=>0, 'msg'=>'Dados não encontrados.');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
//pagar pela carteira
$app->post('/corridaapi/carteira/pagar', function(){
    if(isset($_POST['chave_publica']) && isset($_POST['motorista']) && isset($_POST['viagem']) && isset($_POST['valor'])){
	    $num = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_publica']);
		$numcriptografado = $_POST['motorista'];
		unset($_POST['motorista']);
		$viagem = new Viagens();//buscar dados da viagem
		$viagem->searchById($_POST['viagem']);
		$saldo = Carteira::getSaldo($viagem->getValue('passageiro'));
		if($saldo < $_POST['valor']){
			$retorno = array('retorno'=>0, 'msg'=>'Saldo insuficiente!');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}else{
			$_POST['hash']= Criptografia::criptografar($viagem->getValue('passageiro').' '.$num.' '.$_POST['valor'].' '.$_POST['viagem'],6852530267784961);
			$carteira = new PagamentoCarteira();
			$carteira->setValues($_POST);
			$carteira->setValue('motorista',$num);
			$carteira->setValue('passageiro',$viagem->getValue('passageiro'));
			$carteira->save();

            $saldofinal = $saldo - $_POST['valor'];
			$carteira = new Carteira();
			$carteira->setValue('passageiro',$viagem->getValue('passageiro'));
			$carteira->addSaldo($saldofinal);

			$retorno = array('retorno'=>1, 'msg'=>'Pagamento feito com sucesso');
			header('Content-Type: application/json');
			echo json_encode($retorno);
			exit;
		}
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/viagem/finalizar', function(){
    if(isset($_POST['viagem']) AND isset($_POST['valor']) AND isset($_POST['valor_pago']) AND isset($_POST['metodo_pagamento'])){
    	//Pegar os dados da viagem
    	$viagem = new Viagens();
		$viagem->searchById($_POST['viagem']);
		$viagem->setValue('termino_viagem',	date('Y-m-d H:i:s'));
		$viagem->setValue('status',2);
		$viagem->update();
    	$_POST['data_pagamento'] = date('Y-m-d');
    	//Comissão
    	$comissao = Comissao::getTaxa($viagem->getValue('motorista'));
        $_POST['comissao'] = $comissao['taxa'];
        //Moeda
        $moeda = Moeda::getMoeda();
        $_POST['moeda'] = $moeda['moeda'];
    	$_POST['descricao'] = "Pagamento de Viagem"; 
    	$_POST['imposto'] = 0;
    	$_POST['taxa_adicional'] = 0;
    	$_POST['hash'] = base64_encode($_POST['viagem'].$_POST['descricao'].$_POST['valor'].$viagem->getValue('motorista').$viagem->getValue('passageiro'));
    	//Pagamento
    	$pagamento = new PagamentoViagem();
    	$pagamento->searchByViagem($_POST['viagem']);
		$pagamento->setValues($_POST);
		$pagamento->setValue('status',1);
		$pagamento->update();

		//Verificar o saldo do motorista
		$saldo = SaldoMotorista::getSaldo($viagem->getValue('motorista'));
		$calc_comissao = $_POST['valor'] * ($_POST['comissao'] / 100);
		//Registar Movimento na carteira do motorista
		$movsaldomotorista = new MovimentoSaldoMotorista();
		$movsaldomotorista->setValue('motorista',$viagem->getValue('motorista'));
		$movsaldomotorista->setValue('comprovativo','img/sem imagem.jpg');
		$movsaldomotorista->setValue('tipo_comprovativo','Nulo');
		$movsaldomotorista->setValue('status',1);
		$movsaldomotorista->setValue('data_aceite', null);
		$movsaldomotorista->setValue('aceite_por', null);
		$movsaldomotorista->setValue('tipo_movimento', 'Débito');
		$movsaldomotorista->setValue('metodo_movimento', 'Sistema');
		$movsaldomotorista->setValue('data_movimento', date('Y-m-d H:i:s'));
		$movsaldomotorista->setValue('valor', $calc_comissao);
		$movsaldomotorista->save();
		$obj_saldo_motorista = New SaldoMotorista();
		$obj_saldo_motorista->setValue('motorista', $viagem->getValue('motorista'));
		$obj_saldo_motorista->addSaldo($saldo - $calc_comissao);

		$info_motorista = new InfoMotorista();
		$info_motorista->searchById($viagem->getValue('motorista'));
		$info_motorista->setValue('status_viagem', 0);
		$info_motorista->update();

		$retorno = array('pagamentoviagem'=>$pagamento->getValues(),'viagem'=>$viagem->getValues(),'retorno'=>1,'msg'=>'Viagem terminada com sucesso');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
//Chat 
$app->post('/corridaapi/chat/enviar-msg', function(){
    if(isset($_GET['app'])){
    	$chat = new Chat();
    	if($_GET['app']=='motorista'){
	    	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave_motorista']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);
			$num_passageiro = $_POST['passageiro'];
		}
		if($_GET['app']=='passageiro'){
			$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave_passageiro']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);
			$num_motorista = $_POST['motorista'];
		}
        $chat->setValue('mensagem',$_POST['mensagem']);
        $chat->setValue('passageiro',$num_passageiro);
        $chat->setValue('motorista',$num_motorista);
        $chat->setValue('data',date('Y-m-d'));
        $chat->setValue('hora',date('H:i:s'));
        //Quem envia (status=1) e quem recebe (status=2)
        if($_GET['app']=='motorista'){
        	$chat->setValue('status_passageiro',2);
        	$chat->setValue('status_motorista',1);
        }
        if($_GET['app']=='passageiro'){
        	$chat->setValue('status_passageiro',1);
        	$chat->setValue('status_motorista',2);
        }
        $chat->save();	
		$retorno = array('retorno'=>1,'msg'=>'Mensagem enviada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/chat/notificacao', function(){
    if(isset($_POST['chave']) AND isset($_GET['app'])){
        if($_GET['app']=='motorista'){
        	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);
			$mensagens = Chat::notification($num_motorista,'motorista');
			if(!empty($mensagens)){ $retorno = 1; $quantidade = count($mensagens);}
			else{$retorno = 0; $quantidade = 0;}
        }
        if($_GET['app']=='passageiro'){
        	$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);
			$mensagens = Chat::notification($num_passageiro,'passageiro');
			if(!empty($mensagens)){ $retorno = 1; $quantidade = count($mensagens);}
			else{$retorno = 0; $quantidade = 0;}
        }	
		$retorno = array('retorno'=>$retorno,'quantidade'=>$quantidade,'msg'=>'Pesquisa feita com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
}); 
$app->post('/corridaapi/chat/ver-msg-all', function(){
    if(isset($_POST['chave']) AND isset($_GET['app'])){
        $chat = new Chat();
        if($_GET['app']=='motorista'){
        	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);
			$mensagens_bruta = Chat::getPageMotoristaAll($num_motorista);
        }
        if($_GET['app']=='passageiro'){
        	$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);
			$mensagens_bruta = Chat::getPagePassageiroAll($num_passageiro);
        }	
		$retorno = array('mensagens'=>$mensagens_bruta['data'],'retorno'=>1,'msg'=>'Mensagem enviada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/chat/ver-msg', function(){
    if(isset($_POST['chave']) AND isset($_GET['app'])){
        $chat = new Chat();
        if($_GET['app']=='motorista'){
        	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);
			$mensagens_bruta = Chat::getPageMotorista($num_motorista, $_POST['passageiro']);
        }
        if($_GET['app']=='passageiro'){
        	$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);
			$mensagens_bruta = Chat::getPagePassageiro($num_passageiro, $_POST['motorista']);
        }	
		$retorno = array('mensagens'=>$mensagens_bruta['data'],'retorno'=>1,'msg'=>'Mensagem enviada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
$app->post('/corridaapi/chat/abrir-msg', function(){
    if(isset($_POST['chave']) AND isset($_GET['app'])){
		$chat = new Chat();
        if($_GET['app']=='motorista'){
        	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);
			$chat->setValue('motorista',$num_motorista);
			$chat->openMsg('motorista');
        }
        if($_GET['app']=='passageiro'){
        	$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);
			$chat->setValue('passageiro',$num_passageiro);
			$chat->openMsg('passageiro');
        }	
		$retorno = array('retorno'=> 1, 'msg'=> 'Mensagem aberta com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
}); 
$app->post('/corridaapi/chat/delete-msg', function(){
    if(isset($_POST['chave']) AND isset($_GET['app']) AND isset($_POST['id'])){
        $chat = new Chat();
        if($_GET['app']=='motorista'){
        	$num_motorista = Criptografia::descriptografar($_POST['motorista'],$_POST['chave']);
			$numcriptografado_motorista = $_POST['motorista'];
			unset($_POST['motorista']);

			$chat->searchById($_POST['id']);
			$chat->delete('motorista');
        }
        if($_GET['app']=='passageiro'){
        	$num_passageiro = Criptografia::descriptografar($_POST['passageiro'],$_POST['chave']);
			$numcriptografado_passageiro = $_POST['passageiro'];
			unset($_POST['passageiro']);

			$chat->searchById($_POST['id']);
			$chat->delete('passageiro');
        }	
		$retorno = array('retorno'=> 1, 'msg'=> 'Mensagem eliminada com sucesso!');
		header('Content-Type: application/json');
		echo json_encode($retorno);
		exit;
	}
	else
	{
		header('Content-Type: application/json');
		echo json_encode(array('msg'=>'Dados obrigatórios não preenchidos!'));
		exit;
	}
});
// Rotas administrativas ==========================================
$app->get('/', function(){
	Operador::verifyLogin();
	$num_empresa = $_SESSION['fastusuario']['empresa'];
	$empresa = new Empresa();
	$empresa->searchById($num_empresa);
	if($empresa->getValue('tipo') == 'Geral'){
		$total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilter('1','1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilter('0','1','5000');
		$total_viagens = count(Viagens::getPageFilter(2));
	}else{
        $total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilterEmpresa('1',$empresa->getValue('provincia'),'1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilterEmpresa('0',$empresa->getValue('provincia'),'1','5000');
		$total_viagens = count(Viagens::getPageFilter(2,$empresa->getValue('provincia')));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('home',array('total_passageiros'=>$total_passageiros['total'],'total_motoristas1'=>$total_motoristas1['total'],'total_motoristas0'=>$total_motoristas0['total'],'total_viagens'=>$total_viagens));
});
$app->get('/home-mapa', function(){
	Operador::verifyLogin();
	$num_empresa = $_SESSION['fastusuario']['empresa'];
	$empresa = new Empresa();
	$empresa->searchById($num_empresa);
	if($empresa->getValue('tipo') == 'Geral'){
		$total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilter('1','1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilter('0','1','5000');
		$total_viagens = count(Viagens::getPageFilter(2));
	}else{
        $total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilterEmpresa('1',$empresa->getValue('provincia'),'1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilterEmpresa('0',$empresa->getValue('provincia'),'1','5000');
		$total_viagens = count(Viagens::getPageFilter(2,$empresa->getValue('provincia')));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('home-mapa',array('total_passageiros'=>$total_passageiros['total'],'total_motoristas1'=>$total_motoristas1['total'],'total_motoristas0'=>$total_motoristas0['total'],'total_viagens'=>$total_viagens));
});
$app->get('/home2', function(){
	Operador::verifyLogin();
	$num_empresa = $_SESSION['fastusuario']['empresa'];
	$empresa = new Empresa();
	$empresa->searchById($num_empresa);
	if($empresa->getValue('tipo') == 'Geral'){
		$total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilter('1','1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilter('0','1','5000');
		$total_viagens = count(Viagens::getPageFilter(2));
	}else{
        $total_passageiros = PerfilPassageiro::getPage(); 
		$total_motoristas1 = PerfilMotorista::getPageFilterEmpresa('1',$empresa->getValue('provincia'),'1','5000');
		$total_motoristas0 = PerfilMotorista::getPageFilterEmpresa('0',$empresa->getValue('provincia'),'1','5000');
		$total_viagens = count(Viagens::getPageFilter(2,$empresa->getValue('provincia')));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('mapa2',array('total_passageiros'=>$total_passageiros['total'],'total_motoristas1'=>$total_motoristas1['total'],'total_motoristas0'=>$total_motoristas0['total'],'total_viagens'=>$total_viagens));
});
$app->get('/login/', function(){
	$retorno = isset($_GET['retorno'])?$_GET['retorno']:'';
    $page = new PageAdmin(array("header"=>false,"footer"=>false));
	$page->setTpl('login',array('retorno'=>$retorno));
});
$app->get('/login', function(){
	$retorno = isset($_GET['retorno'])?$_GET['retorno']:'';
    $page = new PageAdmin(array("header"=>false,"footer"=>false));
	$page->setTpl('login',array('retorno'=>$retorno));
});
$app->post('/login', function() {
	Operador::login($_POST['usuario'], $_POST['senha']);
	exit;
});
$app->get('/logout', function(){
    Operador::logout();
});
//users
$app->get('/users', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Operador::getPageSearch($search, $page);
		}else{
			$pagination = Operador::getPageSearchEmpresa($search, $_SESSION['fastusuario']['empresa'], $page);
		}
	} else {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Operador::getPage($page);
		}else{
			$pagination = Operador::getPageEmpresa($_SESSION['fastusuario']['empresa'], $page);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'users?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('user',array('users'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/users/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll(); 
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('user-new',array('lista_empresas'=>$lista_empresas));
});
$app->post('/users/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$user = new Operador();
	$senha = '12345';
 	$_POST['senha'] = password_hash($senha, PASSWORD_DEFAULT, array("cost"=>12));
 	//verificar empresa
 	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
 	if($empresa->getValue('tipo') != 'Geral'){
 		$_POST['empresa'] = $_SESSION['fastusuario']['empresa'];
 	}
	$user->setValues($_POST);
	$num = $user->save();
	$user->searchById($num);
	if(!empty($user->getValues())){
		$permission = new Permissao();
		for($i=1; $i<=8; $i++){
			$permission->setValue('operacao',$i);
			$permission->setValue('operador',$user->getValue('usuario'));
			$permission->setValue('ver',0);
			$permission->setValue('modificar',0);
			$permission->save();
		}
	}
	header('Location:'.DIR_MAE.'users');
	exit; 
});
$app->get('/users/permission', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Operador::getPageSearch($search, $page);
		}else{
			$pagination = Operador::getPageSearchEmpresa($search,$_SESSION['fastusuario']['empresa'], $page);
		}
	} else {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Operador::getPage($page);
		}else{
			$pagination = Operador::getPage($_SESSION['fastusuario']['empresa'], $page);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			'href'=>DIR_MAE.'users?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));

	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('permission',array('users'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/users/permission/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$operador = new Operador();
	$operador->searchById($num);
	$permissoes = Permissao::listAll($operador->getValue('usuario')); 
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('permission-view',array('operador'=>$operador->getValues(),'permissoes'=>$permissoes));
});
$app->get('/users/permission/:num/ver-:value', function($num,$value){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	if($value == 'activar'){ $ver = 1;}
	if($value == 'desactivar'){ $ver = 0;}
	$permission = new Permissao();
	$permission->searchById($num);
	$permission->setValue('ver',$ver);
	$permission->update(); 
	$operador = new Operador();
	$operador->searchByUser($permission->getValue('operador'));
	header('Location:'.DIR_MAE.'users/permission/'.$operador->getValue('id'));
	exit; 
});
$app->get('/users/permission/:num/modificar-:value', function($num,$value){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	if($value == 'activar'){ $modificar = 1;}
	if($value == 'desactivar'){ $modificar = 0;}
	$permission = new Permissao();
	$permission->searchById($num);
	$permission->setValue('modificar',$modificar);
	$permission->update();
	$operador = new Operador();
	$operador->searchByUser($permission->getValue('operador'));
	header('Location:'.DIR_MAE.'users/permission/'.$operador->getValue('id'));
	exit;
});
$app->get('/users/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$operador = new Operador();
	$operador->searchById($num);
    $page = new PageAdmin(array('header'=>false,'footer'=>false));
	$page->setTpl('cropimageoperador',array('operador'=>$operador->getValues()));	 
});
$app->post('/users/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
    	if(isset($_POST["foto"]))
		{
		$data = $_POST["foto"];
		$image_array_1 = explode(";", $data);
		$ext = substr($image_array_1[0],5);
		$image_array_2 = explode(",", $image_array_1[1]); 
		$data = base64_decode($image_array_2[1]); 
		$imageName = 'img/operador/'.rand().date('YmdHis').'.png'; 
		$operador = new Operador();
		$operador->searchById($num);
		$operador->updateImg($imageName);
		if($ext == 'image/png'){
			if(file_put_contents($imageName, $data)){
				if(file_exists($operador->getValue('foto'))){
                  if($operador->getValue('foto') != 'img/sem imagem.jpg')unlink($operador->getValue('foto'));
				}
				echo "<div class='alert alert-success'> Imagem carregada com sucesso! </div>";
   			}
   		}else{
   			echo "<div class='alert alert-danger'> Erro ao carregar a Imagem! </div>";
   		}
	}	 
});
$app->get('/users/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$user = new Operador();
	$user->searchById($num);
    $user->delete();
	header('Location:'.DIR_MAE.'users');
	exit;
});
$app->get('/users/change-password', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('change-password');	 
});
$app->post('/users/change-password', function(){
	Operador::verifyLogin();
    $user = new Operador();
	$user->changePassword($_SESSION['fastusuario']['usuario'],$_POST['antigasenha'],$_POST['novasenha']);
	header('Location:'.DIR_MAE.'users');
	exit; 
});
$app->get('/users/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$user = new Operador();
	$user->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('user-update',array('user'=>$user->getValues(),'lista_empresas'=>$lista_empresas)); 
});
$app->post('/users/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],8);
	$user = new Operador();
	$user->searchById($num);
	$user->setValues($_POST);
	$user->update();
	header('Location:'.DIR_MAE.'users');
	exit; 
});
//Msg
$app->get('/mensagem', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {
		$pagination = Mensagem::getPageSearch($search, $page,50);
	} else {
		$pagination = Mensagem::getPage($page,50);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			'href'=>DIR_MAE.'mensagem?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));

	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues()
)));
	$page->setTpl('msg',array('msg'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/mensagem/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('msg-new');
});
$app->post('/mensagem/new', function(){	
	User::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
	$msg = new Mensagem(); 
	$msg->setValues($_POST);
	$msg->save();
	header('Location:/config/msg');
	exit; 	
});
$app->get('/mensagem/:num/delete', function($num){
	User::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
	$msg = new Mensagem();
	$msg->searchById($num);
    $msg->delete();
	header('Location:'.DIR_MAE.'mensagem');
	exit;
});
$app->get('/mensagem/:num', function($num){
	User::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
	$msg = new Mensagem();
	$msg->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues()
)));
	$page->setTpl('msg-ready',array('msg'=>$msg->getValues())); 
});
$app->post('/mensagem/:num', function($num){
	User::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],1);
	$msg = new Mensagem();
	$msg->searchById($num); 
	$_POST['operador'] = $_SESSION['fastusuario']['usuario'];
	$msg->setValues($_POST);
	$msg->update();
	header('Location:'.DIR_MAE.'mensagem');
	exit; 
});
//SMS MARKETING
$app->get('/sms-marketing', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],2);
	
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues()
)));
	$page->setTpl('sms-marketing');
});
$app->post('/sms-marketing/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],2);
	$motorista = isset($_POST['motorista'])?$_POST['motorista']:0;
	$passageiro = isset($_POST['passageiro'])?$_POST['passageiro']:0;
	$motoristaoff = isset($_POST['motoristaoff'])?$_POST['motoristaoff']:0;
	$passageirooff = isset($_POST['passageirooff'])?$_POST['passageirooff']:0;
	if($motorista){
		$list_motorista = PerfilMotorista::listAllTelEmail();
		if(!empty($list_motorista)){
			foreach($list_motorista as $value){
				Sms::send($value['telefone'],$_POST['note']); 
			}
		}
	}
	if($motoristaoff){
		$list_motoristaoff = PerfilMotorista::listAllTelEmailOff();
		if(!empty($list_motoristaoff)){
			foreach($list_motoristaoff as $value){ echo $value['telefone'];
				Sms::send($value['telefone'],$_POST['note']); 
			}
		}
	}
	if($passageiro){
		$list_passageiro = PerfilPassageiro::listAllTelEmail();
		if(!empty($list_passageiro)){
			foreach($list_passageiro as $value){
				Sms::send($value['telefone'],$_POST['note']); 
			}
		}
	}
	if($passageirooff){
		$list_passageirooff = PerfilPassageiro::listAllTelEmail();
		if(!empty($list_passageirooff)){
			foreach($list_passageirooff as $value){
				Sms::send($value['telefone'],$_POST['note']); 
			}
		}
	}
	header('Location:'.DIR_MAE.'sms-marketing');
	exit; 	
});
//EMAIL MARKETING
$app->get('/email-marketing', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],3);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues()
)));
	$page->setTpl('email-marketing');
});
$app->post('/email-marketing/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],3);

	$motorista = isset($_POST['motorista'])?$_POST['motorista']:0;
	$passageiro = isset($_POST['passageiro'])?$_POST['passageiro']:0;
	$motoristaoff = isset($_POST['motoristaoff'])?$_POST['motoristaoff']:0;
	$passageirooff = isset($_POST['passageirooff'])?$_POST['passageirooff']:0;
	$_POST['note'] = "<html><head><title>Aplicativo fastmoto</title></head><body>
	<a href='https://www.bazeiangola.com/'>
				 	       <img src='https://www.bazeiangola.com/img/logo.png' width='120'>
				 	</a>
				 	<h3 style='color:#f36d47'> fastmoto | A sua boleia em um minuto </h3>
				 	<p>".$_POST['note']."</p><hr style='border-top: 4px solid #f36d47;'><p> 940 758 008 - 222 727 608 | info@bazeiangola.com  </p></body></html>";
	$email = new Email();
	$address = array();  
	if($motorista){
		$list_motorista = PerfilMotorista::listAllEmail();
		if(!empty($list_motorista)){
			foreach($list_motorista as $value){
					$address[] = $value['email'];
			}
		}
	}
	if($motoristaoff){
		$list_motoristaoff = PerfilMotorista::listAllEmailOff();
		if(!empty($list_motoristaOff)){
			foreach($list_motorista as $value){
					$address[] = $value['email'];
			}
		}
	}
	if($passageiro){
		$list_passageiro = PerfilPassageiro::listAllEmail();
		if(!empty($list_passageiro)){
			foreach($list_passageiro as $value){
					$address[] = $value['email'];
			}
		}
	}
	if($passageirooff){
		$list_passageirooff = PerfilPassageiro::listAllEmailOff();
		if(!empty($list_passageirooff)){
			foreach($list_passageirooff as $value){
					$address[] = $value['email'];
			}
		}
	}
	$total_address = count($address);
	$carrinho_email = array();

	if($total_address > 10){
		for($x=1;$x<$total_address; $x++)
		{
		
			$carrinho_email[] = $address[$x-1];
			if(($x % 10) == 0){
				echo "<a href='".http_build_query($carrinho_email)."'></a>";
				$carrinho_email[] = array();
			}
		}
	}else{
			$email->send('Aplicativo fastmoto',$_POST['note'],'',$address);
		}
			
    
	echo "<br><a href=DIR_MAE.'email-marketing'> Voltar </a>"; 	
});
$app->post('/email-marketing/send', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],3);
	var_dump($_GET['emails']);die;

	$email->send('Aplicativo fastmoto',$_POST['note'],'',$address);
});
//passageiros
$app->get('/passageiros', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if($search != ''){
		$pagination = PerfilPassageiro::getPageSearch($search, $page);
	}else if($filtro != ''){
		if($filtro == 'Activos'){
			$pagination = PerfilPassageiro::getPageFilter(1,500,1);
		}if($filtro == 'Banidos'){
			$pagination = PerfilPassageiro::getPageFilter(1,500,-1);
		}
	}else{
		$pagination = PerfilPassageiro::getPage($page,500);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'passageiros?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues()
)));
	$page->setTpl('passageiro',array('passageiro'=>$pagination['data'],'search'=>$search,'pages'=>$pages, 'total_passageiros'=>$pagination['total'],'filtro'=>$filtro));
});
$app->get('/passageiros/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('passageiro-new');
});
$app->post('/passageiros/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$passageiros = new PerfilPassageiro();
	$passageiros->setValues($_POST);
	$passageiros->save();
	header('Location:'.DIR_MAE.'passageiros');
	exit; 
});
$app->get('/passageiros/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$passageiro = new PerfilPassageiro();
	$passageiro->searchById($num);
    $page = new PageAdmin(array('header'=>false,'footer'=>false));
	$page->setTpl('cropimagepassageiro',array('passageiro'=>$passageiro->getValues()));	 
});
$app->post('/passageiros/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
    	if(isset($_POST["icon"]))
		{
		$data = $_POST["icon"];
		$image_array_1 = explode(";", $data);
		$ext = substr($image_array_1[0],5);
		$image_array_2 = explode(",", $image_array_1[1]); 
		$data = base64_decode($image_array_2[1]); 
		$imageName = 'img/viatura/'.rand().date('YmdHis').'.png'; 
		$passageiro = new PerfilPassageiro();
		$passageiro->searchById($num);
		$passageiro->updateImg($imageName);
		if($ext == 'image/png'){
			if(file_put_contents($imageName, $data)){
				if(file_exists($passageiro->getValue('icon'))){
                  if($passageiro->getValue('icon') != 'img/sem imagem.jpg')unlink($passageiro->getValue('icon'));
				}
				echo "<div class='alert alert-success'> Imagem carregada com sucesso! </div>";
   			}
   		}else{
   			echo "<div class='alert alert-danger'> Erro ao carregar a Imagem! </div>";
   		}
	}	 
});
$app->get('/passageiros/:num/activar', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$passageiro = new PerfilPassageiro();
	$passageiro->searchByIdGeneral($num);
    $passageiro->activate();
	header('Location:'.DIR_MAE.'passageiros');
	exit;
});
$app->get('/passageiros/:num/desactivar', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$passageiro = new PerfilPassageiro();
	$passageiro->searchById($num);
    $passageiro->deactivate();
	header('Location:'.DIR_MAE.'passageiros');
	exit;
});
$app->get('/passageiros/:num/banir', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	PerfilPassageiro::salvarJustificativa($num, $_GET['justificativa']);
	$passageiro = new PerfilPassageiro();
	$passageiro->searchById($num);
    $passageiro->banir();
	header('Location:'.DIR_MAE.'passageiros');
	exit;
});
$app->get('/passageiros/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$passageiro = new PerfilPassageiro();
	$passageiro->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('passageiro-update',array('passageiro'=>$passageiro->getValues())); 
});
$app->post('/passageiros/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	if(isset($_POST['telefone'])){unset($_POST['telefone']);}//
	$passageiro = new PerfilPassageiro();
	$passageiro->searchById($num);
	$passageiro->setValues($_POST);
	$passageiro->update();
	header('Location:'.DIR_MAE.'passageiros');
	exit; 
});
//Carteiras 
$app->get('/carteiras', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$search = (isset($_GET['search'])) ? $_GET['search'] : ""; 
	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : ""; 
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if($search != '') {
		$pagination = Carteira::getPageSearch($search, $page,50);
	}else if($filtro != '') {
		$pagination = Carteira::getPageFiltro($filtro, $page,50);
	} else {
		$pagination = Carteira::getPage($page,50);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			'href'=>DIR_MAE.'carteiras?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
	for ($x = 0; $x < count($pagination['data']); $x++)
	{
		$saldo = Carteira::getSaldo($pagination['data'][$x]['passageiro']);
		$pagination['data'][$x]['saldo'] = $saldo;
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('carteira',array('carteira'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/carteiras/extrato', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$operacao = (isset($_GET['operacao'])) ? $_GET['operacao'] : "Débito"; 
	$num_passageiro = (isset($_GET['num_passageiro'])) ? $_GET['num_passageiro'] : "-1";
	$obj_passageiro = new PerfilPassageiro();
	$obj_passageiro->searchById($num_passageiro);
	if(empty($obj_passageiro->getValues())){
		$nome_passageiro="Todos passageiros";
	}else{
		$nome_passageiro=$obj_passageiro->getValue('nome');
	} 
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d',strtotime(date("Y-m-d")."+1 day"));
	if($num_passageiro != -1){
		if($operacao == 'Débito') {
			$pagination = PagamentoCarteira::getPageFiltroPassageiro(1, $num_passageiro, $page,100);
		}else{
			$pagination = Carteira::getPageFiltroPassageiro(2, $num_passageiro, $page,100);
		}
	}else{
		if($operacao == 'Débito') {
			$pagination = PagamentoCarteira::getPageFiltro(1, $page,100);
		}else{
			$pagination = Carteira::getPageFiltro(2, $page,100);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'carteiras?'.http_build_query(array(
				'page'=>$x+1,
				'operacao'=>$operacao
			)),
			'text'=>$x+1
		));
	}
	for ($x = 0; $x < count($pagination['data']); $x++)
	{
		$saldo = Carteira::getSaldo($pagination['data'][$x]['passageiro']);
		$pagination['data'][$x]['saldo'] = $saldo;
	}
	$passageiro = PerfilPassageiro::getPage(1, 1000);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('extrato_carteira',array('carteira'=>$pagination['data'],'pages'=>$pages,'passageiro'=>$passageiro['data'],'de'=>$de,'ate'=>$ate,'operacao'=>$operacao,'num_passageiro'=>$num_passageiro,'nome_passageiro'=>$nome_passageiro));
});
$app->get('/carteiras/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('carteira-new');
});
$app->post('/carteiras/new', function(){	
	Operador::verifyLogin();
	UploadImg::upload('img/carregamento_carteira/','comprovativo');

	$passageiro = PerfilPassageiro::searchByTel($_POST['passageiro']);
    if(!empty($passageiro['dados'])){

    	$saldo = Carteira::getSaldo($passageiro['dados']['id']) + $_POST['valor'];

		$carteira = new Carteira();
		$carteira->setValues($_POST); 	
		$carteira->setValue('passageiro',$passageiro['dados']['id']); 
		$carteira->setValue('status',2);
		$carteira->setValue('data_aceite', date('Y-m-d H:i:s'));
		$carteira->setValue('aceite_por', $_SESSION['fastusuario']['usuario']);  
		$carteira->addSaldo($saldo);
		$carteira->save();
		header('Location:'.DIR_MAE.'carteiras?');
		exit;
	}else{
		header("Location:'.DIR_MAE.'carteiras?msg=Número inválido!");
		exit;
	} 	
});
$app->get('/carteiras/new/all', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('carteira-new-all');
});
$app->post('/carteiras/new/all', function(){	
	Operador::verifyLogin();

	$passageiros = PerfilPassageiro::listAll();
    foreach($passageiros as $value){

    	$saldo = Carteira::getSaldo($value['id']) + $_POST['valor'];
		$carteira = new Carteira(); 	
		$carteira->setValue('passageiro',$value['id']); 
		$carteira->addSaldo($saldo);
		
	}
	header('Location:'.DIR_MAE.'carteiras?');
	exit;
});
$app->get('/carteiras/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new Carteira();
	$carteira->searchById($num);
    $carteira->delete();
	header('Location:'.DIR_MAE.'carteiras');
	exit;
});
$app->get('/carteiras/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new Carteira();
	$carteira->searchById($num);
	$obj_carteira = $carteira->getValues();
	$obj_carteira['saldo'] = Carteira::getSaldo($carteira->getValue('passageiro'));
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('carteira-update',array('carteira'=> $obj_carteira)); 
});
$app->post('/carteiras/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new Carteira();
	$carteira->searchById($num);
	$carteira->setValue('data_aceite',date('Y-m-d H:i:s'));
	$carteira->setValue('status',2);
	$carteira->update();
	$add = new Carteira();
	$add->setValue('passageiro',$carteira->getValue('passageiro'));
	$add->addSaldo(Carteira::getSaldo($carteira->getValue('passageiro')) + $carteira->getValue('valor'));
	header('Location:'.DIR_MAE.'carteiras');
	exit; 
});
//Saldo Motoristas 
$app->get('/saldo-motoristas', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$search = (isset($_GET['search'])) ? $_GET['search'] : ""; 
	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : ""; 
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if($search != '') {
		$pagination = MovimentoSaldoMotorista::getPageSearch($search, $page,50);
	}else if($filtro != '') {
		$pagination = MovimentoSaldoMotorista::getPageFiltro($filtro, $page,50);
	} else {
		$pagination = MovimentoSaldoMotorista::getPage($page,50);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			'href'=>DIR_MAE.'saldo-motorista?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	} 
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('saldo-motorista',array('saldo_motorista'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/saldo-motoristas/extrato', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$operacao = (isset($_GET['operacao'])) ? $_GET['operacao'] : "Débito"; 
	$num_passageiro = (isset($_GET['num_passageiro'])) ? $_GET['num_passageiro'] : "-1";
	$obj_passageiro = new PerfilPassageiro();
	$obj_passageiro->searchById($num_passageiro);
	if(empty($obj_passageiro->getValues())){
		$nome_passageiro="Todos passageiros";
	}else{
		$nome_passageiro=$obj_passageiro->getValue('nome');
	} 
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d',strtotime(date("Y-m-d")."+1 day"));
	if($num_passageiro != -1){
		if($operacao == 'Débito') {
			$pagination = PagamentoCarteira::getPageFiltroPassageiro(1, $num_passageiro, $page,100);
		}else{
			$pagination = Carteira::getPageFiltroPassageiro(2, $num_passageiro, $page,100);
		}
	}else{
		if($operacao == 'Débito') {
			$pagination = PagamentoCarteira::getPageFiltro(1, $page,100);
		}else{
			$pagination = Carteira::getPageFiltro(2, $page,100);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'carteiras?'.http_build_query(array(
				'page'=>$x+1,
				'operacao'=>$operacao
			)),
			'text'=>$x+1
		));
	}
	for ($x = 0; $x < count($pagination['data']); $x++)
	{
		$saldo = Carteira::getSaldo($pagination['data'][$x]['passageiro']);
		$pagination['data'][$x]['saldo'] = $saldo;
	}
	$passageiro = PerfilPassageiro::getPage(1, 1000);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('extrato_carteira',array('carteira'=>$pagination['data'],'pages'=>$pages,'passageiro'=>$passageiro['data'],'de'=>$de,'ate'=>$ate,'operacao'=>$operacao,'num_passageiro'=>$num_passageiro,'nome_passageiro'=>$nome_passageiro));
});
$app->get('/saldo-motoristas/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('saldo-motorista-new');
});
$app->post('/saldo-motoristas/new', function(){	
	Operador::verifyLogin();
	UploadImg::upload('img/carregamento_carteira/','comprovativo');

	$passageiro = PerfilMotorista::searchByTel($_POST['motorista']);
    if(!empty($passageiro['dados'])){

    	$saldo = SaldoMotorista::getSaldo($passageiro['dados']['id']) + $_POST['valor'];

		$carteira = new Carteira();
		$carteira->setValues($_POST); 	
		$carteira->setValue('motorista',$passageiro['dados']['id']); 
		$carteira->setValue('status',2);
		$carteira->setValue('data_aceite', date('Y-m-d H:i:s'));
		$carteira->setValue('aceite_por', $_SESSION['fastusuario']['usuario']);
		$carteira->setValue('tipo_movimento','Crédito');
		$carteira->setValue('metodo_movimento','Sistema');
		$carteira->setValue('status',2);   
		$carteira->addSaldo($saldo);
		$carteira->save();
		header('Location:'.DIR_MAE.'saldo-motoristas?');
		exit;
	}else{
		header("Location:'.DIR_MAE.'saldo-motoristas?msg=Número inválido!");
		exit;
	} 	
});
$app->get('/saldo-motoristas/new/all', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('saldo-motorista-new-all');
});
$app->post('/saldo-motoristas/new/all', function(){	
	Operador::verifyLogin();

	$passageiros = PerfilPassageiro::listAll();
    foreach($passageiros as $value){

    	$saldo = SaldoMotorista::getSaldo($value['id']) + $_POST['valor'];
		$carteira = new SaldoMotorista(); 	
		$carteira->setValue('passageiro',$value['id']); 
		$carteira->addSaldo($saldo);
		
	}
	header('Location:'.DIR_MAE.'saldo-motoristas?');
	exit;
});
$app->get('/saldo-motoristas/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new MovimentoSaldoMotorista();
	$carteira->searchById($num);
    $carteira->delete();
	header('Location:'.DIR_MAE.'saldo-motoristas');
	exit;
});
$app->get('/saldo-motoristas/:num/abrir-pdf', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new MovimentoSaldoMotorista();
	$carteira->searchById($num);
	
	$file = $carteira->getValue('comprovativo');
	
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename=comprovativo');
	header('Content-Transfer-Encoding; binary');
	header('Accept-Ranges; bytes');
	readfile($file);
	exit;
});
$app->get('/saldo-motoristas/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new MovimentoSaldoMotorista();
	$carteira->searchById($num);
	$obj_carteira = $carteira->getValues();
	$obj_carteira['saldo'] = SaldoMotorista::getSaldo($carteira->getValue('motorista'));
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('saldo-motorista-update',array('saldo_motorista'=> $obj_carteira)); 
});
$app->post('/saldo-motoristas/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],4);
	$carteira = new MovimentoSaldoMotorista();
	$carteira->searchById($num);
	$carteira->setValue('data_aceite', date('Y-m-d H:i:s'));
	//$carteira->setValue('aceite_por', $_SESSION['fastusuario']['id']);
	$carteira->setValue('status', 2);
	$carteira->update();
	$add = new SaldoMotorista();
	$add->setValue('motorista',$carteira->getValue('motorista'));
	$add->addSaldo(SaldoMotorista::getSaldo($carteira->getValue('motorista')) + $carteira->getValue('valor'));
	header('Location:'.DIR_MAE.'saldo-motoristas');
	exit; 
});
//Motoristas
$app->get('/motoristas', function(){
	Operador::verifyLogin();
    $empresa = new Empresa();
    $empresa->searchById($_SESSION['fastusuario']['empresa']);
    
	$provincia = $empresa->getValue('provincia'); 
    if (!$provincia) {
        $provincia = 'Luanda';  
    }

	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'], 5);
    $search = (isset($_GET['search'])) ? $_GET['search'] : "";
    $filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
    $regiao = (isset($_GET['regiao'])) ? $_GET['regiao'] : "";
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if ($search != '') {
        if ($empresa->getValue('tipo') == 'Geral') {
            if ($regiao == '') {
                $pagination = PerfilMotorista::getPageSearch($search, $page, 500);
            } else {
                $pagination = PerfilMotorista::getPageSearchEmpresa('', $regiao, $page, 500);
            }
        } else {
            $pagination = PerfilMotorista::getPageSearchEmpresa($search, $provincia, $page, 500);
        }
    } else if ($filtro != '') {
        if ($filtro == 'Activos') {
            if ($empresa->getValue('tipo') == 'Geral') {
                if ($regiao == '') {
                    $pagination = PerfilMotorista::getPageFilter(1, $page, 500);
                } else {
                    $pagination = PerfilMotorista::getPageFilterEmpresa(1, $regiao, $page, 500);
                }
            } else {
                $pagination = PerfilMotorista::getPageFilterEmpresa(1, $provincia, $page, 500);
            }
        } else if ($filtro == 'Candidaturas') {
            if ($empresa->getValue('tipo') == 'Geral') {
                if ($regiao == '') {
                    $pagination = PerfilMotorista::getPageFilter(0, $page, 500);
                } else {
                    $pagination = PerfilMotorista::getPageFilterEmpresa(0, $regiao, $page, 500);
                }
            } else {
                $pagination = PerfilMotorista::getPageFilterEmpresa(0, $provincia, $page, 500);
            }
        } else if ($filtro == 'Rejeitados') {
            if ($empresa->getValue('tipo') == 'Geral') {
                $pagination = PerfilMotorista::getPageFilter(-1, $page, 500);
            } else {
                $pagination = PerfilMotorista::getPageFilterEmpresa(-1, $provincia, $page, 500);
            }
        }
    } else {
        if ($empresa->getValue('tipo') == 'Geral') {
            $pagination = PerfilMotorista::getPage($page, 50);
        } else {
            $pagination = PerfilMotorista::getPageEmpresa($provincia, $page, 50);
        }
    }

	$pages = array();
    for ($x = 0; $x < $pagination['pages']; $x++) {
        array_push($pages, array(
            'href' => DIR_MAE . 'motoristas?' . http_build_query(array(
                'page' => $x + 1,
                'search' => $search
            )),
            'text' => $x + 1
        ));
    }

	$regioes = Empresa::listAll();

	$page = new PageAdmin(array('data' => array('usuario' => $_SESSION['fastusuario'], 'empresa' => $empresa->getValues())));
	$page->setTpl('motorista', array(
		'motorista' => $pagination['data'],
		'search' => $search,
		'pages' => $pages,
		'total_motoristas' => $pagination['total'],
		'filtro' => $filtro,
		'regioes' => $regioes,
		'regiao' => $regiao
	));
});
$app->get('/motoristas/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('motorista-new');
});
$app->post('/motoristas/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->setValues($_POST);
	$motorista->save();
	header('Location:'.DIR_MAE.'motoristas');
	exit; 
});
$app->get('/motoristas/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
    $page = new PageAdmin(array('header'=>false,'footer'=>false));
	$page->setTpl('cropimagemotorista',array('motorista'=>$motorista->getValues()));	 
});
$app->post('/motoristas/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
    	if(isset($_POST["icon"]))
		{
		$data = $_POST["icon"];
		$image_array_1 = explode(";", $data);
		$ext = substr($image_array_1[0],5);
		$image_array_2 = explode(",", $image_array_1[1]); 
		$data = base64_decode($image_array_2[1]); 
		$imageName = 'img/viatura/'.rand().date('YmdHis').'.png'; 
		$motorista = new PerfilMotorista();
		$motorista->searchById($num);
		$motorista->updateImg($imageName);
		if($ext == 'image/png'){
			if(file_put_contents($imageName, $data)){
				if(file_exists($motorista->getValue('icon'))){
                  if($motorista->getValue('icon') != 'img/sem imagem.jpg')unlink($motorista->getValue('icon'));
				}
				echo "<div class='alert alert-success'> Imagem carregada com sucesso! </div>";
   			}
   		}else{
   			echo "<div class='alert alert-danger'> Erro ao carregar a Imagem! </div>";
   		}
	}	 
});
$app->get('/motoristas/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
    $motorista->delete();
	header('Location:'.DIR_MAE.'motoristas');
	exit;
});
$app->get('/motoristas/:num/activar', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchByIdGeneral($num);
	$motorista->setValue('status_cadastro',1);
	$motorista->setValue('activado_por',$_SESSION['fastusuario']['usuario']);
	$motorista->setValue('data_activacao',date('Y-m-d h:i:s')); 
    $motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit;
});
$app->get('/motoristas/:num/desactivar', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
	$motorista->setValue('status_cadastro',0);
	$motorista->setValue('activado_por',$_SESSION['fastusuario']['usuario']);
	$motorista->setValue('data_activacao',date('Y-m-d h:i:s'));
    $motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit;
});
$app->get('/motoristas/:num/excluir', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
	PerfilMotorista::salvarJustificativa($num, $_GET['justificativa']);
	$motorista->setValue('status_cadastro',-1);
	$motorista->setValue('activado_por',$_SESSION['fastusuario']['usuario']);
	$motorista->setValue('data_activacao',date('Y-m-d h:i:s'));
    $motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit;
});
$app->get('/motoristas/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$perfil_motorista = new PerfilMotorista();
	$perfil_motorista->searchById($num);
	$info_motorista = new InfoMotorista();
	$info_motorista->searchById($num);
	$docs_motorista = new DocsMotorista();
	$docs_motorista->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('motorista-update',array('perfil_motorista'=>$perfil_motorista->getValues(), 'info_motorista'=>$info_motorista->getValues(),'docs_motorista'=>$docs_motorista->getValues(),'lista_empresas'=>$lista_empresas)); 
});
$app->post('/motoristas/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
	$motorista->setValues($_POST);
	$motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit; 
});
$app->post('/infomotoristas/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new InfoMotorista();
	$motorista->searchById($num);
	$motorista->setValues($_POST);
	$motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit; 
});
//Operações 
/*
$app->get('/motoristas', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != ''){
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPageSearch($search, $page,50);
		}else{
			$pagination = PerfilMotorista::getPageSearchEmpresa($search,$empresa->getValue('provincia'), $page,50);
		}
	}else if ($filtro != ''){
		if($filtro=='activos'){
			if($empresa->getValue('tipo')=='Geral'){
				$pagination = PerfilMotorista::getPageFilter(1, $page,50);
			}else{
				$pagination = PerfilMotorista::getPageFilterEmpresa(1,$empresa->getValue('provincia'), $page,50);
			}
		}else if($filtro=='pendentes'){
			if($empresa->getValue('tipo')=='Geral'){
				$pagination = PerfilMotorista::getPageFilter(0, $page,50);
			}else{
				$pagination = PerfilMotorista::getPageFilterEmpresa(0,$empresa->getValue('provincia'), $page,50);
			}
		}else if($filtro=='rejeitados'){
			if($empresa->getValue('tipo')=='Geral'){
				$pagination = PerfilMotorista::getPageFilter(-1, $page,50);
			}else{
				$pagination = PerfilMotorista::getPageFilterEmpresa(-1,$empresa->getValue('provincia'), $page,50);
			}
		}
	}else{
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,50);
		}else{
			$pagination = PerfilMotorista::getPageEmpresa($empresa->getValue('provincia'),$page,50);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'motoristas?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('motorista',array('motorista'=>$pagination['data'],'search'=>$search,'pages'=>$pages,'total_motoristas'=>$pagination['total']));
}); */

$app->get('/operacoes/viagens/:num/cancelar', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$motorista = new PerfilMotorista();
	$motorista->searchById($num);
	$motorista->setValue('status_cadastro',0);
	$motorista->setValue('activado_por',$_SESSION['fastusuario']['usuario']);
	$motorista->setValue('data_activacao',date('Y-m-d h:i:s'));
    $motorista->update();
	header('Location:'.DIR_MAE.'motoristas');
	exit;
});
$app->get('/operacoes/viagens', function() {
    Operador::verifyLogin();
    
    if (!isset($_SESSION['fastusuario']['empresa']) || empty($_SESSION['fastusuario']['empresa'])) {
        throw new Exception('ID de empresa inválido.');
    }

    $obj_empresa = new Empresa();
    $obj_empresa->searchById($_SESSION['fastusuario']['empresa']); 

    $permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'], 5);
    
    $num_motorista = (isset($_GET['num_motorista'])) ? $_GET['num_motorista'] : "-1";
    $filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
    $regiao = (isset($_GET['regiao'])) ? $_GET['regiao'] : '';
    
    if (!empty($regiao)) {
        $obj_empresa->searchById($regiao); 
    }

    if (empty($obj_empresa->getValues())) {
        $nome_regiao = 'Todas regiões';
    } else {
        $nome_regiao = $obj_empresa->getValue('provincia');
    }

    // Obtém os parâmetros de data e página
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d', strtotime(date("Y-m-d") . "-1 week"));
    $ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d', strtotime(date("Y-m-d") . "+1 day"));
    $status_pedido = (isset($_GET['status_pedido'])) ? $_GET['status_pedido'] : "-1";

    // Lógica para definir quais viagens buscar com base nos parâmetros
    if ($num_motorista != -1 && $status_pedido == -1) {
        if ($obj_empresa->getValue('tipo') == 'Geral') {
            $pagination = PerfilMotorista::getPage($page, 500);
            if ($regiao == '') {
                $viagens = Viagens::getPageReportViagemMotorista($num_motorista, $de, $ate, $page, 500);
            } else {
                $viagens = Viagens::getPageReportViagemEmpresaMotorista($num_motorista, $regiao, $de, $ate, $page, 500);
            }
        } else {
            $pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'), $page, 500);
            $viagens = Viagens::getPageReportViagemEmpresaMotorista($num_motorista, $obj_empresa->getValue('id'), $de, $ate, $page, 500);
        }
    } else if ($num_motorista == -1 && $status_pedido != -1) {
        if ($obj_empresa->getValue('tipo') == 'Geral') {
            $pagination = PerfilMotorista::getPage($page, 500);
            if ($regiao == '') {
                $viagens = Viagens::getPageReportViagemStatus($status_pedido, $de, $ate, $page, 500);
            } else {
                $viagens = Viagens::getPageReportViagemEmpresaStatus($regiao, $status_pedido, $de, $ate, $page, 500);
            }
        } else {
            $pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'), $page, 500);
            $viagens = Viagens::getPageReportViagemEmpresaStatus($obj_empresa->getValue('id'), $status_pedido, $de, $ate, $page, 500);
        }
    } else if ($num_motorista != -1 && $status_pedido != -1) {
        if ($obj_empresa->getValue('tipo') == 'Geral') {
            $pagination = PerfilMotorista::getPage($page, 500);
            if ($regiao == '') {
                $viagens = Viagens::getPageReportViagemMotoristaStatus($status_pedido, $num_motorista, $de, $ate, $page, 500);
            } else {
                $viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($regiao, $num_motorista, $status_pedido, $de, $ate, $page, 500);
            }
        } else {
            $pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'), $page, 500);
            $viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($obj_empresa->getValue('id'), $num_motorista, $status_pedido, $de, $ate, $page, 500);
        }
    } else {
        if ($obj_empresa->getValue('tipo') == 'Geral') {
            $pagination = PerfilMotorista::getPage($page, 500);
            if ($regiao == '') {
                $viagens = Viagens::getPageReportViagem($de, $ate, 1, 500);
            } else {
                $viagens = Viagens::getPageReportViagemEmpresa($regiao, $de, $ate, 1, 500);
            }
        } else {
            $pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'), $page, 500);
            $viagens = Viagens::getPageReportViagemEmpresa($obj_empresa->getValue('id'), $de, $ate, 1, 500);
        }
    }

    // Criação da paginação
    $pages = array();
    for ($x = 0; $x < $viagens['pages']; $x++) {
        array_push($pages, array(
            'href' => DIR_MAE . 'viagens?' . http_build_query(array(
                'page' => $x + 1,
                'search' => $num_motorista
            )),
            'text' => $x + 1
        ));
    }

    // Lista todas as empresas
    $regioes = Empresa::listAll();

    // Criação da página de administração
    $page = new PageAdmin(array('data' => array('usuario' => $_SESSION['fastusuario'], 'empresa' => $obj_empresa->getValues())));
    $page->setTpl('viagens', array(
        'motorista' => $pagination['data'],
        'de' => $de,
        'ate' => $ate,
        'viagens' => $viagens['data'],
        'total_viagens' => $viagens['total'],
        'status_pedido' => $status_pedido,
        'regiao' => $regiao,
        'nome_regiao' => $nome_regiao,
        'regioes' => $regioes
    ));
});

//Recebimento de comissões
$app->get('/recebimento-comissao', function(){
	Operador::verifyLogin();

	if (!isset($_SESSION['fastusuario']['empresa']) || empty($_SESSION['fastusuario']['empresa'])) {
        throw new Exception('ID de empresa inválido.');
    }

	//dados da empresa
	$obj_empresa = new Empresa();
	$obj_empresa->searchById($_SESSION['fastusuario']['empresa']);

	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);

	$num_motorista = (isset($_GET['num_motorista'])) ? $_GET['num_motorista'] : "-1";
	$retorno = (isset($_GET['retorno'])) ? $_GET['retorno'] : "";
	//dados do motorista
	$obj_motorista = new PerfilMotorista();
	$dados_motorista = $obj_motorista->searchByTel($num_motorista);

	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
	$regiao = (isset($_GET['regiao'])) ? $_GET['regiao'] : '';
	$obj_empresa->searchById($regiao);
	if(empty($obj_empresa->getValues())){
	    $nome_regiao = 'Todas regiões';
	}else{
		$nome_regiao = $obj_empresa->getValue('provincia');
	}
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d',strtotime(date("Y-m-d")."+1 day"));
	$status_pedido = 8;

	if ($num_motorista != -1 && $status_pedido == -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemMotorista($dados_motorista['dados']['id'],$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaMotorista($dados_motorista['dados']['id'],$regiao, $de, $ate, $page,500);
			}
		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaMotorista($dados_motorista['dados']['id'],$obj_empresa->getValue('id'), $de, $ate, $page,500);
		}
	}else if($num_motorista == -1 && $status_pedido != -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemStatus($status_pedido,$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaStatus($regiao,$status_pedido,$de,$ate, $page,500);
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaStatus($obj_empresa->getValue('id'),$status_pedido,$de,$ate, $page,500);
		}
	}else if($num_motorista != -1 && $status_pedido != -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemMotoristaStatus($status_pedido,$dados_motorista['dados']['id'],$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($regiao, $dados_motorista['dados']['id'], $status_pedido,$de,$ate, $page,500); 
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($obj_empresa->getValue('id'), $dados_motorista['dados']['id'], $status_pedido,$de,$ate, $page,500);
		}
	}
	else{
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagem( $de, $ate, 1, 500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresa($regiao, $de, $ate, 1, 500);
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresa($obj_empresa->getValue('id'), $de, $ate, 1, 500);
		}
	}
	$pages = array();
	for ($x = 0; $x < $viagens['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'viagens?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$num_motorista
			)),
			'text'=>$x+1
		));
	}
	$valor_por_receber = 0;
	$valor_recebido = 0;
	foreach($viagens['data'] as $value){
		if($value['status_comissao'] == 1){
			$valor_por_receber = $valor_por_receber + (($value['comissao']/100) * $value['valor_pago']);
		}else if($value['status_comissao'] == 2){
			$valor_recebido = $valor_recebido + (($value['comissao']/100) * $value['valor_pago']);
		}
	}
    
	$regioes = Empresa::listAll();
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$obj_empresa->getValues())));
	$page->setTpl('recebimento-comissao',array('motorista'=>$pagination['data'],'de'=>$de,'ate'=>$ate,'viagens'=>$viagens['data'],'total_viagens'=>$viagens['total'],'status_pedido'=>$status_pedido,'regiao'=>$regiao, 'nome_regiao'=>$nome_regiao,'regioes'=>$regioes,'dados_motorista'=>$dados_motorista['dados'],'retorno'=>$retorno,'num_motorista'=>$num_motorista,'valor_recebido'=>number_format($valor_recebido,2),'valor_por_receber'=>number_format($valor_por_receber,2)));
});
$app->post('/recebimento-comissao', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$pagamento = new PagamentoViagem();
    foreach($_POST as $value){
		$pagamento->searchByViagem($value);
		$pagamento->setValue('status_comissao','2');
		$pagamento->update();
	}
	header('Location:'.DIR_MAE.'recebimento-comissao?retorno=Registos efectuados com sucesso!');
	exit;
});
//Filiais
$app->get('/filiais', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {
		$pagination = Empresa::getPageSearch($search, $page);
	} else {
		$pagination = Empresa::getPage($page);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'viaturas?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('filial',array('filiais'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/filiais/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('filial-new');
});
$app->post('/filiais/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$obj = new Empresa();
	$obj->setValues($_POST);
	$obj->setValue('tipo', 'Filial');
	$obj->save();
	header('Location:'.DIR_MAE.'filiais');
	exit; 
});
$app->get('/filiais/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$obj = new Empresa();
	$obj->searchById($num);
    $obj->delete();
	header('Location:'.DIR_MAE.'filiais');
	exit;
});
$app->get('/filiais/:num/delete-banco-:num_empresa', function($num, $num_empresa){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$obj = new BancoEmpresa();
	$obj->searchById($num);
    $obj->delete();
	header('Location:'.DIR_MAE.'filiais/'.$num_empresa);
	exit;
});
$app->post('/filiais/:num/banco', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$obj = new BancoEmpresa();
	$obj->setValues($_POST);
	$obj->setValue('empresa', $num);
	$obj->save();
	header('Location:'.DIR_MAE.'filiais/'.$num);
	exit;
});
$app->get('/filiais/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$contas_bancarias = BancoEmpresa::listAll($num); 
	$obj = new Empresa();
	$obj->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('filial-update',array('filial'=>$obj->getValues(), 'contas_bancarias'=>$contas_bancarias)); 
});
$app->post('/filiais/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$obj = new Empresa();
	$obj->searchById($num);
	$obj->setValues($_POST);
	$obj->update();
	header('Location:'.DIR_MAE.'filiais');
	exit; 
});
//Viaturas
$app->get('/viaturas', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {
		$pagination = Viaturas::getPageSearch($search, $page);
	} else {
		$pagination = Viaturas::getPage($page);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'viaturas?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('viatura',array('viaturas'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/viaturas/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('viatura-new');
});
$app->post('/viaturas/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$user = new Viaturas();
	$user->setValues($_POST);
	$user->save();
	header('Location:'.DIR_MAE.'viaturas');
	exit; 
});
$app->get('/viaturas/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viatura = new Viaturas();
	$viatura->searchById($num);
    $page = new PageAdmin(array('header'=>false,'footer'=>false));
	$page->setTpl('cropimageviatura',array('viaturas'=>$viatura->getValues()));	 
});
$app->post('/viaturas/img-:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
    	if(isset($_POST["icon"]))
		{
		$data = $_POST["icon"];
		$image_array_1 = explode(";", $data);
		$ext = substr($image_array_1[0],5);
		$image_array_2 = explode(",", $image_array_1[1]); 
		$data = base64_decode($image_array_2[1]); 
		$imageName = 'img/viatura/'.rand().date('YmdHis').'.png'; 
		$viatura = new Viaturas();
		$viatura->searchById($num);
		$viatura->updateImg($imageName);
		if($ext == 'image/png'){
			if(file_put_contents($imageName, $data)){
				if(file_exists($viatura->getValue('icon'))){
                  if($viatura->getValue('icon') != 'img/sem imagem.jpg')unlink($viatura->getValue('icon'));
				}
				echo "<div class='alert alert-success'> Imagem carregada com sucesso! </div>";
   			}
   		}else{
   			echo "<div class='alert alert-danger'> Erro ao carregar a Imagem! </div>";
   		}
	}	 
});
$app->get('/viaturas/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viatura = new Viaturas();
	$viatura->searchById($num);
    $viatura->delete();
	header('Location:'.DIR_MAE.'viaturas');
	exit;
});
$app->get('/viaturas/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viaturas = new Viaturas();
	$viaturas->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('viatura-update',array('viatura'=>$viaturas->getValues())); 
});
$app->post('/viaturas/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viatura = new Viaturas();
	$viatura->searchById($num);
	$viatura->setValues($_POST);
	$viatura->update();
	header('Location:'.DIR_MAE.'viaturas');
	exit; 
});
//Tarifas
$app->get('/tarifas', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Tarifas::getPageSearch($search,$page,50);
		}else{
			$pagination = Tarifas::getPageSearchEmpresa($search,$_SESSION['fastusuario']['empresa'], $page,50);
		}
	} else {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Tarifas::getPage($page,50);
		}else{
			$pagination = Tarifas::getPageEmpresa($_SESSION['fastusuario']['empresa'],$page,50);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'tarifas?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('tarifa',array('tarifa'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/tarifas/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viaturas = Viaturas::listAll();
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('tarifa-new',array('viaturas'=>$viaturas,'lista_empresas'=>$lista_empresas));
});
$app->post('/tarifas/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$tarifas = new Tarifas(); 
	$tarifas->setValue('operador',$_SESSION['fastusuario']['usuario']);
	if($empresa->getValue('tipo')!='Geral'){
		$_POST['empresa'] = $_SESSION['fastusuario']['empresa'];
	}
	$tarifas->setValues($_POST);
	$tarifas->save();
	header('Location:'.DIR_MAE.'tarifas');
	exit; 	
});
$app->get('/tarifas/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$tarifas = new Tarifas();
	$tarifas->searchById($num);
    $tarifas->delete();
	header('Location:'.DIR_MAE.'tarifas');
	exit;
});
$app->get('/tarifas/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viaturas = Viaturas::listAll();
	$tarifa = new Tarifas();
	$tarifa->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('tarifa-update',array('tarifa'=>$tarifa->getValues(),'viaturas'=>$viaturas,'lista_empresas'=>$lista_empresas)); 
});
$app->post('/tarifas/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$tarifas = new Tarifas();
	$tarifas->searchById($num); 
	$_POST['operador'] = $_SESSION['fastusuario']['usuario'];
	$tarifas->setValues($_POST);
	$tarifas->update();
	header('Location:'.DIR_MAE.'tarifas');
	exit; 
});
//Comições
$app->get('/comissao', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != '') {
		$pagination = Comissao::getPageSearch($search, $page,50);
	} else {
		$pagination = Comissao::getPage($page,50);
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'comissao?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('comissao',array('comissao'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/comissao/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viaturas = Viaturas::listAll();
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('comissao-new',array('viaturas'=>$viaturas));
});
$app->post('/comissao/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$comissao = new Comissao(); 
	$comissao->setValue('operador',$_SESSION['fastusuario']['usuario']);
	$comissao->setValues($_POST);
	$comissao->save();
	header('Location:'.DIR_MAE.'comissao');
	exit; 	
});
$app->get('/comissao/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$comissao = new Comissao();
	$comissao->searchById($num);
    $comissao->delete();
	header('Location:'.DIR_MAE.'comissao');
	exit;
});
$app->get('/comissao/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$viaturas = Viaturas::listAll();
	$comissao = new Comissao();
	$comissao->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('comissao-update',array('comissao'=>$comissao->getValues(),'viaturas'=>$viaturas)); 
});
$app->post('/comissao/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$comissao = new Comissao();
	$comissao->searchById($num); 
	$_POST['operador'] = $_SESSION['fastusuario']['usuario'];
	$comissao->setValues($_POST);
	$comissao->update();
	header('Location:'.DIR_MAE.'comissao');
	exit; 
});
//Cupom
$app->get('/cupom', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Cupom::getPageSearch($search, $page,50);
		}else{
			$pagination = Cupom::getPageSearchEmpresa($search,$_SESSION['fastusuario']['empresa'], $page,50);
		}
	} else {
		if($empresa->getValue('tipo')=='Geral'){
			$pagination = Cupom::getPage($page,50);
		}else{
			$pagination = Cupom::getPageEmpresa($_SESSION['fastusuario']['empresa'],$page,50);
		}
	}
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			'href'=>DIR_MAE.'cupom?'.http_build_query(array(
				'page'=>$x+1,
				'search'=>$search
			)),
			'text'=>$x+1
		));

	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('cupom',array('cupom'=>$pagination['data'],'search'=>$search,'pages'=>$pages));
});
$app->get('/cupom/new', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('cupom-new',array('lista_empresas'=>$lista_empresas));
});
$app->post('/cupom/new', function(){	
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	if($empresa->getValue('tipo')!='Geral'){
		$_POST['empresa'] = $_SESSION['fastusuario']['empresa'];
	} 
	$cupom = new Cupom();
	$cupom->setValues($_POST);
	$cupom->save();
	header('Location:'.DIR_MAE.'cupom');
	exit; 	
});
$app->get('/cupom/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$cupom = new Cupom();
	$cupom->searchById($num);
    $cupom->delete();
	header('Location:'.DIR_MAE.'cupom');
	exit;
});
$app->get('/cupom/:num', function($num){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$cupom = new Cupom();
	$cupom->searchById($num);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('cupom-update',array('cupom'=>$cupom->getValues(),'lista_empresas'=>$lista_empresas)); 
});
$app->post('/cupom/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$cupom = new Cupom();
	$cupom->searchById($num);
	$cupom->setValues($_POST);
	$cupom->update();
	header('Location:'.DIR_MAE.'cupom');
	exit; 
});
//Configurações
$app->get('/config', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$moeda = Moeda::listAll();
	$formaspagamento = FormasPagamento::listAll();
    $configparagem = new ConfigParagem();
	$configparagem->searchById(1);
	$pesquisa = new Pesquisa();
	$pesquisa->searchById(1);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('config',array('configparagem'=>$configparagem->getValues(),'pesquisa'=>$pesquisa->getValues(),'formaspagamento'=>$formaspagamento,'moeda'=>$moeda));
});
$app->post('/config/fpagamentos/new', function(){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$fpagamento = new FormasPagamento();
	$fpagamento->setValue('operador',$_SESSION['fastusuario']['usuario']);
	$fpagamento->setValues($_POST);
	$fpagamento->save();
	header('Location:'.DIR_MAE.'config');
	exit; 	
});
$app->get('/config/fpagamentos/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$fpagamento = new FormasPagamento(); 
    $fpagamento->searchById($num);
	$fpagamento->delete();
	header('Location:'.DIR_MAE.'config');
	exit; 	
});
$app->post('/config/moeda/new', function(){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$moeda = new Moeda(); 
	$moeda->setValue('operador',$_SESSION['fastusuario']['usuario']);
	$moeda->setValues($_POST);
	$moeda->save();
	header('Location:'.DIR_MAE.'config');
	exit; 	
});
$app->get('/config/moeda/:num/delete', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$moeda = new Moeda(); 
	$moeda->searchById($num);
	$moeda->delete();
	header('Location:'.DIR_MAE.'config');
	exit; 	
});
$app->post('/config/paragem/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$configparagem = new ConfigParagem();
	$configparagem->searchById($num);
	$configparagem->setValues($_POST);
	$configparagem->update();
	header('Location:'.DIR_MAE.'config');
	exit; 
});
$app->post('/config/pesquisa/:num', function($num){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],6);
	$pesquisa = new Pesquisa();
	$pesquisa->searchById($num);
	$pesquisa->setValues($_POST);
	$pesquisa->setValue('operador',$_SESSION['fastusuario']['usuario']);
	$pesquisa->update();
	header('Location:'.DIR_MAE.'config');
	exit; 
});
//FINANÇAS
$app->get('/report/viagens', function(){
	Operador::verifyLogin();

	error_log($_SESSION['fastusuario']['empresa']);
	$obj_empresa = new Empresa();
    $obj_empresa->searchById($_SESSION['fastusuario']['empresa']);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'], 5);
    $num_motorista = (isset($_GET['num_motorista'])) ? $_GET['num_motorista'] : "-1";

	$obj_motorista = new PerfilMotorista();
    $dados_motorista = $obj_motorista->searchByTel($num_motorista);

	$filtro = (isset($_GET['filtro'])) ? $_GET['filtro'] : "";
    $regiao = (isset($_GET['regiao'])) ? $_GET['regiao'] : "";
    
	$obj_empresa->searchById($regiao);
    if(empty($obj_empresa->getValues())){
        $nome_regiao = 'Todas regiões';
    } else {
        $nome_regiao = $obj_empresa->getValue('provincia');
    }

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d', strtotime(date("Y-m-d")."-1 week"));
    $ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d', strtotime(date("Y-m-d")."+1 day"));
    $status_pedido = 8;

	if ($num_motorista != -1 && $status_pedido == -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemMotorista($dados_motorista['dados']['id'],$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaMotorista($dados_motorista['dados']['id'],$regiao, $de, $ate, $page,500);
			}
		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaMotorista($dados_motorista['dados']['id'],$obj_empresa->getValue('id'), $de, $ate, $page,500);
		}
	}else if($num_motorista == -1 && $status_pedido != -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemStatus($status_pedido,$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaStatus($regiao,$status_pedido,$de,$ate, $page,500);
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaStatus($obj_empresa->getValue('id'),$status_pedido,$de,$ate, $page,500);
		}
	}else if($num_motorista != -1 && $status_pedido != -1){
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagemMotoristaStatus($status_pedido,$dados_motorista['dados']['id'],$de, $ate, $page,500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($regiao, $dados_motorista['dados']['id'], $status_pedido,$de,$ate, $page,500);
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresaMotoristaStatus($obj_empresa->getValue('id'), $dados_motorista['dados']['id'], $status_pedido,$de,$ate, $page,500);
		}
	}
	else{
		if($obj_empresa->getValue('tipo')=='Geral'){
			$pagination = PerfilMotorista::getPage($page,500);
			if($regiao == ''){
				$viagens = Viagens::getPageReportViagem( $de, $ate, 1, 500);
			}else{
				$viagens = Viagens::getPageReportViagemEmpresa($regiao, $de, $ate, 1, 500);
			}

		}else{
			$pagination = PerfilMotorista::getPageEmpresa($obj_empresa->getValue('provincia'),$page,500);
			$viagens = Viagens::getPageReportViagemEmpresa($obj_empresa->getValue('id'), $de, $ate, 1, 500);
		}
	}

	$pages = array();
    for ($x = 0; $x < $viagens['pages']; $x++) {
        array_push($pages, array(
            'href' => DIR_MAE.'viagens?'.http_build_query(array(
                'page' => $x+1,
                'search' => $num_motorista
            )),
            'text' => $x+1
        ));
    }
	
	$valor_total = 0;
	foreach ($viagens['data'] as $value) {
        $valor_total = $valor_total + $value['valor_pago'];
    }
    $valor_total = number_format($valor_total, 2, ',', ' ');

	$regioes = Empresa::listAll();
	$page = new PageAdmin(array('data' => array('usuario' => $_SESSION['fastusuario'], 'empresa' => $obj_empresa->getValues())));
    $page->setTpl('report-viagens', array(
        'motorista' => $pagination['data'],
        'de' => $de,
        'ate' => $ate,
        'viagens' => $viagens['data'],
        'total_viagens' => $viagens['total'],
        'status_pedido' => $status_pedido,
        'regiao' => $regiao,
        'nome_regiao' => $nome_regiao,
        'regioes' => $regioes,
        'valor_total' => $valor_total,
        'num_motorista' => $num_motorista,
        'dados_motorista' => $dados_motorista['dados']
    ));
});
$app->get('/report/produtividade', function(){
	Operador::verifyLogin();
	$obj_empresa = new Empresa();
	$obj_empresa->searchById($_SESSION['fastusuario']['empresa']);

	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],5);
	$num_motorista = (isset($_GET['num_motorista'])) ? $_GET['num_motorista'] : "todos";
	
	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d',strtotime(date("Y-m-d")."+1 day"));

	if($num_motorista != 'todos'){
		$pedidos = Pedidos::getPageReportPedidoMotorista($num_motorista, $de, $ate);
	}else{
		$pedidos = Pedidos::getPageReportPedido($de, $ate);
	}
	$mapa = array();
	$result = array('atendidos'=> 0, 'natendidos'=>0, 'cancelados'=>0, 'realizados'=>0, 'pendentes'=>0);
	foreach($pedidos['data'] as $value){
		$motorista = $value['motorista'];
		$st0 = Pedidos::getPageReportPedidoStatus($motorista, 0, $de, $ate);
		$st1 = Pedidos::getPageReportPedidoStatus($motorista, 1, $de, $ate);
		$st2  = Pedidos::getPageReportPedidoStatus($motorista, 2, $de, $ate);
		$st3  = Pedidos::getPageReportPedidoStatus($motorista, 3, $de, $ate);
		$st4  = Pedidos::getPageReportPedidoStatus($motorista, 4, $de, $ate);
		$st5  = Pedidos::getPageReportPedidoStatus($motorista, 5, $de, $ate);
		$st6  = Pedidos::getPageReportPedidoStatus($motorista, 6, $de, $ate);
		$st7  = Pedidos::getPageReportPedidoStatus($motorista, 7, $de, $ate);
		$st8 = Pedidos::getPageReportPedidoStatus($motorista, 8, $de, $ate);
		 
		$total = $st0 + $st1 + $st2 + $st3 + $st4 + $st5 + $st6 + $st7 + $st8;

		$pendente = $st1 + $st2 + $st3 + $st4 + $st5 + $st6 + $st7;
		$ta = ($pendente != 0) ? ($pendente * 100 / $total) : 0;
		$tc = ($st0 != 0) ?($st0 * 100 / $total): 0;
		$te = ($st8 != 0) ? ($st8 * 100 / $total): 0;

		//resumos
		$result['atendidos'] = $result['atendidos'] + $st0 + $st2 + $st3 + $st4 + $st5 + $st6 + $st7 + $st8;
		$result['cancelados'] = $result['cancelados'] + $st0;
		$result['realizados'] = $result['realizados'] + $st8;
		$result['pendentes'] = $result['pendentes'] + $pendente;

		$mapa[] = array('nome'=> $value['nome'].' '.$value['apelido'],
		                'telefone'=> $value['telefone'],
						'canceladas'=> $st0,
						'realizadas'=> $st8,
						'pendente'=> $pendente,
						'atendidas' => $total,
						'ta'=> number_format($ta, 2 , ',', ' '),
						'tc'=> number_format($tc, 2 ,  ',', ' '),
						'te' => number_format($te, 2 ,  ',', ' ')
		);	
	}
	$result['natendidos'] = $pedidos['total'] - $result['atendidos'];
	//$valor_total = number_format($valor_total,2,',',' ');
	$regioes = Empresa::listAll();
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$obj_empresa->getValues())));
	$page->setTpl('report-produtividade',array('mapa'=>$mapa,'de'=>$de,'ate'=>$ate,'total'=>$pedidos['total'], 'result'=>$result,'num_motorista'=>$num_motorista));
});
$app->get('/config/financas/geral', function(){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($_SESSION['fastusuario']['empresa']);

	$lista_empresas = Empresa::listAll();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],7);
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('financas-geral',array('lista_empresas'=>$lista_empresas));
});
$app->get('/config/report-viagens/:num', function($num_empresa){
	Operador::verifyLogin();
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],7);

	$empresa = new Empresa();
	$empresa->searchById($num_empresa);

	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d',strtotime(date("Y-m-d")."+1 day"));
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$pagination = Viagens::getPageSearchEmpresa($num_empresa, $de, $ate, $page,50);
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'config/report-viagens/'.$num_empresa.'?'.http_build_query(array(
				'page'=>$x+1,
				'de'=>$de,
				'ate'=>$ate
			)),
			'text'=>$x+1
		));
	}
	$valor_total = 0;
	$comissao = 0;
	foreach ($pagination['data'] as $value) {
		$valor_total = $valor_total + $value['valor'];
		$comissao = $comissao + ($value['valor'] * $value['comissao']/100);
	}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('report-viagens',array('viagens' => $pagination['data'], 'de'=> $de,'ate' => $ate,'pages'=>$pages,'valor_total'=> $valor_total,'comissao' => $comissao,'num_registos'=>$pagination['total']));
});
$app->get('/config/report-motoristas/:num', function($num_empresa){
	Operador::verifyLogin();
	$empresa = new Empresa();
	$empresa->searchById($num_empresa);
	$permissao = Permissao::verifyPermission($_SESSION['fastusuario']['usuario'],7);
	$de = (isset($_GET['de'])) ? $_GET['de'] : date('Y-m-d',strtotime(date("Y-m-d")."-1 week"));
	$ate = (isset($_GET['ate'])) ? $_GET['ate'] : date('Y-m-d');
	$num_motorista = (isset($_GET['num_motorista'])) ? $_GET['num_motorista'] : '';
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$motoristas = PerfilMotorista::listAllEmpresa($empresa->getValue('provincia'));
	$pagination = Viagens::getPageSearchMotoristaEmpresa($num_empresa, $de, $ate, $num_motorista, $page,50);
	$pages = array();
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, array(
			'href'=>DIR_MAE.'config/report-motoristas?'.http_build_query(array(
				'page'=>$x+1,
				'de'=>$de,
				'ate'=>$ate
			)),
			'text'=>$x+1
		));
	}
	$valor_total = 0;
	$comissao = 0;
	foreach ($pagination['data'] as $value) {
		$valor_total = $valor_total + $value['valor'];
		$comissao = $comissao + ($value['valor'] * $value['comissao']/100);
	}
	$obj_motoristas = PerfilMotorista::listName($num_motorista);
	if(count($obj_motoristas)>0){
		$nome_motorista = $obj_motoristas[0]['nome'].' '.$obj_motoristas[0]['apelido'];
	}else{ $nome_motorista = '';}
    $page = new PageAdmin(array('data'=>array('usuario' => $_SESSION['fastusuario'],'empresa'=>$empresa->getValues())));
	$page->setTpl('report-motoristas',array('viagens' => $pagination['data'], 'de'=> $de,'ate' => $ate,'pages'=>$pages,'valor_total'=> $valor_total,'comissao' => $comissao,'num_registos'=>$pagination['total'],'motoristas'=>$motoristas,'nome_motorista'=>$nome_motorista,'num_motorista'=>$num_motorista));
});
//fim Rotas administrativas


$app->run();

 ?>