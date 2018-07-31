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

class FunChat extends PluginBase implements Listener {
	public $oldmsg;
	public $timer;
	public $ts;
	public $mysqli;
	public $playerPrefix;
	
	public function onEnable() {
		$this->owp = $this->getServer()->getPluginManager()->getPlugin("FunPerms");
		$this->owa = $this->getServer()->getPluginManager()->getPlugin("FunAuth");
      $this->owl = $this->getServer()->getPluginManager()->getPlugin("FunLanguage");
       $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new task($this), 15);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function massives(PlayerPreLoginEvent $e) {
		$player = $e->getPlayer();
		$this->oldmsg[$player->getName()] = null;
		$this->timer[$player->getName()] = 0;
		$this->ts[$player->getName()] = false;
	}
	
	
	public function groupManager($player) {
		$group = $this->owp->getGroup($player->getName());
		switch($group) {
			case "user":
			return F::BOLD. F::GRAY. "User";
			break;
			case "vip":
			return F::BLUE. "VIP ";
			break;
			case "premium":
			return F::RED. "Premium ";
			break;
			case "helper":
			return F::GREEN. "Helper ";
			break;
			case "admin":
			return F::RED. "Admin ";
			break;
			case "youtube":
			return F::WHITE. "You" .F::RED. "Tuber ";
			case "owner":
			return F::GOLD. "Owner ";
			break;
			case "dev":
			return F::YELLOW. "DEV ";
			break;
			case "hladmin":
			return F::RED. "Admin+ ";
			break;
			case "builder":
			return F::AQUA. "Builder ";
			break;
			case "hlbuilder":
			return F::GOLD. "Builder+ ";
			break;
		}
	}
	
	public function format($player, $message) {
		$group = $this->owp->getGroup($player->getName());
      $level = $this->getServer()->getPluginManager()->getPlugin("FunMoney")->getLevel($player->getName());
		$gp = $this->groupManager($player);
		switch($group) {
			case "user":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "vip":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "premium":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "helper":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "admin":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "youtube":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "owner":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "dev":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "hladmin":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "hlbuilder":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
			case "builder":
			return F::GRAY. "[" .F::GREEN. $level. F::GRAY . "] " .$gp. F::WHITE. $player->getName(). F::GRAY. " > " .F::GRAY. $message;
			break;
		}
	}
	
	public function chat(PlayerChatEvent $e) {
		$e->setCancelled();
		$player = $e->getPlayer();
		$message = $e->getMessage();
      $lang = $this->owl->getLanguage($player->getName());
		$group = $this->owp->getGroup($player->getName());
		$auth = $this->owa->authSession[$player->getName()];
		$password = $this->owa->returnPassword($player->getName());
        $ips = [".cz", ".eu", ".sk", ".tk", ".com", ".net", "lifeboat", "mineplex", "46.47.183.2", ":19132", "19132"];
        foreach ($ips as $ip){
            if (stripos($e->getMessage(), $ip) !== false){
                $player->kick("§e[FunAPI] §cYou have been kicked\n§e[FunAPI] §cReason: §bAdvertising");
                return;
            }
        }
		if(!$auth) {
			if($message != $password) {
				$this->sendMessage($player, $this->format($player, $message));
			} else {
				$player->sendMessage($this->api->prefix .F::RED. " You are already logged in!");
			}
		}
	}
	
	public function sendMessage($sender, $message) {
        foreach($this->getServer()->getOnlinePlayers() as $p){
			$p->sendMessage($message);
		}
		$this->getServer()->getLogger()->info($message);
	}
	
	public function timerManager() {
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$player = $p->getPlayer()->getName();
			$timer = $this->timer[$player];
			switch($timer) {
				case 11:
				return 1;
				break;
				case 10:
				return 1;
				break;
				case 9:
				return 2;
				break;
				case 8:
				return 3;
				break;
				case 7:
				return 4;
				break;
				case 6:
				return 5;
				break;
				case 5:
				return 6;
				break;
				case 4:
				return 7;
				break;
				case 3:
				return 8;
				break;
				case 2:
				return 8;
				break;
				case 1:
				return 9;
				break;
				case 0:
				return 9;
				break;
			}
		}
	}
	
	public function timer() {
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$player = $p->getPlayer()->getName();
			if($this->ts[$player]) {
				if($this->timer[$player] != 0) {
					$this->timer[$player]++;
					switch($this->timer[$player]) {
						case 1:
						break;
						case 2:
						break;
						case 3:
						break;
						case 4:
						break;
						case 5:
						break;
						case 6:
						break;
						case 7:
						break;
						case 8:
						break;
						case 9:
						break;
						case 10:
						break;
						case 11:
						$this->timer[$player] = 0;
						$this->ts[$player] = false;
						break;
					}
				}
			}
		}
	}
	
}