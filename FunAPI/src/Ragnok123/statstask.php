<?php

namespace Ragnok123;

use pocketmine\scheduler\PluginTask;

class statstask extends PluginTask{

    public function __construct(FunAPI $plugin){
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }

    public function onRun($tick){
		$this->plugin->sendStats();
    }

}