<?php
/* This is a smarty/www file and should be moved to the nZEDb_WWW/pages directory? */
#require_once './config.php';

use nzedb\Users;

class AdminPage extends BasePage
{
	public function __construct($allowModerator = false)
	{
		parent::__construct();

		if (!$this->users->isLoggedIn() || !isset($this->userdata['role'])) {
			$this->show403(true);
		}

		// If the user isn't an admin or mod then access is denied, OR if they're a mod and mods aren't allowed then access is denied.
		if (
			($this->userdata['role'] != Users::ROLE_ADMIN && $this->userdata['role'] != Users::ROLE_MODERATOR) ||
			($this->userdata['role'] == Users::ROLE_MODERATOR && $allowModerator === false)
		) {
			$this->show403(true);
		}

		$this->smarty->setTemplateDir(
			array(
				'admin'    => nZEDb_WWW . 'themes_shared/templates/admin',
				'frontend' => nZEDb_WWW . 'themes/Default/templates/frontend',
			)
		);
	}

	public function render()
	{
		$this->smarty->assign('page', $this);

		$admin_menu = $this->smarty->fetch('adminmenu.tpl');
		$this->smarty->assign('admin_menu', $admin_menu);

		$this->page_template = 'baseadminpage.tpl';

		parent::render();
	}
}
