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

class FunLanguage extends PluginBase implements Listener {
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
      $this->api = $this->getServer()->getPluginManager()->getPlugin("FunAPI");
	}
	
	public function checkAcc($username) {
	    $username = strtolower($username);
		$result = $this->api->mysqli->query("SELECT * FROM `language` WHERE `nickname` = '".$username."'");
        $user = mysqli_fetch_assoc($result);
        if($user) {
	        return true;
        } else {
	        return false;
        }
	}
	
	public function updateLanguage($username) {
	    $username = strtolower($username);
	    if($this->checkAcc($username)) {
	        $this->playerLanguage[$username] = $this->checkLanguage($username);
		} else {
		    $this->playerLanguage[$username] = "en";
		    $this->createData($username);
		}
	}
	
	public function onJoin(PlayerJoinEvent $e) {
		$player = $e->getPlayer();
		if($this->checkAcc($player->getName())) {
		    $this->updateLanguage($player->getName());
		} else {
		    $this->createData($player->getName());
		}
	}
	
	public function createData($username) {
	    $username = strtolower($username);
        if(!$this->checkAcc($username)) {
            $this->api->mysqli->query("INSERT INTO `language` (`id`, `nickname`, `language`) VALUES (NULL, '".$username."', 'en')");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunLanguage]" .F::GOLD. " Создана таблица игроку " .F::GREEN. $username);
			$this->updateLanguage($username);
		}
	}
	
	public function checkLanguage($username) {
	    $username = strtolower($username);
        $result = $this->api->mysqli->query("SELECT * FROM `language` WHERE `nickname` = '".$username."'");
		if($this->checkAcc($username)) {
			$data = $result->fetch_assoc();
			$result->free();
			if(isset($data["language"])){
			    return $data["language"];
			}
		} else {
		    $this->createData($username);
		}
	}
	
	public function getLanguage($username) {
	    $username = strtolower($username);
	    if(isset($this->playerLanguage[$username])) {
			return $this->playerLanguage[$username];
		} else {
		    $this->updateLanguage($username);
		}
	}
	
	public function setLanguage($language, $username) {
	 		$language = strtolower($language);
	    $username = strtolower($username);
        if($this->checkAcc($username)) {
            $this->api->mysqli->query("UPDATE `language` SET `language` = '".$language."' WHERE `nickname` = '".$username."'");
			$this->getServer()->getLogger()->info(F::YELLOW. "[FunLanguage]" .F::GOLD. " Hrac " .F::GREEN. $username. F::GOLD. " nastavil jazyk na" .F::GREEN. $language);
			$this->updateLanguage($username);
		} else {
		    $this->createData($username);
            $this->api->mysqli->query("UPDATE `language` SET `language` = '".$language."' WHERE `nickname` = '".$username."'");
			$this->updateLanguage($username);
		}
	}

    public function onCommand(CommandSender $entity, Command $cmd, $label, array $args) {
         $lang = $this->getLanguage($entity->getName());
        switch ($cmd->getName()) {
            case "lang":
             if(isset($args[0])){
               if($args[0] == "Čeština"){
                   $this->setLanguage("cz", $entity->getName());
                   $entity->sendMessage($this->api->prefix. " §aVáš jazyk je nyní §bČeština");
                     }
               if($args[0] == "English"){
                   $this->setLanguage("en", $entity->getName());
                   $entity->sendMessage($this->api->prefix. " §aYour language is now §bEnglish");
                         }
                     }
              break;
              }
      }
}