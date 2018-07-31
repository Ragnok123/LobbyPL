<?php

namespace Ragnok123;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\tile\Chest;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as F;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\utils\Utils;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\entity\Attribute;
use synapsepm\SynapsePM;
use synapse\Player as SynapsePlayer;
use synapse\Synapse;
use Ragnok123\reloadtask;
use Ragnok123\apitask;
use Ragnok123\statstask;
use Ragnok123\fallenTimeOutTask;

class FunAPI extends PluginBase implements Listener {
	public $goto;
	public $timeToShotdown;
	public $report;
   public $mysqli;
   public $bpos;
   public $fallen = [ ];
   public $serverId;
	
	public function onEnable() : void {
		$this->lobbyplayers = array();
		$server = \pocketmine\Server::getInstance();
		$worlddir = "worlds/";
		$count = 0;
		foreach (scandir($worlddir) as $value) {
			if(is_dir($worlddir . $value) && ($value !== "." && $value !== "..") ){
				$server->loadLevel($value) && $count++;
			}
		}
		$this->getLogger()->info("§aLoaded $count Worlds");
		$this->mysqli = new \mysqli("", "", "", "");
		$this->owp = $this->getServer()->getPluginManager()->getPlugin("FunPerms");
		$this->owc = $this->getServer()->getPluginManager()->getPlugin("FunChat");
		$this->owm = $this->getServer()->getPluginManager()->getPlugin("FunMoney");
		$this->owl = $this->getServer()->getPluginManager()->getPlugin("FunLanguage");
		$this->prefix = "§e[BDGCraft]";
		$this->owa = $this->getServer()->getPluginManager()->getPlugin("FunBan");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new statstask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new apitask($this), 2600);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new reloadtask($this), 1 * 60 * 20);
		$this->distance = 1000;
		$this->getServer()->getDefaultLevel()->setTime(6000);
		$this->getServer()->getDefaultLevel()->stopTime();
		$this->makeMOTD();
		$this->lobby =  new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->getX(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getY(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ(), $this->getServer()->getDefaultLevel());
		$this->owtime = 60;
		$this->timeToShotdown = 120 * 60;
		if(Server::getInstance()->getPort() == 19132){
			$this->serverId = "lobby";
		}
   }

	public function reloadTime() {
		$this->timeToShotdown -= 60;
		foreach ($this->getServer()->getOnlinePlayers() as $p) {
        $lang = $this->owl->getLanguage($p->getName());
          if($lang == "en"){
			//$p->sendMessage($this->prefix. F::GREEN. " Server will restart in " .F::AQUA. $this->timeToShotdown / 60 . F::GREEN. " minutes");
                }
          if($lang == "cz"){
			//$p->sendMessage($this->prefix. F::GREEN. " Server se restartuje za " .F::AQUA. $this->timeToShotdown / 60 . F::GREEN. " minut");
                }
		}
          $this->getServer()->getLogger()->info($this->prefix. F::GREEN. " Server will restart in " .F::GREEN. $this->timeToShotdown / 60 . F::GOLD. " minutes");
		
		if($this->timeToShotdown <= 1) {
			$this->getServer()->shutdown();
		}
	}
	

   public function sendStats(){
     foreach($this->getServer()->getOnlinePlayers() as $p){
         $coins = $this->owm->getCoins($p->getName());
         $gems = $this->owm->getGems($p->getName());
         $level = $this->owm->getLevel($p->getName());
         $exp = $this->owm->getExp($p->getName());
         $group = $this->owp->getGroup($p->getName());
         $lang = $this->owl->getLanguage($p->getName());
         $space = str_repeat(" ", 85);
         $space1 = str_repeat(" ", 75);
         $void = str_repeat("\n", 25);
                 $p->sendTip($space. "§l§e--=[BDGCraft]=--\n".$space1."§aNick: §f" .$p->getName()."\n".$space1."§aRank: §f" .$group. "\n".$space1."§aCoins: §d" .$coins. " §e•\n".$space1."§aGems: §d" .$gems." §a♦\n".$space1."§aLevel: §f" .$level."\n".$space1."§aProgress: §f" .$exp."§b%"); 
        }
    }



   public function onFullServer(PlayerPreLoginEvent $e){
      $p=$e->getPlayer();
      $group=$this->owp->getGroup($p->getName());
       if(count($this->getServer()->getOnlinePlayers()) == 50){
              $e->setCancelled();
              $p->kick("§cServer is full");
       }
 }

   public function qery(QueryRegenerateEvent $e){
          $e->setPlayerCount(Utils::getURL('http://minecraft-api.com/api/query/playeronline.php?ip=play.bdgcraft.tk&port=22800') + Utils::getURL('http://minecraft-api.com/api/query/playeronline.php?ip=play.bdgcraft.tk&port=22810') + Utils::getURL('http://minecraft-api.com/api/query/playeronline.php?ip=play.bdgcraft.tk&port=22830') + Utils::getURL('http://minecraft-api.com/api/query/playeronline.php?ip=play.bdgcraft.tk&port=22840') + Utils::getURL('http://minecraft-api.com/api/query/playeronline.php?ip=play.bdgcraft.tk&port=22820') + count($this->getServer()->getOnlinePlayers()));
 }
	public function spawnJoin(PlayerLoginEvent $e) {
		$this->spawnParticle($e->getPlayer());
      $e->getPlayer()->teleport($this->lobby);
      $e->getPlayer()->getInventory()->setArmorItem(1, Item::get(444));
	}
  public function spawnDeath(PlayerRespawnEvent $e){
   $e->getPlayer()->teleport($this->lobby);
      $e->getPlayer()->getInventory()->setArmorItem(1, Item::get(444));
  }
	public function EntityExplode(EntityExplodeEvent $e) {
            $e->setCancelled();
   }
   public function antiVoid(PlayerMoveEvent $e){
   if($e->getPlayer()->getY() < 10){
      $e->getPlayer()->teleport($this->lobby);
     }
  }



public function checkXYZ(PlayerInteractEvent $e){
    if(isset($this->bpos[$e->getPlayer()->getName()])){
       $e->setCancelled();
       $e->getPlayer()->sendMessage("§eX: " .$e->getBlock()->getX(). "\n§eY: " .$e->getBlock()->getY(). "\n§eZ: " .$e->getBlock()->getZ());
       unset($this->bpos[$e->getPlayer()->getName()]);
      }
}

/*  public function onPortal(PlayerMoveEvent $e){
    $p = $e->getPlayer();
    $x = $p->getX();
    $y = $p->getY();
    $z = floor($p->getZ());
		if ($p == null) return;
		if ($p->getLevel () == null) return;
		
		// under
		$x = ( int ) round ($p->x - 0.5);
		$y = ( int ) round ($p->y - 1);
		$z = ( int ) round ($p->z - 0.5);
		
		$id = $p->getLevel()->getBlockIdAt( $x, $y, $z );
		$data = $p->getLevel()->getBlockDataAt( $x, $y, $z );
		
		 if ($id == 159 and $data == 4) {
      $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "rca " .$p->getName(). " ftransfer 82.208.17.163 22810");
		} else if ($id == 159 and $data == 14) {
      $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "rca " .$p->getName(). " ftransfer 82.208.17.163 22820");
			}
     /*
    if($x == - 42 && $y > 65 && $y < 72 && $z < 6 && $z > 0 && $p->isInsideOfPortal()){
      $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "rca " .$p->getName(). " ftransfer 82.208.17.163 22810");
    }
    if($x < 5 && $x > 0 && $y > 76 && $y < 83 && $z == - 58 && $p->isInsideOfPortal()){
      $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "rca " .$p->getName(). " ftransfer 82.208.17.163 22820");
    }*/
 } */

  public function onReward(PlayerInteractEvent $e){
   $p = $e->getPlayer();
   $b = $e->getBlock();
   $lang = $this->owl->getLanguage($p->getName());
    if($b->x == 120 && $b->y == 76 && $b->z == 76){
       $e->setCancelled();
      if($lang == "en"){
       $p->sendMessage($this->prefix. " §aYou have completed parkour");
       $p->sendMessage($this->prefix. " §f+ 10 §eCoins");
         }
      if($lang == "cz"){
       $p->sendMessage($this->prefix. " §aProšel jsi parkour");
       $p->sendMessage($this->prefix. " §f+ 10 §eCoins");
         }
       $this->owm->addCoins($p->getName(), +10);
       $p->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->getX(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getY(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ(), $this->getServer()->getDefaultLevel()));
      }
}
	
	public function joinMsg(PlayerJoinEvent $e) {
		$e->setJoinMessage(null);
		$player = $e->getPlayer();
      $this->getServer()->broadcastMessage(F::GRAY. "[" .F::GREEN. "+" .F::GRAY. "] " .$player->getNameTag());
	}
	
	public function quitMsg(PlayerQuitEvent $e) {
		$e->setQuitMessage(null);
      $player = $e->getPlayer();
      $this->getServer()->broadcastMessage(F::GRAY. "[" .F::RED. "-" .F::GRAY. "] " .$player->getNameTag());
	}
	
	public function broadcastMsg() {
      foreach($this->getServer()->getOnlinePlayers() as $p){
      $lang = $this->owl->getLanguage($p->getName());
		$rand = mt_rand(1, 4);
       switch($rand){
       case 1: 
        if($lang == "en"){
       $msg = F::GREEN. " You can buy VIP on" .F::AQUA. " ----";
         }
        if($lang == "cz"){
       $msg = F::GREEN. " VIP si muzes koupit na webu" .F::AQUA. " ----";
         }
        break;
       case 2:
        if($lang == "en"){
        $msg = F::GREEN. " Subcribe our twitter account".F::YELLOW. " ----";
         }
         if($lang == "cz"){
        $msg = F::GREEN. " Odebírejte naší facebookovou stránku".F::YELLOW. " ----";
         }
		    break;
        case 3:
        if($lang == "en"){
        $msg = F::GREEN. " Vote for our server  on web".F::YELLOW. " ----";
         }
         if($lang == "cz"){
        $msg = F::GREEN. " Hlasujte pro nas server na webu".F::YELLOW. " ----";
         }
		    break;
         case 4:
         if($lang == "en"){
         $msg = F::GREEN. " Change your language with".F::YELLOW. " /lang eng/cz".F::GREEN. " Zmen si jazyk pomoci".F::YELLOW. " /lang cz/eng";
		 }
		 if($lang == "cz"){
         $msg = F::GREEN. " Change your language with".F::YELLOW. " /lang eng/cz".F::GREEN. " Zmen si jazyk pomoci".F::YELLOW. " /lang cz/eng";
         }
        }
      $p->sendMessage($this->prefix. F::RESET. $msg);
	}
  }
	
	

	
	public function CheckJoin(PlayerJoinEvent $e) {
		$entity = $e->getPlayer();
		$this->gotoname[$entity->getName()] = null;
		$this->goto[$entity->getName()] = false;
		$group = $this->owp->getGroup($entity->getName());
      $this->checkOp($entity);
    }

  public function checkOp(Player $p){
      $group = $this->owp->getGroup($p->getName());
       switch($group){
            case "owner":
              $p->setOp(true);
              break;
             case "dev":
               $p->setOp(true);
              break;
             case "hladmin":
               $p->setOp(true);
              break;
             case "admin":
               $p->setOp(true);
               break;
             case "helper":
               $p->setOp(true);
               break;
             case "builder":
               $p->setOp(true);
               break;
             case "vip":
               $p->setOp(false);
               break;
             case "user":
               $p->setOp(false);
               break;
        }
    }
	
  public function onFly(Player $pl) {
		$group = $this->owp->getGroup($pl->getName());
       if($group !== "user"){
          if($pl->getLevel()->getName() == "lobby1"){
           $pl->setAllowFlight(true);
          }
     }
 }
/*	public function WorldBorder(PlayerMoveEvent $e) {
		$entity = $e->getPlayer();
		$v = new Vector3($entity->getLevel()->getSpawnLocation()->getX(), $entity->getPosition()->getY(), $entity->getLevel()->getSpawnLocation()->getZ());
		if($this->owp->getGroup($entity->getName()) == "user") {
			if(floor($entity->distance($v)) >= $this->distance) {
				$e->setCancelled();
				$entity->sendTip(F::YELLOW. "[WallGuard]" .F::GOLD. " Nekonecny svět? Kupte si " .F::GREEN. "VIP" .F::GOLD. "!");
			}
		}
	}
*/
	
    public function WhoDamager(EntityDamageEvent $e) {
        $entity = $e->getEntity();
		$entity = $e->getEntity();
		$level = $this->getServer()->getDefaultLevel();
        if ($entity instanceof Player) {
            if ($e instanceof EntityDamageByEntityEvent) {
                $damager = $e->getDamager()->getPlayer();
                $cause = $e->getEntity()->getPlayer()->getName();
                if ($e->getDamager() instanceof Player) {
                    $v = new Vector3($entity->getLevel()->getSpawnLocation()->getX(),$entity->getPosition()->getY(),$entity->getLevel()->getSpawnLocation()->getZ());
                    $r = $this->getServer()->getSpawnRadius();
                    if(($entity instanceof Player) && ($entity->getPosition()->distance($v) <= $r)) {
						if(!($damager->isOp())) {
							$e->setCancelled();
							$damager->sendPopup(F::RED. "PVP is disabled on spawn!");
						}
					}
				}
			}
		}
	}
	
	public function reportMessage($msg) {
		return implode(" ", $msg);
	}
	
	public function sendReport($username, $msg) {
		$text = "[BDGCraft] Hráč $username píše report: $msg.";
		$this->sendUserMessage(225153780, $text);
	}
	
	public function sendUserMessage($id, $message) {
		$this->go('messages.send', ['user_id' => $id, 'message' => rawurlencode($message)]);
		$array = json_decode($this->lastcurl, true);
	}
	
	public function go($method, $par) { 
        $token = "";
		$params = '';
        foreach ($par as $key => $val) {
            $params .= $key.'='.$val.'&';
        }
		
		$this->curl('https://api.vk.com/method/' .$method. '?' .$params. 'access_token=' .$token);
	}
	
	
	public function curl($args) { 
		$curl = curl_init($args);
        curl_setopt($curl, CURLOPT_URL, $args);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "a=4&b=7");
		$data = @curl_exec($curl);
		@curl_close($curlObject);
        if ($data) {
			$this->lastcurl = $data;
		} else {
			$this->lastcurl = 'curl nefunguje';
		}
	}
	
    public function onCommand(CommandSender $entity, Command $cmd, $label, array $args) {
		$level = $this->getServer()->getDefaultLevel();
		$username = strtolower($entity->getName());
		$group = $this->owp->getGroup($entity->getName());
		$lang = $this->owl->getLanguage($entity->getName());
        switch ($cmd->getName()) {         
        case "bpos":
          if(!isset($this->bpos[$entity->getName()])){
             $this->bpos[$entity->getName()] = true;
             $entity->sendMessage($this->prefix. " §aOk, now click block");
           }else{
             $entity->sendMessage($this->prefix. " §cYou are already in getting mode");
            }
          break;
            case "spawn":
				$entity->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->getX(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getY(), $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ(), $this->getServer()->getDefaultLevel()));
           if($lang == "en"){
				$entity->sendMessage($this->prefix .F::GREEN. " Dear " .F::AQUA. $entity->getName(). F::GREEN. ", you were teleported to spawn.");
              }
           if($lang == "cz"){
				$entity->sendMessage($this->prefix .F::GREEN. " Vážený " .F::AQUA. $entity->getName(). F::GREEN. ", byl jsi teleportován na spawn.");
              }
			break;
			case "tpall":
			if($entity Instanceof Player) {
				if($group == "owner" or $group == "god") {
					foreach ($this->getServer()->getOnlinePlayers() as $p) {
						$p->teleport(new Vector3($entity->getX(), $entity->getY(), $entity->getZ()));
						$this->getServer()->getLogger()->info(F::YELLOW. "[CubeOfFun]" .F::GREEN. $entity->getName(). F::GOLD. " teleportoval vsech v jedno misto.");
					}
				} else {
					$entity->sendMessage($this->prefix .F::RED. " This can only owner.");
				}
			} else {
				$entity->sendMessage(F::YELLOW. "[CubeOfFun]" .F::GOLD. " Cant.");
			}
			break;
             case "clear":
			if($entity Instanceof Player) {
				$entity->getInventory()->clearAll();
          if($lang == "en"){
				$entity->sendMessage($this->prefix." §aYour inventory was cleared");
              }
          if($lang == "cz"){
				$entity->sendMessage($this->prefix." §aVáš inventář byl vymazán");
              }
			} else {
				$entity->sendMessage(F::YELLOW. "[WallGuard]" .F::GOLD. " Nejde.");
			}
			break;

			case "gm":
			if($entity Instanceof Player) {
				if($group != "user" && $group != "vip" && $group != "premium" && $group !== "youtube") {
					if(isset($args[0])) {
						if(is_numeric($args[0])) {
							$entity->setGamemode($args[0]);
							$entity->sendMessage($this->prefix .F::GREEN. " You have changed gamemode.");
						} else {
							$entity->sendMessage($this->prefix .F::RED. " Gamemode must be numeric.");
						}
					} else {
						$entity->sendMessage($this->prefix .F::RED. " Write gamemode.");
					}
				} else {
					$entity->sendMessage($this->prefix.F::RED. " Only for staff.");
				}
			} else {
				$entity->sendMessage(F::GOLD. "[Minetox]" .F::RED. " Error.");
			}
			break;
			case "fly":
			if($entity Instanceof Player) {
				if($group != "user") {
					if(isset($args[0])) {
						if($args[0] == "on") {
							$entity->setAllowFlight(true);
                   if($lang == "en"){
							$entity->sendMessage($this->prefix .F::GREEN. " Fly was enabled.");
                      }
                   if($lang == "cz"){
							$entity->sendMessage($this->prefix .F::GREEN. " Lítání bylo zapnuto.");
                      }
						}
						if($args[0] == "off") {
							$entity->setAllowFlight(false);
                   if($lang == "en"){
							$entity->sendMessage($this->prefix .F::GREEN. " Fly was disabled.");
                      }
                   if($lang == "cz"){
							$entity->sendMessage($this->prefix .F::GREEN. " Lítání bylo vypnuto.");
                      }
						}
					} else {
                   if($lang == "en"){
							$entity->sendMessage($this->prefix .F::GREEN. " /fly on|off.");
                      }
                   if($lang == "cz"){
							$entity->sendMessage($this->prefix .F::GREEN. " /fly on|off.");
                      }
					}
				} else {
             if($lang == "en"){
					$entity->sendMessage($this->prefix .F::RED. " Buy VIP for that feature");
                }
             if($lang == "cz"){
					$entity->sendMessage($this->prefix .F::RED. " Kuote si VIP pro tuto výhodu");
                }
				}
			}
			break;

			case "sleep":
			if($group != "user") {
				$entity->sleepOn(new Vector3($entity->getX(), $entity->getY(), $entity->getZ()));
             if($lang == "en"){
				$entity->sendMessage($this->prefix .F::GREEN. " You are sleeping, lol");
              }
             if($lang == "cz"){
				$entity->sendMessage($this->prefix .F::GREEN. " Šel jsi spát, ahah");
              }
			} else {
           if($lang == "en"){
				$entity->sendMessage($this->prefix .F::RED. " Buy VIP for that feature!");
            }
           if($lang == "cz"){
				$entity->sendMessage($this->prefix .F::RED. " Kup si VIP pro tuto výhodu!");
            }
			}
			break;
			case "ftp":
			if($entity Instanceof Player) {
				if($group != "user" && $group != "vip" && $group != "premium" && $group != "helper") {
					if(isset($args[0])) {
						if($this->getServer()->getPlayer($args[0]) Instanceof Player) {
							$x = $this->getServer()->getPlayer($args[0])->getX();
							$y = $this->getServer()->getPlayer($args[0])->getY();
							$z = $this->getServer()->getPlayer($args[0])->getZ();
                    $playerlevel = $this->getServer()->getPlayer($args[0])->getLevel()->getFolderName();
							$entity->teleport(new Position($x, $y, $z, $playerlevel));
							$entity->sendMessage($this->prefix. F::GREEN. " You have teleported to " .F::AQUA. $args[0]. F::GREEN. "!");
						} else {
							$entity->sendMessage($this->prefix. F::RED. " Player " .F::AQUA. $args[0]. F::RED. " is offline.");
						}
					} else {
						//$entity->sendMessage(F::GOLD. "[Minetox]" .F::RED. " Only for staff.");
					}
				} else {
					//$entity->sendMessage($this->prefix. F::RED. " Only for staff.");
				}
			} else {
				$entity->sendMessage($this->prefix. F::RED. " Error.");
			}
			break;
		}
	}
	
	public function cmd(PlayerCommandPreprocessEvent $e) {
		$entity = $e->getPlayer();
		$msg = $e->getMessage();
		$group = $this->owp->getGroup($entity->getName());
		$lang = $this->owl->getLanguage($entity->getName());
		if($msg{0} == "/" && $msg{1} == "h" && $msg{2} == "e" && $msg{3} == "l" && $msg{4} == "p") {
			$e->setCancelled();
           if($lang == "en"){
				$entity->sendMessage(F::GREEN. "- /spawn" .F::AQUA. " - Teleport to spawn");
				$entity->sendMessage(F::GREEN. "- /sethome" .F::AQUA. " - Sethome");
           $entity->sendMessage(F::GREEN. "- /newpass" .F::AQUA. " - Change password");
				$entity->sendMessage(F::GREEN. "- /home" .F::AQUA. " - Teleport to home");
            $entity->sendMessage(F::GREEN. "-/res" .F::AQUA. " - Regions");
				$entity->sendMessage(F::GREEN. "- /warp" .F::AQUA. " - Warps");
				$entity->sendMessage(F::GREEN. "- /job" .F::AQUA. " - Choose job");
				$entity->sendMessage(F::GREEN. "- /money bank" .F::AQUA. " - Coins");
            $entity->sendMessage(F::GREEN. " - /sleep" .F::AQUA. " - Sleeping (only VIP)");
            $entity->sendMessage(F::GREEN. " - /fly" .F::AQUA. " - Fly (only VIP)");
            $entity->sendMessage(F::GREEN. " - /gm" .F::AQUA. " - Gamemode (only staff)");
            $entity->sendMessage(F::GREEN. " - /fban" .F::AQUA. " - Ban (only staff)");
            $entity->sendMessage(F::GREEN. " - /fkick" .F::AQUA. " - Kick (only staff)");
            $entity->sendMessage(F::GREEN. " - /ftp" .F::AQUA. " - Teleport to player (only for staff)");
        }
           if($lang == "cz"){
				$entity->sendMessage(F::GREEN. "- /spawn" .F::AQUA. " - Teleportovat se na spawn");
				$entity->sendMessage(F::GREEN. "- /sethome" .F::AQUA. " - Sethome");
           $entity->sendMessage(F::GREEN. "- /newpass" .F::AQUA. " - Změnit heslo");
				$entity->sendMessage(F::GREEN. "- /home" .F::AQUA. " - Teleportovat se domů ");
            $entity->sendMessage(F::GREEN. "-/res" .F::AQUA. " - Resky");
				$entity->sendMessage(F::GREEN. "- /warp" .F::AQUA. " - Warpy");
				$entity->sendMessage(F::GREEN. "- /job" .F::AQUA. " - Vybrat práci");
				$entity->sendMessage(F::GREEN. "- /money bank" .F::AQUA. " - Bankovský účet");
            $entity->sendMessage(F::GREEN. " - /sleep" .F::AQUA. " - Jít spát (jen pro VIP)");
            $entity->sendMessage(F::GREEN. " - /fly" .F::AQUA. " - Lítání (jen pro VIP)");
            $entity->sendMessage(F::GREEN. " - /gm" .F::AQUA. " - Gamemode (jen pro A-Team)");
            $entity->sendMessage(F::GREEN. " - /fban" .F::AQUA. " - Ban (jen pro A-Team)");
            $entity->sendMessage(F::GREEN. " - /fkick" .F::AQUA. " - Kick (jen pro A-Team)");
            $entity->sendMessage(F::GREEN. " - /ftp" .F::AQUA. " - Teleportovat se k hráči (jen pro A-Team)");
        }
	}
		if($msg{0} == "/" && $msg{1} == "m" && $msg{2} == "e") {
			$e->setCancelled();
			$entity->sendMessage(F::YELLOW. "Just do nothing :--)");
     }
    if($msg{0} == "/" && $msg{1} == "a" && $msg{2} == "b" && $msg{3} == "o" && $msg{4} == "u" && $msg{5} == "t"){
        $e->setCancelled();
        $entity->sendMessage("Paldopice");
      }
    if($msg{0} == "/" && $msg{1} == "r" && $msg{2} == "e" && $msg{3} == "l" && $msg{4} == "o" && $msg{5} == "a" && $msg{6} == "d") {
        $e->setCancelled();
        $entity->sendMessage("Dont type, bitch");
      }
    if($msg{0} == "/" && $msg{1} == "v" && $msg{2} == "e" && $msg{3} == "r" && $msg{4} == "s" && $msg{5} == "i" && $msg{6} == "o" && $msg{7} == "n") {
        $e->setCancelled();
        $entity->sendMessage("Paldopice");
      }
  if($msg{0} == "/" && $msg{1} == "v" && $msg{2} == "e" && $msg{3} == "r") {
        $e->setCancelled();
        $entity->sendMessage("Paldopice");
      }
  if($msg{0} == "/" && $msg{1} == "b" && $msg{2} == "a" && $msg{3} == "n") {
        $e->setCancelled();
        $entity->sendMessage($this->prefix. " §cUse /fban <nickname> <reason>");
      }
  if($msg{0} == "/" && $msg{1} == "k" && $msg{2} == "i" && $msg{3} == "c" && $msg{4} == "k") {
        $e->setCancelled();
        $entity->sendMessage($this->prefix. " §cUse /fkick <nickname> <reason>");
      }
   if($msg{0} == "/" && $msg{1} == "p" && $msg{2} == "l"){
    if(!$entity->isOp()){
       $e->setCancelled();
       $entity->sendMessage("Why did you typed? Ok, §aFunAPI_v1.0");
      }
   }/*
    if($msg{0} == "/" && $msg{1} == "o" && $msg{2} == "p"){
    if($group !== "owner" || $group !== "hladmin" || $group !== "admin"){
      $e->setCancelled();
      }
   }*/
    if($msg{0} == "/" && $msg{1} == "p" && $msg{2} == "l" && $msg{3} == "u" && $msg{4} == "g" && $msg{5} == "i" && $msg{6} == "n" && $msg{7} == "s"){
      if(!$entity->isOp()){
        $e->setCancelled();
        $entity->sendMessage("Why did you typed? Ok, §aFunAPI_v1.0");
         }
      }
	}
}