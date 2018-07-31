<?php

namespace Ragnok123;

use pocketmine\scheduler\PluginTask;
use Ragnok123\FunAPI;

class fallenTimeOutTask extends PluginTask {
	public $name;
	public function __construct(FunAPI $owner, $name) {
		parent::__construct ( $owner );
		$this->name = $name;
	}
	public function onRun($currentTick) {
		$this->owner->fallenTimeOut ( $this->name );
	}
}

?>