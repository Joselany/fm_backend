<?php 

namespace Core;

class PageAdmin extends Page{
	public function __construct($opts = array(), $tpl_dir = DIR_MAE."views/admin/")
	{
		parent::__construct($opts, $tpl_dir);
	}
}

?>