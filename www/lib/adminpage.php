<?php

require_once("config.php");
require_once(WWW_DIR."/lib/framework/basepage.php");
require_once(WWW_DIR."/lib/users.php");

class AdminPage extends BasePage
{    
	function AdminPage($allowmod = false)
	{	
		$this->template_dir = 'admin';
		parent::BasePage();
		
		$users = new Users();
		if (!$users->isLoggedIn() || !isset($this->userdata["role"]))
			$this->show403(true);
		
		// if the user isn't an admin or mod then access is denied, OR if they're a mod and mods aren't allowed then access is denied
		if (($this->userdata["role"] != Users::ROLE_ADMIN && $this->userdata["role"] != Users::ROLE_MODERATOR) || ($this->userdata["role"] == Users::ROLE_MODERATOR && $allowmod === false))
			$this->show403(true);
		
	}	
	
	public function render() 
	{			
		$this->smarty->assign('page',$this);
		
		$admin_menu = $this->smarty->fetch('adminmenu.tpl');
		$this->smarty->assign('admin_menu',$admin_menu);
		
		$this->page_template = "baseadminpage.tpl";				
		
		parent::render();
	}
}

?>
