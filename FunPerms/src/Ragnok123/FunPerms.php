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

class FunPerms extends PluginBase implements Listener {
	private $playerGroup;
//	private $mysqli;
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
      $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
	}
	
	public function setPrefixTag($player, $color) {
		$group = $this->getGroup($player->getName());
      $level = $this->getServer()->getPluginManager()->getPlugin("FunMoney")->getLevel($player->getName());
		if($group == "user") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "vip") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::BLUE. "VIP " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "premium") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::LIGHT_PURPLE. "Premium " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "helper") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::GREEN. "Helper " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "admin") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::RED. "Admin " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "youtube") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::WHITE. "You" .F::RED. "Tuber " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "owner") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::GOLD. "Owner " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "dev") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::YELLOW. "DEV " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "hladmin") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::RED. "Admin+ " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "hlbuilder") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::RED. "Builder+ " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
		if($group == "builder") {
			$player->setNameTag(F::GRAY. "[" .F::GREEN. $level. F::GRAY. "] " .F::AQUA. "Builder " .$color. $player->getName());
         $player->setDisplayName($player->getNameTag());
		}
	}
	
	public function checkAcc($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `perms` WHERE `nickname` = '".$username."'");
        $user = mysqli_fetch_assoc($result);
        if($user) {
	        return true;
        } else { 
	        return false;
        }
	}
	
	public function updateGroup($username) {
	    $username = strtolower($username);
	    if($this->checkAcc($username)) {
	        $this->playerGroup[$username] = $this->checkGroup($username);
		} else {
		    $this->playerGroup[$username] = "user";
		    $this->createData($username);
		}
	}
	
	public function onJoin(PlayerJoinEvent $e) {
		$player = $e->getPlayer();
		if($this->checkAcc($player->getName())) {
		    $this->updateGroup($player->getName());
			$this->setPrefixTag($player, F::WHITE);
		} else {
		    $this->createData($player->getName());
		}
	}
	
	public function createData($username) {
	    $username = strtolower($username);
        if(!$this->checkAcc($username)) {
            $this->api->mysqli->query("INSERT INTO `perms` (`id`, `nickname`, `group`) VALUES (NULL, '".$username."', 'user')");
			$this->getServer()->getLogger()->info(F::YELLOW. "[Minetox]" .F::GOLD. " Создана таблица игроку " .F::GREEN. $username);
			$this->updateGroup($username);
		}
	}
	
	public function checkGroup($username) {
	    $username = strtolower($username);
        $result = $this->api->mysqli->query("SELECT * FROM `perms` WHERE `nickname` = '".$username."'");
		if($this->checkAcc($username)) {
			$data = $result->fetch_assoc();
			$result->free();
			if(isset($data["group"])){
			    return $data["group"];
			}
		} else {
		    $this->createData($username);
		}
	}
	
	public function getGroup($username) {
	    $username = strtolower($username);
	    if(isset($this->playerGroup[$username])) {
			return $this->playerGroup[$username];
		} else {
		    $this->updateGroup($username);
		}
	}
	
	public function setGroup($username, $group) {
	    $username = strtolower($username);
		$group = strtolower($group);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `perms` SET `group` = '".$group."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[Minetox]" .F::GOLD. " Игрок " .F::GREEN. $username. F::GOLD. " получил группу " .F::GREEN. $group);
			$this->updateGroup($username);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `perms` SET `group` = '".$group."' WHERE `nickname` = '".$username."'");
			$this->updateGroup($username);
		}
	}

    public function onCommand(CommandSender $entity, Command $cmd, $label, array $args) {
        switch ($cmd->getName()) {
            case "perms":
			if($entity->isOp()) {
                if(isset($args[0])) {
				    if($args[0] == "setgroup") {
					    if(isset($args[1])) {
						    if(isset($args[2])) {
						    	if($args[2] == "admin" || $args[2] == "user" || $args[2] == "helper" || $args[2] == "vip" || $args[2] == "premium" || $args[2] == "youtube" || $args[2] == "owner" || $args[2] == "elite" || $args[2] == "builder") {
								    $this->setGroup($args[1], $args[2]);
								    if($this->getServer()->getPlayer($args[1]) Instanceof Player) {
									    $this->getServer()->getPlayer($args[1])->sendMessage($this->api->prefix .F::GREEN. " Now you have " .F::AQUA. $args[2]. F::GREEN. " rank!");
								    }
								    $entity->sendMessage($this->api->prefix .F::GREEN. " You gived rank " .F::AQUA. $args[2] .F::GREEN. " to player " .F::AQUA. $args[1] .F::GREEN. ".");
									$this->getServer()->getLogger()->info(F::YELLOW. "[Minetox]" .F::GREEN. $entity->getName(). " выдал игроку " .F::GREEN. $args[1]. F::GOLD. " группу " .F::GREEN. $args[2]. F::GOLD. ".");
							    } else {
								    $entity->sendMessage($this->api->prefix .F::RED. " This player doesnt exist.");
							    }
							}
						}
					}
				}
			} else {
				$entity->sendMessage($this->api->prefix. F::RED. " Youd doesnt have permission");
			}
		}
	}

 
}