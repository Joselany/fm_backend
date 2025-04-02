<?php 

namespace Core;
use Rain\Tpl;

class Page{
	private $tpl;
	private $options = array();
	private $default = array("header"=>true,"footer"=>true,"data"=>array());
	
	public function __construct($opt = array(),$tpl_dir = DIR_MAE."views/"){
		// config
		$config = array(
					"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
					"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
					"debug"         => false // set to false to improve the speed
				   );

		Tpl::configure( $config );
		
		$this->options = array_merge($this->default,$opt);
		$this->tpl = new Tpl();
		$this->setData($this->options["data"]);
		if($this->options["header"] == true )$this->tpl->draw("header");
	}
	private function setData($data = array()){ //to set values valores in tamplets
		foreach($data as $key => $value){
			$this->tpl->assign($key,$value);
		}
	}
	public function setTpl($name, $data = array(), $returnHTML = false){ //to draw the body of website
		$this->setData($data);
		$this->tpl->draw($name,$returnHTML);
	}
	public function __destruct(){
		if($this->options["footer"] == true )$this->tpl->draw("footer");
	}
}