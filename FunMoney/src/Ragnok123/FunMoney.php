<?php

namespace Ragnok123;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\BatSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as F;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\entity\Effect;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\utils\Config;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;

class FunMoney extends PluginBase implements Listener {
//	public $mysqli;
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->owp = $this->getServer()->getPluginManager()->getPlugin("FunPerms");
      $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
      $this->owl = $this->getServer()->getPluginManager()->getPlugin("FunLanguage");
	}
	
	public function onJoin(PlayerJoinEvent $e) {
		$p = $e->getPlayer();
		$username = strtolower($p->getName());
	}
	
	public function sendAllPlayers($message) {
		foreach($this->getServer()->getOnlinePlayers() as $p) {
			$p->sendMessage($message);
		}
	}
	public function give($username, $id, $meta, $count) {
		$player = $this->getServer()->getPlayer($username);
		if($player Instanceof Player) {
			$inventory = $player->getInventory();
			$inventory->addItem(new Item($id, $meta, $count));
		}
		}


   public function newLevel(PlayerMoveEvent $e){
   $level = $this->getLevel($e->getPlayer()->getName());
   $exp = $this->getExp($e->getPlayer()->getName());
   if($exp == 100){
      $this->addLevel($e->getPlayer()->getName(), 1);
      $this->addExp($e->getPlayer()->getName(), -100);
      $e->getPlayer()->sendMessage($this->api->prefix. " §aYou have reached level §b" .$level);
      }
  }


    public function onCommand(CommandSender $player, Command $cmd, $label, array $args) {
		$nickname = $player->getName();
        switch ($cmd->getName()) {
			case "money":
			if(isset($args[0])) {
				switch(strtolower($args[0])) {
					case "bank":
					$coins = $this->getCoins($nickname);
					$gems = $this->getGems($nickname);
              $lang = $this->owl->getLanguage($player->getName());
					$player->sendMessage($this->api->prefix .F::GREEN. " Coins: " .F::WHITE. $coins. "\n" .F::GREEN. ". Gems: " .F::WHITE. $gems);
					break;
					case "addcoins":
					if(isset($args[1])) {
						if(isset($args[2])) {
							if($player->isOp()) {
								$this->addCoins($args[1], $args[2]);
								$player->sendMessage($this->api->prefix .F::GREEN. " Gived " .F::WHITE. $args[2]. F::YELLOW. "  §e● §ato player " .F::AQUA. $args[1]. F::GREEN. ".");
							}
						} else {
							$player->sendMessage($this->api->prefix .F::RED. " Count.");
						}
					} else {
						$player->sendMessage($this->api->prefix .F::RED. " Nickname.");
					}
					break;
					case "addgems":
					if(isset($args[1])) {
						if(isset($args[2])) {
							if($player->isOp()) {
								$this->addGems($args[1], $args[2]);
								$player->sendMessage($this->api->prefix .F::GREEN. " Gived " .F::WHITE. $args[2]. F::YELLOW. "  §egems §ato player " .F::AQUA. $args[1]. F::GREEN. ".");
							}
						} else {
							$player->sendMessage($this->api->prefix .F::RED. " Count.");
						}
					} else {
						$player->sendMessage($this->api->prefix .F::RED. " Nickname.");
					}
					break;
					case "addexp":
					if(isset($args[1])) {
						if(isset($args[2])) {
							if($player->isOp()) {
								$this->addExp($args[1], $args[2]);
								$player->sendMessage($this->api->prefix .F::GREEN. " Gived " .F::WHITE. $args[2]. F::YELLOW. "  §eexp §ato player " .F::AQUA. $args[1]. F::GREEN. ".");
							}
						} else {
							$player->sendMessage($this->api->prefix .F::RED. " Count.");
						}
					} else {
						$player->sendMessage($this->api->prefix .F::RED. " Nickname.");
					}
					break;
					case "addlevel":
					if(isset($args[1])) {
						if(isset($args[2])) {
							if($player->isOp()) {
								$this->addLevel($args[1], $args[2]);
								$player->sendMessage($this->api->prefix .F::GREEN. " Gived " .F::WHITE. $args[2]. F::YELLOW. "  §elevels §ato player " .F::AQUA. $args[1]. F::GREEN. ".");
							}
						} else {
							$player->sendMessage($this->api->prefix .F::RED. " Count.");
						}
					} else {
						$player->sendMessage($this->api->prefix .F::RED. " Nickname.");
					}
					break;
				}
			} else {
				$line[1] = $this->api->prefix. " §cType §a/money bank";
				$player->sendMessage($this->api->prefix .F::GREEN. " Coins: " .F::WHITE. $coins. "\n" .F::GREEN. ". Gems: " .F::WHITE. $gems);
         }
		}
	}
	
	//Cast pl pro MySQL
	
	public function checkAcc($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
        $user = mysqli_fetch_assoc($result);
        if($user) {
	        return true;
        } else {
	        return false;
        }
	}
	
	public function createData($username) {
	    $username = strtolower($username);
        if(!$this->checkAcc($username)) {
            $this->api->mysqli->query("INSERT INTO `money` (`id`, `nickname`, `coins`, `gems`, `exp`, `level`) VALUES (NULL, '".$username."', '1', '1', '0', '0')");
		}
	}
	
	public function getCoins($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['coins'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['coins'];
		}
	}
	
	public function getGems($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['gems'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['gems'];
		}
	}

	public function getExp($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['exp'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['exp'];
		}
	}

	public function getLevel($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['level'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `money` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['level'];
		}
	}
	
	public function addCoins($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getCoins($username);
			$value = $count + $value;
            $this->api->mysqli->query("UPDATE `money` SET `coins` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " Игроку " .F::GREEN. $username. F::GOLD. " выданы ключи. Теперь их: " .F::GREEN. $value);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `coins` = '".$value."' WHERE `nickname` = '".$username."'");
		}
	}
	
	public function addGems($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getGems($username);
			$value = $count + $value;
            $this->api->mysqli->query("UPDATE `money` SET `gems` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " Игроку " .F::GREEN. $username. F::GOLD. " выданы кейсы. Теперь их: " .F::GREEN. $value);
		} else {
			$count = $this->getCoins($username);
			$value = $count + $value;
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `gems` = '".$value."' WHERE `nickname` = '".$username."'");
		}
	}
	
	public function addExp($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getExp($username);
			$value = $count + $value;
            $this->api->mysqli->query("UPDATE `money` SET `exp` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " Игроку " .F::GREEN. $username. F::GOLD. " выданы ключи. Теперь их: " .F::GREEN. $value);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `exp` = '".$value."' WHERE `nickname` = '".$username."'");
		}
	}

	public function addLevel($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getLevel($username);
			$value = $count + $value;
            $this->api->mysqli->query("UPDATE `money` SET `level` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " Игроку " .F::GREEN. $username. F::GOLD. " выданы ключи. Теперь их: " .F::GREEN. $value);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `level` = '".$value."' WHERE `nickname` = '".$username."'");
		}
	}

	public function removeCoins($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getCoins($username);
			if($count > 0) { $value = $count - $value; } else { $value = 0; }
            $this->api->mysqli->query("UPDATE `money` SET `coins` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " У игрока " .F::GREEN. $username. F::GOLD. " забрали " .F::GREEN. $value. F::GOLD. " ключей.");
		} else {
			$count = $this->getCoins($username);
			if($count > 0) { $value = $count - $value; } else { $value = 0; }
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `coins` = '".$value."' WHERE `nickname` = '".$username."'");
            $this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " У игрока " .F::GREEN. $username. F::GOLD. " забрали " .F::GREEN. $value. F::GOLD. " ключей.");
		}
	}
	
	public function removeGems($username, $value) {
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
			$count = $this->getGems($username);
			if($count > 0) { $value = $count - $value; } else { $value = 0; }
            $this->api->mysqli->query("UPDATE `money` SET `gems` = '".$value."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " У игрока " .F::GREEN. $username. F::GOLD. " забрали " .F::GREEN. $value. F::GOLD. " кейсов.");
		} else {
			$count = $this->getCoins($username);
			if($count > 0) { $value = $count - $value; } else { $value = 0; }
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `money` SET `gems` = '".$value."' WHERE `nickname` = '".$username."'");
            $this->getServer()->getLogger()->info(F::YELLOW. "[FunMoney]" .F::GOLD. " У игрока " .F::GREEN. $username. F::GOLD. " забрали " .F::GREEN. $value. F::GOLD. " кейсов.");
		}
	}
	
	
 
}