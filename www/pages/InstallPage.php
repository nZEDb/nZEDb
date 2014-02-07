<?php
require_once '../../smarty/Smarty.class.php';

class InstallPage
{
	public $title = '';
	public $content = '';
	public $head = '';
	public $page_template = '';
	public $smarty = '';
	public $error = false;

	function  __construct()
	{
		@session_start();

		$this->smarty = new Smarty();

		$this->smarty->setTemplateDir(realpath('../install/templates/'));
		$this->smarty->setCompileDir(realpath('../../smarty/templates_c/'));
		$this->smarty->setConfigDir(realpath('../../smarty/configs/'));
		$this->smarty->setCacheDir(realpath('../../smarty/cache/'));
	}

	public function addToHead($headcontent)
	{
		$this->head = $this->head . "\n" . $headcontent;
	}

	public function render()
	{
		$this->page_template = "installpage.tpl";
		$this->smarty->display($this->page_template);
	}

	public function isPostBack()
	{
		return (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST");
	}

	public function isSuccess()
	{
		return isset($_GET['success']);
	}
}
