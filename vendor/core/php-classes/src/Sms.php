<?php

namespace Core;

class Sms
{
	//netsms
	public static function send2($destinatario,$mensagem)
	{
   		$data = json_encode(
			array("mensagem"=>array(
					"accao"=> "enviar_sms",
					"chave_entidade"=> "sYgeFS65d5s62feEJc5dH5662KT",
					"destinatario"=> $destinatario,
					"descricao_sms"=> $mensagem
					)));

		$curl = curl_init('https://netsms.co.ao/app/appi/');
		curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER,
        array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$json_response = curl_exec($curl);
		curl_close($curl);

	}
	//sms ilico
	public static function send($destinatario, $mensagem){
		$url = 'https://api.smsillico-ao.com/sendsms/json/';
		$jsondata = [
			"authentification" => [
				"username" => "fastmoto",
				"password" => "smsillico"
			],
			"message" => [
				[
					"sender" => "fastmoto",
					"text" => $mensagem,
					"recipients" => [
						[
							"gsm" => $destinatario
						]
					]
				]
			]
		];
		$jsondata = json_encode($jsondata);
		$ch = curl_init();
		Curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		$response = curl_exec($ch);
		
		if( $response === false){
			//echo 'curl error: ' .curl_error($ch);
		} else{
			//print_r($response);
		}

		curl_close($ch);
	}
	//sms ilico 2
	public static function send3($destinatario, $mensagem){
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "http://api.smsillico-ao.com/Sendsms/plain?username=fastmoto&password=smsillico&sender=fastmoto&text=".$mensagem."&recipients=".$destinatario,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "GET",
			));

			$response = curl_exec($curl);

			curl_close($curl);
			//echo $response;

	
	}

}
