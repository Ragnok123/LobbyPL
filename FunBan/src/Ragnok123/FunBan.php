<?php

namespace Ragnok123;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as F;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerPreLoginEvent;

class FunBan extends PluginBase implements Listener {
	public $token;
	
	public function onEnable() {
		$this->owp = $this->getServer()->getPluginManager()->getPlugin("FunPerms");
       $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
      $client = $event->getPlayer()->getClientId();
		if($this->getBanned($player->getName())) {
			$event->setCancelled(true);
			$player->close("", F::GOLD. "You have been banned.");
		}
	}
	
	public function kick($player, $kicker, $reason) {
		if($kicker Instanceof Player) {
			if($this->getServer()->getPlayer($player) Instanceof Player) {
				foreach ($this->getServer()->getOnlinePlayers() as $p) {
					$p->sendMessage($this->api->prefix .F::AQUA. $kicker->getName(). F::GREEN. " kicked player " .F::AQUA. $player. F::GREEN. ".\n".$this->api->prefix." §aReason: " .F::RED. $reason);
				}
				$this->getLogger()->info($this->api->prefix .F::GREEN. $kicker->getName(). F::GOLD. " кикнул игрока " .F::GREEN. $player. F::GOLD. " за " .F::DARK_GREEN. $reason);
				$this->getServer()->getPlayer($player)->kick(F::GOLD. $reason);
			} else {
				$kicker->sendMessage($this->api->prefix .F::RED. " Player is offline.");
			}
		} else {
			if($this->getServer()->getPlayer($player) Instanceof Player) {
				$this->getLogger()->info(F::YELLOW. "[Minetox]" .F::GREEN. " CONSOLE". F::GOLD. " кикнул игрока " .F::GREEN. $player. F::GOLD. " за " .F::DARK_GREEN. $reason);
				$this->getServer()->getPlayer($player)->kick(F::GOLD. $reason);
			} else {
				$kicker->sendMessage($this->api->prefix .F::RED. " Player is offline.");
			}
		}
	}
	
	public function addban($username, $reason, $banner) {
		$username = strtolower($username);
		$bname = $banner->getName();
		$usergroup = $this->owp->getGroup($username);
		if($usergroup == "user" || $usergroup == "vip" || $usergroup == "premium") {
			if($this->getAcc($username)) {
				if(!($this->getBanned($username))) {
					$this->api->mysqli->query('SET CHARACTER SET utf8');
					$this->api->mysqli->query("INSERT INTO `ban` (`id`, `nickname`, `reason`, `banner`) VALUES (NULL, '".$username."', '".$reason."', '".$bname."')");
					if($this->getServer()->getPlayer($username) Instanceof Player) {
						$this->getServer()->getPlayer($username)->close("", F::GOLD. "You have been banned.");
					}
					$banner->sendMessage($this->api->prefix .F::GREEN. " Player " .F::AQUA. $username .F::GREEN. " Was banned. Reason: " .F::RED. $reason. F::GREEN. ".");
					$this->sendUsers($this->api->prefix .F::GREEN. $bname .F::GOLD. " banned player " .F::GREEN. $username. F::GOLD. ".\n".$this->api->prefix." §aReason: " .F::GREEN. $reason. F::GOLD. ".");
				} else {
					$banner->sendMessage($this->api->prefix .F::RED. " This player is banned.");
				}
			} else {
				$banner->sendMessage($this->api->prefix .F::RED. " Cannot ban this player.");
			}
		} else {
			$banner->sendMessage($this->prefix->player .F::RED. " WTF?!");
		}
	}
	
	public function sendUsers($text) {
		foreach ($this->getServer()->getOnlinePlayers() as $p) {
			$p->sendMessage($text);
		}
	}
	
	public function removeBan($username, $banner) {
		$username = strtolower($username);
		if($this->getBanned($username)) {
			$this->api->mysqli->query("DELETE FROM `ban` WHERE `nickname` = '".$username."'");
			$banner->sendMessage($this->api->prefix .F::GREEN. " Plaser " .F::AQUA. $username. F::GREEN. " was unbaned.");
		} else {
			$banner->sendMessage($this->api->prefix .F::RED. " This player isnt banned.");
		}
	}
	
	public function getBanned($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `ban` WHERE `nickname` = '".$username."'");
        $nickname = mysqli_fetch_assoc($result);
        if($nickname) {
	        return true;
        } else {
	        return false;
        }
	}
	
	public function getAcc($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `acc` WHERE `nickname` = '".$username."'");
        $user = mysqli_fetch_assoc($result);
        if($user) {
	        return true;
        } else {
	        return false;
        }
	}
	
    public function onCommand(CommandSender $entity, Command $cmd, $label, array $args) {
		$group = $this->owp->getGroup($entity->getName());
        switch ($cmd->getName()) {
			case "fkick":
			if($entity Instanceof Player) {
				if(isset($args[0])) {
					if(isset($args[1])) {
						if($group == "helper" || $group == "admin" || $group == "dev" || $group == "owner" || $group == "hladmin" || $group == "hlbuilder" || $group == "builder") {
							$this->kick($args[0], $entity, $args[1]);
						}
					} else {
						$entity->sendMessage($this->api->prefix. F::RED. " Reason.");
					}
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			} else {
				if(isset($args[0])) {
					if(isset($args[1])) {
						$this->kick($args[0], $entity, $args[1]);
					} else {
						$entity->sendMessage($this->api->prefix. F::RED. " Reason.");
					}
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			}
			break;
			case "fban":
			if($entity Instanceof Player) {
				if(isset($args[0])) {
					if(isset($args[1])) {
						if($group == "helper" || $group == "admin" || $group == "dev" || $group == "owner" || $group == "hladmin" || $group == "hlbuilder" || $group == "builder") {
							$this->addban($args[0], $args[1], $entity);
						}
					} else {
						$entity->sendMessage($this->api->prefix. F::RED. " Reason.");
					}
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			} else {
				if(isset($args[0])) {
					if(isset($args[1])) {
						$this->addban($args[0], $args[1], $entity);
					} else {
						$entity->sendMessage($this->api->prefix. F::RED. " Reason.");
					}
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			}
			break;
			case "fpardon":
			if($entity Instanceof Player) {
				if(isset($args[0])) {
					if($group == "helper" || $group == "admin" || $group == "dev" || $group == "owner" || $group == "hladmin" || $group == "hlbuilder" || $group == "builder") {
					    $this->removeBan($args[0], $entity);
					} else {
						$entity->sendMessage($this->api->prefix. F::RED. " Fuck you.");
					}
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			} else {
				if(isset($args[0])) {
					$this->removeBan($args[0], $entity);
				} else {
					$entity->sendMessage($this->api->prefix. F::RED. " Nickname.");
				}
			}
			break;
		}
	}
	
}