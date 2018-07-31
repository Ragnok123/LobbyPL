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
use pocketmine\event\player\PlayerPreLoginEvent;

class FunAuth extends PluginBase implements Listener {
	public $authSession;
	private $mysqli;
	public $passw;
	public $changePass;
	public $registerStep;
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
		$this->lang = $this->getServer()->getPluginManager()->getPlugin("FunLanguage");
	}
	
	public function returnPassword($username) {
		if(isset($this->passw[$username])) {
			return $this->passw[$username];
		} else {
			return 123456;
		}
	}
	
	public function checkAcc($username) {
		$result = $this->api->mysqli->query("SELECT * FROM `acc` WHERE `nickname` = '".$username."'");
		$user = $result->fetch_assoc();
		$result->free();
        if($user) {
	        return true;
        } else {
	        return false;
        }
	}
	
	public function getPassword($username) {
        $result = $this->api->mysqli->query("SELECT * FROM `acc` WHERE `nickname` = '".$username."'");
		if($this->checkAcc($username)) {
			$data = $result->fetch_assoc();
			$result->free();
			if(isset($data["password"])){
				return $data["password"];
			}
		}
	}


	public function getMail($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `acc` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['mail'];
        }
	}
	
	public function loginAcc($username, $password, $player) {
     $lang = $this->lang->getLanguage($player->getName());
		if($this->checkAcc($username)) {
			if(isset($this->passw[$username])) {
				if($password === $this->passw[$username]) {
					$this->closeSession($player);
					$client = $player->getClientId();
					$ip = $player->getAddress();
					if($lang == "en"){
						$player->sendMessage($this->api->prefix. F::GREEN. " You were logged in.");
					}
					if($lang == "cz"){
						$player->sendMessage($this->api->prefix. F::GREEN. " Byl jsi přihlášen.");
					}
					$this->getServer()->getLogger()->info(F::YELLOW. "[BDGCraft]" .F::GOLD. " Hrac " .F::GREEN. $username . F::GOLD. " se prihlasil");
					$this->updateClient($username, $client);
					$this->updateIp($username, json_encode($ip));
				} else {
					if($lang == "en"){
						$player->sendMessage($this->api->prefix. F::RED. " Wrong password.");
					}
					if($lang == "cz"){
						$player->sendMessage($this->api->prefix. F::RED. " Špatné heslo.");
					}
					$this->getServer()->getLogger()->info(F::YELLOW. "[BDGCraft]" .F::GOLD. " Hrac " .F::GREEN. $username . F::GOLD. " se neprihlasil");
				}
			} else {
				$this->passw[$username] = $this->getPassword($username);
				$this->loginAcc($username, $password, $player);
			}
		}
	}
	
	public function registerAcc($username, $password, $player, $lang) {
        if(!$this->checkAcc($username)) {
			$client = $player->getClientId();
			$ip = $player->getAddress();
            $this->api->mysqli->query("INSERT INTO `acc` (`id`, `nickname`, `password`, `mail`, `client`, `ip`) VALUES (NULL, '".$username."', '".$password."', 'any_mail', '".$client."', '".$ip."')");
			unset($this->registerStep[$username]);
			if($lang == "en"){
				$player->sendMessage($this->api->prefix .F::GREEN. " You have registered account.\n " .$this->api->prefix. " §aNow, log in please, write your password to chat");
			}
			if($lang == "cz"){
				$player->sendMessage($this->api->prefix .F::GREEN. " Zargistoval jsi účet.\n " .$this->api->prefix. " §aNyní se přihlaš napsáním hesla eo chatu");
			}
			$this->getServer()->getLogger()->info(F::YELLOW. "[BDGCraft]" .F::GOLD. " Hrac " .F::GREEN. $username . F::GOLD. " se zaregistroval");
		} else {
		    $player->sendMessage($this->api->prefix .F::RED. " This account is registered.");
			$this->getServer()->getLogger()->info(F::YELLOW. "[BDGCraft]" .F::GOLD. " Hrac " .F::GREEN. $username . F::GOLD. " se nedokazal zaregistrovat");
		}
	}
	
	public function clientEquals($username, $client) {
		$username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `acc` WHERE `nickname` = '".$username."'");
		$data = $result->fetch_assoc();
		if($data['client'] == $client) {
			return true;
		} else {
			return false;
		}
	}
	
	public function updateClient($username, $client) {
		$username = strtolower($username);
		$this->api->mysqli->query("UPDATE `acc` SET `client` = '".$client."' WHERE `nickname` = '".$username."'");
	}

	public function updateIp($username, $ip) {
		$username = strtolower($username);
		$this->api->mysqli->query("UPDATE `acc` SET `ip` = '".$ip."' WHERE `nickname` = '".$username."'");
	}
	
	public function OnPlayerJoin(PlayerJoinEvent $e) {
		$p = $e->getPlayer();
		$username = $p->getName();
		$client = $p->getClientId();
		$lang = $this->lang->getLanguage($p->getName());
		
		$this->changePass[strtolower($username)] = false;
		
		if($this->clientEquals($username, $client)) {
        if($lang == "en"){
			$p->sendMessage($this->api->prefix. F::GREEN. " You were automatically logged in.");
        }
        if($lang == "cz"){
			$p->sendMessage($this->api->prefix. F::GREEN. " Byl jsi automaticky přihlášen.");
        }
		$this->closeSession($p);
         $this->updateClient($p, $p->getClientId());
         $this->updateIp($p, json_encode($p->getAddress()));
		} else {
            $this->openSession($p);
		}
	}
	
	public function OnPlayerQuit(PlayerQuitEvent $e) {
		$player = $e->getPlayer();
		$username = $player->getName();
		$this->changePass[strtolower($username)] = false;
		$this->closeSession($player);
	}
	
	public function onPlayerPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		foreach($this->getServer()->getOnlinePlayers() as $p){
			if($p !== $player and strtolower($player->getName()) === strtolower($p->getName())){
				$event->setCancelled(true);
				$player->kick("already logged in");
			}
		}
	}
	
	public function closeSession($player) {
		$this->authSession[$player->getName()] = false;
		$player->removeAllEffects();
	}
	
	public function openSession($player) {
		$this->authSession[$player->getName()] = true;
		if($this->checkAcc($player->getName())) {
			$this->passw[$player->getName()] = $this->getPassword($player->getName());
		}
	}
	
	public function JoinSession(PlayerJoinEvent $e) {
		$player = $e->getPlayer();
		$lang = $this->lang->getLanguage($player->getName());
		if($this->authSession[$player->getName()]) {
		    if(!($this->checkAcc($player->getName()))) {
				$player->sendMessage($this->api->prefix .F::GREEN. " Welcome on §eBDGCraft " .F::AQUA. $player->getName() .F::GREEN. ".If yo're english speaking man, type §blang.en §a in chat\n§7============\n" .$this->api->prefix .F::GREEN. " Vítej na §eBDGCraftu " .F::AQUA. $player->getName() .F::GREEN. ". Jestli jste čech, napiš §blang.cz§a do chatu");
            $this->registerStep[$player->getName()] = 0;
			} else {
           if($lang == "en"){
				$player->sendMessage($this->api->prefix .F::GREEN. " Dear " .F::AQUA. $player->getName() .F::GREEN. ", type your password to chat to log in");
               }
           if($lang == "cz"){
				$player->sendMessage($this->api->prefix .F::GREEN. " Vážený " .F::AQUA. $player->getName() .F::GREEN. ", napište svoje heslo do chatu pro přihlášení");
               }
			}
		}
	}
	
	public function updatePassword($username, $pass) {
		$username = strtolower($username);
		$this->api->mysqli->query("UPDATE `acc` SET `password` = '".$pass."' WHERE `nickname` = '".$username."'");
	}
	
	public function maincmd(PlayerCommandPreprocessEvent $e) {
		$player = $e->getPlayer();
		$username = strtolower($player->getName());
		$msg = $e->getMessage();
		$lang = $this->lang->getLanguage($player->getName());
		if($this->authSession[$player->getName()]) {
			$e->setCancelled(true);
			if($msg{0} === "/") {
				if($lang == "en"){
					$player->sendMessage($this->api->prefix .F::RED. " Log in first.");
				}
				if($lang == "cz"){
					$player->sendMessage($this->api->prefix .F::RED. " Přihlaš se nejdřív.");
				}
			} elseif($msg{0} == "l" && $msg{1} == "a" && $msg{2} == "n" && $msg{3} == "g" && $msg{4} == "." && $msg{5} == "c" && $msg{6} == "z"){
				if(!($this->checkAcc($player->getName()))) {
					if($this->registerStep[$player->getName()] == 0){
						$this->registerStep[$player->getName()] = 1;
						$this->lang->setLanguage("cz", $player->getName());
						$player->sendMessage($this->api->prefix. " §aOk, nyní napiš do chatu heslo \n" .$this->api->prefix. " §aPříklad: §bheslo123");
                    }
				}
			} elseif($msg{0} == "l" && $msg{1} == "a" && $msg{2} == "n" && $msg{3} == "g" && $msg{4} == "." && $msg{5} == "e" && $msg{6} == "n"){
				if(!($this->checkAcc($player->getName()))) {
					if($this->registerStep[$player->getName()] == 0){
						$this->lang->setLanguage("en", $player->getName());
						$this->registerStep[$player->getName()] = 1;
						$player->sendMessage($this->api->prefix. " §aOk, now write your new password to chat\n" .$this->api->prefix. " §aExample: §bpassword123");
					}
				}
			} else {
				if($msg != null) {
                    $pattern = '#[^\s\da-z]#is';
                    if(!preg_match($pattern, $msg)) {
					    $msg = explode(" ", $msg);
				        if(!($this->checkAcc($player->getName()))) {
							if($this->registerStep[$player->getName()] == 1){
								$this->RegisterAcc($player->getName(), $msg[0], $player, $lang);
							}
				        } else {
					        $this->loginAcc($player->getName(), $msg[0], $player);
				        }
			        } else {
						if($lang == "en"){
							$player->sendMessage($this->api->prefix .F::RED. " Wrong symbols.");
						}
						if($lang == "cz"){
							$player->sendMessage($this->api->prefix .F::RED. " Špatné symboly.");
						}
					}
				} else {
					if($lang == "en"){
				        $player->sendMessage($this->api->prefix .F::RED. " Wrong symbols.");
                    }
					if($lang == "cz"){
				        $player->sendMessage($this->api->prefix .F::RED. " Špatné symboly.");
                    }
				}
			}
		} else {
			if(!$this->changePass[$username]) {
				$msg = $e->getMessage();
				if(!$this->authSession[$player->getName()]){
					if($msg{0} == "/" && $msg{1} == "n" && $msg{2} == "e" && $msg{3} == "w" && $msg{4} == "p" && $msg{5} == "a" && $msg{6} == "s"&& $msg{7} == "s"){
						$e->setCancelled();
						$this->changePass[$username] = true;
						if($lang == "en"){
							$player->sendMessage($this->api->prefix .F::GREEN. " Ok, type your new password to chat.");
						}
						if($lang == "cz"){
							$player->sendMessage($this->api->prefix .F::GREEN. " Ok, napiš svoje nové heslo do chatu.");
						}
					}
				} else {
					if($lang == "en"){
						$player->sendMessage($this->api->prefix .F::RED. " Log in first.");
					}
					if($lang == "cz"){
						$player->sendMessage($this->api->prefix .F::RED. " Přihlaš se nejdřív.");
					}
				}
			} else {
				$e->setCancelled();
				if($msg != null) {
					$pattern = '#[^\s\da-z]#is';
					if(!preg_match($pattern, $msg)) {
						$msg = explode(" ", $msg);
						$this->updatePassword($username, $msg[0]);
						if($lang == "en"){
							$player->sendMessage($this->api->prefix .F::GREEN. " You have changed your password. Dont forgot new password.");
						}
						if($lang == "cz"){
							$player->sendMessage($this->api->prefix .F::GREEN. " Změnili jste heslo. Nezapomeňte ho prosím.");
						}
						$this->passw[$username] = $msg[0];
						$this->changePass[$username] = false;
					} else {
						if($lang == "en"){
							$player->sendMessage($this->api->prefix .F::RED. " Wrong symbols.");
						}
						if($lang == "cz"){
							$player->sendMessage($this->api->prefix .F::RED. " Špatné symboly.");
						}
					}
				} else {
					if($lang == "en"){
				        $player->sendMessage($this->api->prefix .F::RED. " Wrong symbols.");
                    }
					if($lang == "cz"){
				        $player->sendMessage($this->api->prefix .F::RED. " Špatné symboly.");
                    }
				}
			}
		}
	}
	
	public function Move(PlayerMoveEvent $e) {
		$player = $e->getPlayer();
		if($this->authSession[$player->getName()]) {
			$e->setCancelled(true);
			$player->onGround = true;
			if(!($this->checkAcc($player->getName()))) {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", register");
				$player->sendPopup(F::GREEN. "Type your new password to chat to register");
			} else {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", log in");
				$player->sendPopup(F::GREEN. "Type your password to chat to log in");
 
			}
		}
	}
	
    public function Damage(EntityDamageEvent $e) {
        $player = $e->getEntity();
        if ($player instanceof Player) {
            if ($e instanceof EntityDamageByEntityEvent) {
                $damager = $e->getDamager()->getPlayer();
                $cause = $e->getEntity()->getPlayer()->getName();
                if ($e->getDamager() instanceof Player && $player instanceof Player) {
					if($this->authSession[$player->getName()]) {
						$e->setCancelled(true);
						if(!($this->checkAcc($player->getName()))) {
							$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", register");
							$player->sendPopup(F::GREEN. "Type your new password to chat to register");
						} else {
							$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", log in");
							$player->sendPopup(F::GREEN. "Type your password to chat to log in");
						}
					}
				}
			}
		}
	}
	
	public function Interact(PlayerInteractEvent $e) {
		$player = $e->getPlayer();
		if($this->authSession[$player->getName()]) {
			$e->setCancelled(true);
			if(!($this->checkAcc($player->getName()))) {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", register");
				$player->sendPopup(F::GREEN. "Type your new password to chat to register");
			} else {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", log in");
				$player->sendPopup(F::GREEN. "Type your password to chat to log in");
			}
		}
	}
	
	public function BreakBlock(BlockBreakEvent $e) {
		$player = $e->getPlayer();
		if($this->authSession[$player->getName()]) {
			$e->setCancelled(true);
			if(!($this->checkAcc($player->getName()))) {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", register");
				$player->sendPopup(F::GREEN. "Type your new password to chat to register");
			} else {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", log in");
				$player->sendPopup(F::GREEN. "Type your password to chat to log in");
			}
		}
	}
	
	public function Sprint(PlayerToggleSprintEvent $e) {
		$player = $e->getPlayer();
		if($this->authSession[$player->getName()]) {
			$e->setCancelled(true);
			if(!($this->checkAcc($player->getName()))) {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", register");
				$player->sendPopup(F::GREEN. "Type your new password to chat to register");
			} else {
				$player->sendTip(F::GREEN. "Dear " .F::AQUA. $player->getName() .F::GREEN. ", log in");
				$player->sendPopup(F::GREEN. "Type your password to chat to log in");
			}
		}
	}

  public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        switch($cmd->getNamd()){
			case "newpass":
				$sender->sendMessage("kek");
			break;
		}
	}
	
	
}