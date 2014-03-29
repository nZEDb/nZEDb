<?php

class Net_SmartIRC_module_PingFix
{
    public $name = 'PingFix';
    public $version = '1.0';
    public $description = 'An active-pinging system to keep the bot from dropping the connection';
    public $author = 'Garrett W.';
    public $license = 'LGPL';

    private $irc;
    private $thid;

    function __construct (&$irc) {
        $this->irc = $irc;
        $this->thid = $this->irc->registerTimehandler(
            $this->irc->_rxtimeout/8*1000, $this, 'pingCheck'
        );
    }

    function __destruct () {
        $this->irc->unregisterTimeid($this->thid);
    }

    function pingCheck () {
        if (time() - $this->irc->_lastrx > $this->irc->_rxtimeout) {
            $this->irc->reconnect();
            $this->irc->_lastrx = time();
        } elseif (time() - $this->irc->_lastrx > $this->irc->_rxtimeout/2) {
            $this->irc->_send('PING '.$this->irc->_address, SMARTIRC_CRITICAL);
        }
    }
}
?>
