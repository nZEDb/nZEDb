<?php
/* This is a smarty/www file and should be moved to the nZEDb_WWW/pages directory? */

use nzedb\Category;
use nzedb\Contents;
use nzedb\Menu;
use nzedb\Users;

class Page extends BasePage
{
	public function __construct()
	{
		parent::__construct();

		$this->page_template = "basepage.tpl";

		define('WWW_THEMES', WWW_TOP . '/themes');

		// Tell Smarty which directories to use for templates
		$this->smarty->setTemplateDir(
			[
				'user'    => nZEDb_THEMES . $this->theme . '/templates',
				'shared'  => nZEDb_THEMES . 'shared/templates',
				'default' => nZEDb_THEMES . 'Default/templates'
			]
		);

		$role = Users::ROLE_GUEST;
		if ($this->userdata != null) {
			$role = $this->userdata["role"];
		}

		$content = new Contents(['Settings' => $this->settings]);
		$menu = new Menu($this->settings);
		$this->smarty->assign('menulist', $menu->get($role, $this->serverurl));
		$this->smarty->assign('usefulcontentlist', $content->getForMenuByTypeAndRole(Contents::TYPEUSEFUL, $role));
		$this->smarty->assign('articlecontentlist', $content->getForMenuByTypeAndRole(Contents::TYPEARTICLE, $role));

		$this->smarty->assign('main_menu', $this->smarty->fetch('mainmenu.tpl'));
		$this->smarty->assign('useful_menu', $this->smarty->fetch('usefullinksmenu.tpl'));
		$this->smarty->assign('article_menu', $this->smarty->fetch('articlesmenu.tpl'));

		$category = new Category(['Settings' => $content->pdo]);
		if ($this->userdata != null) {
			$parentcatlist = $category->getForMenu($this->userdata["categoryexclusions"], $this->userdata['rolecategoryexclusions']);
		} else {
			$parentcatlist = $category->getForMenu();
		}

		// Add in system types to console categories to make the boot strap drop down list less long.
		$consoleCatList = [];
		foreach ($parentcatlist as $parent) {
			if ($parent['title'] === 'Console') {
				foreach ($parent['subcatlist'] as $consoleCat) {
					if (preg_match('/^XBOX/i', $consoleCat['title'])) {
						$consoleCatList['Microsoft'][] = $consoleCat;
					} else if (preg_match('/^([3N]DS|N?GC)$|^WII/i', $consoleCat['title'])) {
						$consoleCatList['Nintendo'][] = $consoleCat;
					} else if (preg_match('/PS[\dXP ]/i', $consoleCat['title'])) {
						$consoleCatList['Sony'][] = $consoleCat;
					} else {
						$consoleCatList['Other'][] = $consoleCat;
					}
				}
				break;
			}
		}

		$this->smarty->assign('consolecatlist', $consoleCatList);
		$this->smarty->assign('parentcatlist', $parentcatlist);
		$this->smarty->assign('catClass', $category);

		$searchStr = '';
		if ($this->page == 'search' && isset($_REQUEST["id"])) {
			$searchStr = (string)$_REQUEST["id"];
		}
		$this->smarty->assign('header_menu_search', $searchStr);

		if (isset($_REQUEST["t"])) {
			$this->smarty->assign('header_menu_cat', $_REQUEST["t"]);
		} else {
			$this->smarty->assign('header_menu_cat', '');
		}
		$header_menu = $this->smarty->fetch('headermenu.tpl');
		$this->smarty->assign('header_menu', $header_menu);
	}

	public function render()
	{
		$this->smarty->assign('page', $this);

		parent::render();
	}

    private function outputMaintenanceMessage() {
        readfile(MAINTENANCE_MODE_HTML_PATH);
        exit();
    }

    public function maintenanceCheck($outputMessage = true) {
        if (MAINTENANCE_MODE_ENABLED &&
            !in_array($_SERVER['REMOTE_ADDR'], MAINTENANCE_MODE_IP_EXCEPTIONS) &&
            file_exists(MAINTENANCE_MODE_HTML_PATH)) {
            if ($outputMessage) {
                $this->outputMaintenanceMessage();
            }
            return true;
        }
        return false;
    }
}
