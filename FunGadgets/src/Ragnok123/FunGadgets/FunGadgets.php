<?php

namespace Ragnok123\FunGadgets;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\{
     Level,
     Position,
     Explosion};
use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\entity\PrimedTNT;
use pocketmine\command\{
    Command,
    CommandSender,
    ConsoleCommandSender};
use pocketmine\event\{
    Listener,
    player\PlayerItemHeldEvent,
    player\PlayerJoinEvent,
    player\PlayerLoginEvent,
    player\PlayerQuitEvent,
    player\PlayerInteractEvent,
    player\PlayerMoveEvent,
    player\PlayerDeathEvent,
    entity\Effect,
    entity\EntityDespawnEvent,
    entity\EntityExplodeEvent,
    entity\EntityDamageEvent,
    entity\EntityDamageByEntityEvent, 
    entity\EntityLevelChangeEvent,
    inventory\InventoryPickupItemEvent,
    server\DataPacketReceiveEvent};
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\nbt\tag\{
     CompoundTag,
     ListTag,
     LongTag,
     ShortTag,
     DoubleTag,
     FloatTag,
     ByteTag};
use pocketmine\network\protocol\{
     UseItemPacket,
     AddEntityPacket};
use pocketmine\level\particle\{
     BubbleParticle,
     PortalParticle,
     EnchantParticle,
     FlameParticle,
     HeartParticle,
     DustParticle};
use pocketmine\utils\{
     Config,
     TextFormat};
use pocketmine\inventory\Inventory;
use pocketmine\scheduler\PluginTask;
use Ragnok123\FunGadgets\{
     GadgetsTask,
      Pets\Pets,
       Pets\PetCow, 
       Pets\PetWolf,
       Pets\PetOcelot,
       Pets\PetIronGolem,
       Pets\PetChicken};

class FunGadgets extends PluginBase implements Listener{

   public $onBubble;
   public $onPortal;
   public $onEnchant;
   public $onFlame;
   public $onHearth;
   public $onDust;
   public $players;

    public $id;
    public $eid;
    public $owner;

   
     public function onEnable(){
     $this->getServer()->getPluginManager()->registerEvents($this, $this);
     $serv = Server::getInstance();
     $this->inMenu = array();
     $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
     $this->owp = $this->getServer()->getPluginManager()->getPlugin("FunPerms");
     $this->owm = $this->getServer()->getPluginManager()->getPlugin("FunMoney");
     $this->owa = $this->getServer()->getPluginManager()->getPlugin("FunAuth");
     $this->players = new Config($this->getDataFolder()."players.yml",Config::YAML);
     $this->getServer()->getScheduler()->scheduleRepeatingTask(new GadgetsTask([$this,"checkCompass"]),20);
     $this->melonLaunched=false;
     $this->bleskLaunched=false;
     $this->tntLaunched=false;
     $this->melonDropped=false;
     foreach($this->getServer()->getDefaultLevel()->getEntities() as $ent){
         if($ent instanceof Pets){
             $ent->kill();
           }
       }
        Entity::registerEntity(PetCow::class, true);
        Entity::registerEntity(PetChicken::class, true);
        Entity::registerEntity(PetIronGolem::class, true);
        Entity::registerEntity(PetOcelot::class, true);
        Entity::registerEntity(PetWolf::class, true);
     echo "debug";
       }



    public function createNbt($x, $y, $z, $yaw, $pitch){
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $x),
            new DoubleTag("", $y),
            new DoubleTag("", $z)
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag("", $yaw),
            new FloatTag("", $pitch)
        ]);
        $nbt->Health = new ShortTag("Health", 1);
        $nbt->Invulnerable = new ByteTag("Invulnerable", 1);
        return $nbt;
    }

    public function spawn(Player $player, $name){
        $entity = Entity::createEntity($name, $player->getLevel()/*->getChunk($player->x >> 4, $player->z >> 4)*/, $this->createNbt($player->x, $player->y, $player->z, $player->yaw, $player->pitch));
        $entity->spawnToAll();
        $this->eid[$player->getName()] = $entity->getId();
        $entity->setNameTag($player->getName(). "'s pet");
        $entity->setNameTagAlwaysVisible(true);
        $entity->setNameTagVisible(true);
    }
/*
    public function spawnByCommand(Player $player, $name, $jmeno)
    {
        $entity = Entity::createEntity($name, $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), $this->createNbt($player->x, $player->y, $player->z, $player->yaw, $player->pitch));
        $entity->spawnToAll();
        $this->eid[$player->getName()] = $entity->getId();
        $entity->setNameTag($jmeno);
        $entity->setNameTagAlwaysVisible(true);
        $entity->setNameTagVisible(true);
    }

*/
    public function moveEntity(Player $player, $entityId){
        $chunk = $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4);
        $player->getLevel()->addEntityMovement(
            $chunk->getX(), $chunk->getZ(),
            $entityId,
            $player->x, $player->y, $player->z,
            $player->yaw, $player->pitch
        );
    }

    public function removePet(Player $player){
        if (isset($this->eid[$player->getName()])) {
            $player->getLevel()->getEntity($this->eid[$player->getName()])->kill();
            unset($this->eid[$player->getName()]);
        }
    }

   public function checkCompass(){
    foreach($this->getServer()->getOnlinePlayers() as $p){
      if($this->isInMenu($p) !== true){
       if($p->getLevel()->getName() == "Glorious_Downfall"){
       $p->getInventory()->setItem(0, Item::get(Item::CLOCK)->setCustomName("§l§a> §eMenu §a<"));
                }
             }
         }
      }

   public function onCommand(CommandSender $s, Command $cmd, $label, array $args){
          switch($cmd->getName()){
             case "gadgets":
                  if(isset($args[0])){
                    if($args[0] == "addmelons"){
                      if(isset($args[1])){
                        if(isset($args[2])){
                          $this->addMelonLauncher($args[1], $args[2]);
                           }
                       }
                    }
                    if($args[0] == "addtnt"){
                      if(isset($args[1])){
                        if(isset($args[2])){
                          $this->addTNTLauncher($args[1], $args[2]);
                           }
                       }
                    }
                    if($args[0] == "addzeus"){
                      if(isset($args[1])){
                        if(isset($args[2])){
                          $this->addZeusBlesk($args[1], $args[2]);
                           }
                       }
                    }
                    if($args[0] == "addenderpearls"){
                      if(isset($args[1])){
                        if(isset($args[2])){
                          $this->addEnderpeal($args[1], $args[2]);
                           }
                       }
                    }
                    if($args[0] == "addoinbombs"){
                      if(isset($args[1])){
                        if(isset($args[2])){
                          $this->addCoinBomb($args[1], $args[2]);
                           }
                       }
                    }
                }
         break;
       }
   }

	public function onJoinDatabaze(PlayerLoginEvent $e) {
		$p = $e->getPlayer();
       $p->getInventory()->setItem(0, Item::get(Item::CLOCK)->setCustomName("§l§a> §eMenu §a<"));
		if($this->checkAcc($p->getName())) {
		    $this->getServer()->getLogger()->info("§e[Gadgets] §a" .$p->getName(). " §eje zapsan v databazi gadgetu, vse v poradku.");
		} else {
		    $this->createData($p->getName());
		    $this->getServer()->getLogger()->info("§e[Gadgets] §a" .$p->getName(). " §eneni zapsan v databazi gadgetu, vytvarim zaznam.");
		}
	}

    public function isInMenu($p){
       foreach($this->inMenu as $kek){
         if($p->getName() == $kek){
             return true;
            }
       }
  }

	public function checkAcc($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
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
            $this->api->mysqli->query("INSERT INTO `gadgets` (`id`, `nickname`, `coinbomb`, `melonlauncher`, `tntlauncher`, `enderpearl`, `zeusblesk`) VALUES (NULL, '".$username."', '0', '0', '0', '0', '0')");
		}
	}

	public function getCoinBomb($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['coinbomb'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['coinbomb'];
		}
	}


 	public function addCoinBomb($username, $status) {
	    $username = strtolower($username);
		$count = strtolower($status) + $this->getCoinBomb($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `gadgets` SET `coinbomb` = '".$count."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(TextFormat::YELLOW. "[Gadgets]" .TextFormat::GOLD. " Игрок " .TextFormat::GREEN. $username. TextFormat::GOLD. " получил Coin Bomb x" .TextFormat::GREEN. $count);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `gadgets` SET `coinbomb` = '".$count."' WHERE `nickname` = '".$username."'");
		}

	}

	public function getMelonLauncher($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['melonlauncher'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['melonlauncher'];
		}
	}


 	public function addMelonLauncher($username, $status) {
	    $username = strtolower($username);
		$count = strtolower($status) + $this->getMelonLauncher($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `gadgets` SET `melonlauncher` = '".$count."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(TextFormat::YELLOW. "[Gadgets]" .TextFormat::GOLD. " Игрок " .TextFormat::GREEN. $username. TextFormat::GOLD. " получил Melon Launcher x" .TextFormat::GREEN. $count);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `gadgets` SET `melonlauncher` = '".$count."' WHERE `nickname` = '".$username."'");
		}
	}

	public function getTNTLauncher($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['tntlauncher'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['tntlauncher'];
		}
	}


 	public function addTNTLauncher($username, $status) {
	    $username = strtolower($username);
		$count = strtolower($status) + $this->getTNTLauncher($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `gadgets` SET `tntlauncher` = '".$count."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(TextFormat::YELLOW. "[Gadgets]" .TextFormat::GOLD. " Игрок " .TextFormat::GREEN. $username. TextFormat::GOLD. " получил TNT Launcher x" .TextFormat::GREEN. $count);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `gadgets` SET `tntlauncher` = '".$count."' WHERE `nickname` = '".$username."'");
		}
	}

	public function getEnderpearl($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['enderpearl'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['enderpearl'];
		}
	}


 	public function addEnderpearl($username, $status) {
	    $username = strtolower($username);
		$count = strtolower($status) + $this->getEnderpearl($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `gadgets` SET `enderpearl` = '".$count."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(TextFormat::YELLOW. "[Gadgets]" .TextFormat::GOLD. " Игрок " .TextFormat::GREEN. $username. TextFormat::GOLD. " получил Enderpearl x" .TextFormat::GREEN. $count);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `gadgets` SET `enderpearl` = '".$count."' WHERE `nickname` = '".$username."'");
		}
	}

	public function getZeusBlesk($username) {
	    $username = strtolower($username);
		if($this->checkAcc($username)) {
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['zeusblesk'];
		} else {
		    $this->createData($username);
			$result = $this->api->mysqli->query("SELECT * FROM `gadgets` WHERE `nickname` = '".$username."'");
			$data = $result->fetch_assoc();
			$result->free();
			return $data['zeusblesk'];
		}
	}


 	public function addZeusBlesk($username, $status) {
	    $username = strtolower($username);
		$count = strtolower($status) + $this->getZeusBlesk($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `gadgets` SET `zeusblesk` = '".$count."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(TextFormat::YELLOW. "[Gadgets]" .TextFormat::GOLD. " Игрок " .TextFormat::GREEN. $username. TextFormat::GOLD. " получил Zeus Blesk x" .TextFormat::GREEN. $count);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `gadgets` SET `zeusblesk` = '".$count."' WHERE `nickname` = '".$username."'");
		}
	}

    public function saveInv(Player $player){
        $items = [];
        foreach($player->getInventory()->getContents() as $slot=>&$item){
            $items[$slot] = implode(":", [$item->getId(), $item->getDamage(), $item->getCount()]);
        }
        $this->players->setNested(trim(strtolower($player->getName())).".inventory", $items);
    }
    
    public function loadInv(Player $player){
        foreach($this->players->getNested(trim(strtolower($player->getName())).".inventory") as $slot => $t){
            list($id, $dmg, $count) = explode(":", $t);
            $item = Item::get($id, $dmg, $count);
            $player->getInventory()->setItem($slot, $item);
        }
    }

   public function addPlayer(Player $player){
            $this->saveInv($player);
            $this->players->save();
            $this->players->reload();
    }
    
    public function removePlayer(Player $player){
            $this->loadInv($player);
            $this->players->setNested(trim(strtolower($player->getName())).".inventory", null);
            $this->players->save();
            $this->players->reload();
    }
    
    public function onLevelChange(EntityLevelChangeEvent $e){
        $p = $e->getEntity();
        if($p instanceof Player){
            $lvl = $e->getTarget();
            if($lvl->getName() == "lobby1" || $lvl->getName() == "flat" || $lvl->getName() == "creative"){
                $this->addPlayer($p);
                $p->getInventory()->clearAll();
                $clock = Item::get(Item::CLOCK);
                $clock->setCustomName("§l§a> §eMenu §a<");
                $p->getInventory()->addItem($clock);
            }
          if($lvl->getName() == "survival"){ 
                $clock = Item::get(Item::CLOCK);
                $clock->setCustomName("§l§a> §eMenu §a<");
                $p->getInventory()->addItem($clock);
                $this->removePlayer($p);
            }
        }
    }

   public function onGadgetPopup(PlayerItemHeldEvent $e){
   $p = $e->getPlayer();
   $i = $p->getInventory()->getItemInHand();
     if($i->getId() == 103){
        $p->sendPopup("§l§aMelon Launcher (".$this->getMelonLauncher($p->getName()).")");
      }
   if($i->getId() == Item::TNT){
        $p->sendPopup("§l§cTNT Launcher (".$this->getTNTLauncher($p->getName()).")");
      }
   if($i->getId() == Item::ICE){
        $p->sendPopup("§l§bZeus Storm (".$this->getZeusBlesk($p->getName()).")");
      }
    }

   public function onMenu(PlayerInteractEvent $e){

      $p = $e->getPlayer();
       $b = $e->getBlock();
       $i = $e->getItem();
       if($i->getCustomName() == "§l§a> §eMenu §a<"){
         $e->setCancelled();
          foreach($this->inMenu as $num => $pl){if($pl==$p->getName()){array_splice($this->inMenu,$num,1);}}
          array_push($this->inMenu, $p->getName());
          $p->sendMessage($this->api->prefix. " §aYou have opened menu");
          $item = Item::get(27,0,1);
          $item->setCustomName("§l§d> §aLobby §d<");
$p->getInventory()->setItem(0, $item);
          $item = Item::get(91,0,1);
          $item->setCustomName("§l§6> §bStats §6<");
          $p->getInventory()->setItem(1, $item);
          $item = Item::get(Item::STICK);
          $item->setCustomName("§l§b> §aParticles §b<");
          $p->getInventory()->setItem(2, $item);
          $item = Item::get(Item::SUGAR);
          $item->setCustomName ("§l§e> §dPets §e<");
          $p->getInventory()->setItem(3, $item);
          $item = Item::get(35,14,1);
          $item->setCustomName("§l§9> §cExit Menu §9<");
          $p->getInventory()->setItem(4, $item);
        }
    if($i->getCustomName() ==
"§l§d> §aLobby §d<"){
      $e->setCancelled();
      $this->getServer()->dispatchCommand(new ConsoleCommandSender(), "rca " .$p->getName(). " spawn");
        }
     if($i->getCustomName() == "§l§6> §bStats §6<"){
         $p->sendMessage($this->api->prefix. " §aYour stats:");
         $p->sendMessage("  §e> §aNickname: §b" .$p->getName());
         $p->sendMessage("  §e> §aRank: §b" .$this->owp->getGroup($p->getName()));
         $p->sendMessage("  §e> §aLevel: §b" .$this->owm->getLevel($p->getName()));
         $p->sendMessage("  §e> §aProgress: §b" .$this->owm->getExp($p->getName())."§a%");
         $p->sendMessage("  §e> §aCoins: §b" .$this->owm->getCoins($p->getName()));
         $p->sendMessage("  §e> §aGems: §b" .$this->owm->getGems($p->getName()));
       }

    if($i->getCustomName() == "§l§e> §dPets §e<"){
       if($this->owp->getGroup($p->getName()) !== "user"){
           $p->sendMessage($this->api->prefix. " §aYou have opened Pet category!");
           $p->getInventory()->clearAll();
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§eCow Pet");
           $p->getInventory()->setItem(0, $item);
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§eOcelot Pet");
           $p->getInventory()->setItem(1, $item);
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§eIron Golem Pet");
           $p->getInventory()->setItem(2, $item);
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§eChicken Pet");
           $p->getInventory()->setItem(3, $item);
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§eDog Pet");
           $p->getInventory()->setItem(4, $item);
           $item = Item::get(397, 0, 1);
           $item->setCustomName("§l§cDisable Pets");
           $p->getInventory()->setItem(5, $item);
           $item = Item::get(35,14,1);
           $item->setCustomName("§l§9> §cBack §9<");
           $p->getInventory()->setItem(6, $item);
         } else {
          $p->sendMessage($this->api->prefix. " §cBuy §9VIP §cto unlock Pet category!");
            }
      }

    if($i->getCustomName() == "§l§b> §aParticles §b<"){
       if($this->owp->getGroup($p->getName()) !== "user"){
           $p->sendMessage($this->api->prefix. " §aYou have opened Particle category!");
           $p->getInventory()->clearAll();
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§eBubble Particle");
           $p->getInventory()->setItem(0, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§ePortal Particle");
           $p->getInventory()->setItem(1, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§eEnchant Particle");
           $p->getInventory()->setItem(2, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§eFlame Particle");
           $p->getInventory()->setItem(3, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§eHearth Particle");
           $p->getInventory()->setItem(4, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§eDust Particle");
           $p->getInventory()->setItem(5, $item);
           $item = Item::get(Item::STICK);
           $item->setCustomName("§l§cDisable Particles");
           $p->getInventory()->setItem(6, $item);
           $item = Item::get(35,14,1);
           $item->setCustomName("§l§9> §cBack §9<");
           $p->getInventory()->setItem(7, $item);
         } else {
          $p->sendMessage($this->api->prefix. " §cBuy §9VIP §cto unlock Particle category!");
            }
      }
    if($i->getCustomName() == "§l§9> §cBack §9<"){
        $p->getInventory()->clearAll();
        $item = Item::get(27,0,1);
          $item->setCustomName("§l§d> §aLobby §d<");
$p->getInventory()->setItem(0, $item);
          $item = Item::get(91,0,1);
          $item->setCustomName("§l§6> §bStats §6<");
          $p->getInventory()->setItem(1, $item);
          $item = Item::get(Item::STICK);
          $item->setCustomName("§l§b> §aParticles §b<");
          $p->getInventory()->setItem(2, $item);
          $item = Item::get(Item::SUGAR);
          $item->setCustomName ("§l§e> §dPets §e<");
          $p->getInventory()->setItem(3, $item);
          $item = Item::get(35,14,1);
          $item->setCustomName("§l§9> §cExit Menu §9<");
          $p->getInventory()->setItem(4, $item);
          $item = Item::get(0,0,0);
          $p->getInventory()->setItem(5, $item);
          $p->getInventory()->setItem(6, $item);
          $p->getInventory()->setItem(7, $item);
          $p->getInventory()->setItem(8, $item);
      }

/* pets */


       if($i->getCustomName() == "§l§eCow Pet"){
          $this->removePet($p);
          $this->spawn($p, "PetCow");
          $p->sendMessage($this->api->prefix. " §aYou have took §bCow §apet");
        }

       if($i->getCustomName() == "§l§eOcelot Pet"){
          $this->removePet($p);
          $this->spawn($p, "PetOcelot");
          $p->sendMessage($this->api->prefix. " §aYou have took §bOcelot §apet");
        }

       if($i->getCustomName() == "§l§eChicken Pet"){
          $this->removePet($p);
          $this->spawn($p, "PetChicken");
          $p->sendMessage($this->api->prefix. " §aYou have took §bChicken §apet");
        }

       if($i->getCustomName() == "§l§eIron Golem Pet"){
          $this->removePet($p);
          $this->spawn($p, "PetIronGolem");
          $p->sendMessage($this->api->prefix. " §aYou have took §bIron Golem §apet");
        }

       if($i->getCustomName() == "§l§eDog Pet"){
          $this->removePet($p);
          $this->spawn($p, "PetWolf");
          $p->sendMessage($this->api->prefix. " §aYou have took §bDog §apet");
        }

       if($i->getCustomName() == "§l§cDisable Pets"){
          $this->removePet($p);
          $p->sendMessage($this->api->prefix. " §aYou have disabled all pets");
        }

/* pets */



      /*particles*/
       if($i->getCustomName() == "§l§eBubble Particle"){
           if(!isset($this->onBubble[$p->getName()])){
               unset($this->onPortal[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $this->onBubble[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bBubble Particle");
              }
        }

       if($i->getCustomName() == "§l§ePortal Particle"){
           if(!isset($this->onPortal[$p->getName()])){
               unset($this->onBubble[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $this->onPortal[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bPortal Particle");
              }
        }

       if($i->getCustomName() == "§l§eEnchant Particle"){
           if(!isset($this->onEnchant[$p->getName()])){
               unset($this->onPortal[$p->getName()]);
               unset($this->onBubble[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $this->onEnchant[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bEnchant Particle");
              }
        }

       if($i->getCustomName() == "§l§eFlame Particle"){
           if(!isset($this->onFlame[$p->getName()])){
               unset($this->onPortal[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onBubble[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $this->onFlame[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bFlame Particle");
              }
        }

       if($i->getCustomName() == "§l§eHearth Particle"){
           if(!isset($this->onHearth[$p->getName()])){
               unset($this->onPortal[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onBubble[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $this->onHearth[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bHearth Particle");
              }
        }

       if($i->getCustomName() == "§l§eDust Particle"){
           if(!isset($this->onDust[$p->getName()])){
               unset($this->onPortal[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onBubble[$p->getName()]);
               $this->onDust[$p->getName()] = true;
               $p->sendMessage($this->api->prefix. " §aYou have enabled §bDust Particle");
              }
        }

       if($i->getCustomName() == "§l§cDisable Particles"){
               unset($this->onBubble[$p->getName()]);
               unset($this->onPortal[$p->getName()]);
               unset($this->onEnchant[$p->getName()]);
               unset($this->onFlame[$p->getName()]);
               unset($this->onHearth[$p->getName()]);
               unset($this->onDust[$p->getName()]);
               $p->sendMessage($this->api->prefix. " §aYou have disabled all particles");
        }

     /*particles*/


    if($i->getCustomName() == "§l§9> §cExit Menu §9<"){
     if($this->isInMenu($p) == true){
         $p->getInventory()->clearAll();
          $p->sendMessage($this->api->prefix. " §aYou have closed menu");
      $p->getInventory()->setItem(0, Item::get(Item::CLOCK)->setCustomName("§l§a> §eMenu §a<"));
           foreach($this->inMenu as $num => $kek){
              if($kek == $p->getName()){
                array_splice($this->inMenu, $num, 1);
               }
             }
           } else {
            $p->sendMessage($this->prefix->api. " §cYou have already closed menu");
           }
       }
    }

	public function onPacketRecieve(DataPacketReceiveEvent $event){
		if($event->getPacket() instanceof UseItemPacket){
			$player = $event->getPlayer();
			if($player->getInventory()->getItemInHand()->getId() == 103){
  if($this->getMelonLauncher($player->getName()) > 0){
                    $this->melonLaunched=true;
 						$namedTag = new CompoundTag("", [
								"Pos" => new ListTag("Pos", [
									new DoubleTag("", $player->x),
									new DoubleTag("", $player->y + $player->getEyeHeight()),
									new DoubleTag("", $player->z)
								]),
								"Motion" => new ListTag("Motion", [
									new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
									new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
									new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
								]),
								"Rotation" => new ListTag("Rotation", [
									new FloatTag("", $player->yaw),
									new FloatTag("", $player->pitch)
								]),
							]);
							$e = new Snowball($player->chunk, $namedTag, $player);
						$e->setMotion($e->getMotion()->multiply(1));
						$e->spawnToAll();
                 $this->addMelonLauncher($player->getName(), -1); 
          } else {
             $player->sendMessage($this->api->prefix. " §c You doesnt have §aMelon Launchers");
          }
       }
 			if($player->getInventory()->getItemInHand()->getId() == Item::ICE){
  if($this->getZeusBlesk($player->getName()) > 0){
                    $this->bleskLaunched=true;
 						$namedTag = new CompoundTag("", [
								"Pos" => new ListTag("Pos", [
									new DoubleTag("", $player->x),
									new DoubleTag("", $player->y + $player->getEyeHeight()),
									new DoubleTag("", $player->z)
								]),
								"Motion" => new ListTag("Motion", [
									new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
									new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
									new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
								]),
								"Rotation" => new ListTag("Rotation", [
									new FloatTag("", $player->yaw),
									new FloatTag("", $player->pitch)
								]),
							]);
							$e = new Snowball($player->chunk, $namedTag, $player);
						$e->setMotion($e->getMotion()->multiply(1));
						$e->spawnToAll();
                 $this->addZeusBlesk($player->getName(), -1); 
          } else {
             $player->sendMessage($this->api->prefix. " §c You doesnt have §bZeus Storm");
          }
       }
			if($player->getInventory()->getItemInHand()->getId() == Item::TNT){
  if($this->getTNTLauncher($player->getName()) > 0){
                 $this->tntLaunched = true;
 						$namedTag = new CompoundTag("", [
								"Pos" => new ListTag("Pos", [
									new DoubleTag("", $player->x),
									new DoubleTag("", $player->y + $player->getEyeHeight()),
									new DoubleTag("", $player->z)
								]),
								"Motion" => new ListTag("Motion", [
									new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
									new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
									new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
								]),
								"Rotation" => new ListTag("Rotation", [
									new FloatTag("", $player->yaw),
									new FloatTag("", $player->pitch)
								]),
							]);
							$e = new Snowball($player->chunk, $namedTag, $player);
						$e->setMotion($e->getMotion()->multiply(1));
						$e->spawnToAll();
                 $this->addTNTLauncher($player->getName(), -1); 
          } else {
             $player->sendMessage($this->api->prefix. " §c You doesnt have TNT Launchers");
          }
       }
	}
}

   public function onQuit(PlayerQuitEvent $e){
     $p = $e->getPlayer();
foreach($this->inMenu as $num => $pl){if($pl==$p->getName()){array_splice($this->inMenu,$num,1);}}
      }

   public function onBlesk(EntityDespawnEvent $e){
      $ent=$e->getEntity();
        if($this->bleskLaunched == true){
         if($ent instanceof Snowball){
          $p=$ent->shootingEntity;
              foreach($this->getServer()->getOnlinePlayers() as $kek){
$this->bleskLaunched=false;
$pk = new AddEntityPacket();
        $pk->type = 93;
        $pk->eid = Entity::$entityCount++;
        $pk->metadata = array();
        $pk->x = $ent->getX();
        $pk->y = $ent->getY();
        $pk->z = $ent->getZ();
        $pk->speedX = 0;
        $pk->speedY = 0;
        $pk->speedZ =0;
        $pk->yaw = 0;
        $pk->pitch = 0;
            $kek->dataPacket($pk);
           }
        }
    }
}

      public function onTNT(EntityDespawnEvent $e){
      $ent=$e->getEntity();
        if($this->tntLaunched == true){
         if($ent instanceof Snowball){
             $this->tntLaunched = false;
             $explosion = new Explosion(new Position($ent->getX(), $ent->getY(), $ent->getZ(), $ent->getLevel()), 4);
             $explosion->explodeB();
        }
    }
}

   public function onMelonDrop(EntityDespawnEvent $e){
    $ent = $e->getEntity();
      if($this->melonLaunched == true){
         if($ent instanceof Snowball){
           $p=$ent->shootingEntity;
               foreach($this->getServer()->getOnlinePlayers() as $kek){
         if($kek->getName()==$p->getName()){
           $this->melonLaunched=false;
           $this->spawnRes($ent->getX(), $ent->getY() + 0.5, $ent->getZ(), 360, $p);
           $this->melonDropped=true;
                   }
              }
         }
      }
   }



    public function onExit(PlayerQuitEvent $e){
        $this->removePet($e->getPlayer());
    }

    public function onDeath(PlayerDeathEvent $e){
        $this->removePet($e->getPlayer());
    }

    public function move(PlayerMoveEvent $e){
        if (isset($this->eid[$e->getPlayer()->getName()])){
            $this->moveEntity($e->getPlayer(), $this->eid[$e->getPlayer()->getName()]);
        }
    }

    public function noDmg(EntityDamageEvent $e){
        if ($e instanceof EntityDamageByEntityEvent) {
            $damager = $e->getDamager();
            $entity = $e->getEntity();
            if ($damager instanceof Player && $entity instanceof Pets) {
                $e->setCancelled();
            }
        }
    }

   public function melonPickup(InventoryPickupItemEvent $e){
      $p = $e->getInventory()->getHolder();
      $i = $e->getItem();
//      if($this->melonDropped == true){
        if($i->getId() == 360 && $i->getCustomName() == "Melon"){
          if($p instanceof Player){
          $this->melonDropped=false;
          $effect = Effect::getEffect(1);
          $effect->setVisible(true);
          $effect->setDuration(100);
          $p->addEffect($effect);
         }
//        }
      }
   }

    public function onGadgetMove(PlayerMoveEvent $e){
     $p = $e->getPlayer();

		if (isset($this->onBubble[$p->getName()])) {
					$level = $p->getLevel();
                    $x = $p->getX();
                    $y = $p->getY()+3;
                    $z = $p->getZ();
 $center = new Vector3($x, $y - 4, $z);
 $radius = 6;
 $count = 700;
 $particle = new BubbleParticle($center);
 for($yaw = 3, $y = $center->y; $y < $center->y + 6; $yaw += (M_PI * 2) / 28, $y += 1 / 23){
 $x = -sin($yaw) + $center->x;
 $z = cos($yaw) + $center->z;
 $particle->setComponents($x, $y, $z);
 $level->addParticle($particle);
}
}
		if (isset($this->onPortal[$p->getName()])) {
					 $level = $p->getLevel();
                    $x = $p->getX();
                    $y = $p->getY()+3;
                    $z = $p->getZ();
 $center = new Vector3($x, $y - 4, $z);
 $radius = 7;
 $count = 900;
 $particle = new PortalParticle($center);
 for($yaw = 3, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 30, $y += 1 / 25){
 $x = -sin($yaw) + $center->x;
 $z = cos($yaw) + $center->z;
 $particle->setComponents($x, $y, $z);
 $level->addParticle($particle);
 }
		}
		if (isset($this->onEnchant[$p->getName()])) {
					 $level = $p->getLevel();
                    $x = $p->getX();
                    $y = $p->getY()+3;
                    $z = $p->getZ();
 $center = new Vector3($x, $y - 4, $z);
 $radius = 6;
 $count = 900;
 $particle = new EnchantParticle($center);
 for($yaw = 3, $y = $center->y; $y < $center->y + 5; $yaw += (M_PI * 2) / 30, $y += 1 / 25){
 $x = -sin($yaw) + $center->x;
 $z = cos($yaw) + $center->z;
 $particle->setComponents($x, $y, $z);
 $level->addParticle($particle);
}
}
		if (isset($this->onFlame[$p->getName()])) {
					$level = $p->getLevel();
                    $x = $p->getX();
                    $y = $p->getY()+5;
                    $z = $p->getZ();
 $center = new Vector3($x, $y - 4, $z);
 $radius = 8;
 $count = 900;
 $particle = new FlameParticle($center);
 for($yaw = 3, $y = $center->y; $y < $center->y + 6; $yaw += (M_PI * 2) / 30, $y += 1 / 30){
 $x = -sin($yaw) + $center->x;
 $z = cos($yaw) + $center->z;
 $particle->setComponents($x, $y, $z);
 $level->addParticle($particle);
}
}
		if (isset($this->onHearth[$p->getName()])) {
					 $level = $p->getLevel();
                    $x = $p->getX();
                    $y = $p->getY()+3;
                    $z = $p->getZ();
 $center = new Vector3($x, $y - 4, $z);
 $radius = 5;
 $count = 900;
 $particle = new HeartParticle($center);
 for($yaw = 3, $y = $center->y; $y < $center->y + 5; $yaw += (M_PI * 2) / 30, $y += 1 / 25){
 $x = -sin($yaw) + $center->x;
 $z = cos($yaw) + $center->z;
 $particle->setComponents($x, $y, $z);
 $level->addParticle($particle);
}
}
		if (isset($this->onDust[$p->getName()])) {
	$level = $p->getLevel();
	$x = $p->getX();
	$y = $p->getY()+3;
	$z = $p->getZ();
	$center = new Vector3($x, $y - 4, $z);
	$radius = 7;
	$count = 900;
	$particle = new DustParticle($center, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
		for($yaw = 3, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 30, $y += 1 / 25){
	$x = -sin($yaw) + $center->x;
	$z = cos($yaw) + $center->z;
	$particle->setComponents($x, $y, $z);
	$level->addParticle($particle);
}
}
    }

  public function entityantitnt(EntityExplodeEvent $e){
            $e->setCancelled();
    }

    public function spawnRes($x,$y,$z,$id, $player){
        $nbt = new CompoundTag("", ["Pos" => new ListTag("Pos", [
              new DoubleTag("", $x),
              new DoubleTag("", $y),
               new DoubleTag("", $z)
 ]),"Motion" => new ListTag("Motion", [
              new DoubleTag("", mt_rand(-100,100)*0.001),
              new DoubleTag("", mt_rand(-100,100)*0.001),
              new DoubleTag("", mt_rand(-100,100)*0.001)
 ]),"Rotation" => new ListTag("Rotation", [
             new FloatTag("", 0),
             new FloatTag("", 0)
 ]),"Health" => new ShortTag("Health", 5)
  ,"Item" => new CompoundTag("Item", ["id" => new ShortTag("id", $id),"Damage" => new ShortTag("Damage", 0),"Count" => new ByteTag("Count", 1),
 ]),"PickupDelay" => new ShortTag("PickupDelay", 0),
 ]);
         $f = 1;
         $res=Entity::createEntity("Item", $this->getServer()->getDefaultLevel()->getChunk($x >> 4, $z >> 4), $nbt);
         $res->spawnToAll();
       }

}