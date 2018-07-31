<?php

namespace Ragnok123;

use pocketmine\scheduler\PluginTask;

class task extends PluginTask{

    public function __construct(FunChat $plugin){
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }

    public function onRun($tick){
		$this->plugin->timer();
    }

}