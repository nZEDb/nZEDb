<?php


class InstallPage
{
	public $title = '';
	public $content = '';
	public $head = '';
	public $page_template = '';

	/**
	 * @var Smarty
	 */
	public $smarty;

	public $error = false;

	public function  __construct()
	{
		@session_start();

		$this->smarty = new Smarty();

		$this->smarty->setTemplateDir(realpath('../install/templates/'));
		$this->smarty->setCompileDir(nZEDb_RES . 'smarty/templates_c/');
		$this->smarty->setConfigDir(nZEDb_RES . 'smarty/configs/');
		$this->smarty->setCacheDir(nZEDb_RES . 'smarty/cache/');
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
