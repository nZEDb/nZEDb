<?php
class Install {
	public $DB_TYPE;
	public $DB_HOST = "localhost";
	public $DB_USER;
	public $DB_PASSWORD;
	public $DB_NAME = "nzedb";
	
	public $NNTP_USERNAME;
	public $NNTP_PASSWORD;
	public $NNTP_SERVER;
	public $NNTP_PORT = 119;
	public $NNTP_SSLENABLED = false;
		
	public $WWW_DIR;
	public $SMARTY_DIR;
	public $DB_DIR;
	public $MISC_DIR;
	public $INSTALL_DIR;
	
	public $ADMIN_USER;
	public $ADMIN_PASS;
	public $ADMIN_EMAIL;
	
	public $NZB_PATH;
	
	public $COMPILED_CONFIG;
	
	public $doCheck = false;
	
	public $sha1Check;
	public $mysqlCheck;
	public $gdCheck;
	public $curlCheck;
	public $cacheCheck;
	public $movieCoversCheck;
	public $animeCoversCheck;
	public $musicCoversCheck;
	public $configCheck;
	public $lockCheck;
	public $pearCheck;
	public $schemaCheck;
	public $phpCheck;
	public $timelimitCheck;
	public $memlimitCheck;
	public $rewriteCheck;
	public $opensslCheck;
	public $timezoneCheck;
	
	public $dbConnCheck;
	public $dbNameCheck;
	public $dbCreateCheck;
	
	public $nntpCheck;
	public $adminCheck;
	public $nzbPathCheck;
	
	public $saveConfigCheck;
	public $saveLockCheck;
	
	public $error = false;
	
	function Install() {
		$this->WWW_DIR = dirname(realpath('.'));
		$this->SMARTY_DIR = $this->WWW_DIR.'/lib/smarty';
		$this->DB_DIR = dirname(realpath('..')).'/db';
		$this->MISC_DIR = dirname(realpath('..')).'/misc';
		$this->NZB_PATH = str_replace('\\', '/', dirname(realpath('..'))).'/nzbfiles/';
		$this->INSTALL_DIR = $this->WWW_DIR.'/install';
	}
	
	public function setSession() {
		$_SESSION['cfg'] = serialize($this);
	}
	
	public function getSession() {
		$tmpCfg = unserialize($_SESSION['cfg']);
		$tmpCfg->error = false;
		$tmpCfg->doCheck = false;
		return $tmpCfg;
	}
	
	public function isInitialized() {
		return (isset($_SESSION['cfg']) && is_object(unserialize($_SESSION['cfg'])));
	}
	
	public function isLocked() {
		return (file_exists($this->INSTALL_DIR.'/install.lock') ? true : false);
	}
	
	public function setConfig($tmpCfg) 
	{
		preg_match_all('/define\((.*?)\)/i', $tmpCfg, $matches);
		$defines = $matches[1];
		foreach ($defines as $define) 
		{
			$define = str_replace('\'', '', $define);
			list($defName,$defVal) = explode(',', $define);
			$this->{$defName} = trim($defVal);
		}
	}
	
	public function saveConfig() 
	{
		$tmpCfg = file_get_contents($this->INSTALL_DIR.'/config.php.tpl');
		$tmpCfg = str_replace('%%DB_HOST%%', $this->DB_HOST, $tmpCfg);
		$tmpCfg = str_replace('%%DB_USER%%', $this->DB_USER, $tmpCfg);
		$tmpCfg = str_replace('%%DB_PASSWORD%%', $this->DB_PASSWORD, $tmpCfg);
		$tmpCfg = str_replace('%%DB_NAME%%', $this->DB_NAME, $tmpCfg);
		
		$tmpCfg = str_replace('%%NNTP_USERNAME%%', $this->NNTP_USERNAME, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PASSWORD%%', $this->NNTP_PASSWORD, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SERVER%%', $this->NNTP_SERVER, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PORT%%', $this->NNTP_PORT, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SSLENABLED%%', ($this->NNTP_SSLENABLED?"true":"false"), $tmpCfg);
		
		$this->COMPILED_CONFIG = $tmpCfg;
		return @file_put_contents($this->WWW_DIR.'/config.php', $tmpCfg);
	}
	
	public function saveInstallLock() {
		return @file_put_contents($this->INSTALL_DIR.'/install.lock', '');
	}
}
