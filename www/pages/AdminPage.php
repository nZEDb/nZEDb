<?php
require_once nZEDb_ROOT . 'app' . DS . 'config' . DS . 'bootstrap' . DS . 'libraries.php';

use nzedb\Category;
use nzedb\Users;

class AdminPage extends BasePage
{
	public function __construct($allowModerator = false)
	{
		parent::__construct();

		$this->page_template = 'baseadminpage.tpl';

		define('WWW_THEMES', WWW_TOP . '/../themes');

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

		$category = new Category();
		$this->smarty->assign('catClass', $category);

		$this->smarty->setTemplateDir(
			[
				'admin'		=> nZEDb_THEMES . 'shared/templates/admin',
				'shared'	=> nZEDb_THEMES . 'shared/templates',
				'default'	=> nZEDb_THEMES . 'Default/templates'
			]
		);
	}

	public function render()
	{
		$this->smarty->assign('page', $this);

		$admin_menu = $this->smarty->fetch('adminmenu.tpl');
		$this->smarty->assign('admin_menu', $admin_menu);

		parent::render();
	}
}
