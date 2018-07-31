<?php
namespace Ragnok123\FunGadgets\Pets;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class PetWolf extends Pets
{

    const NETWORK_ID = 14;

    public function getName()
    {
        return "Wolf";
    }

    public function spawnTo(Player $player)
    {
        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = self::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = [
            3 => [0, $this->getDataProperty(3)],
            15 => [0, 1],
            Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
            Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 1]
        ];
        $player->dataPacket($pk);
        parent::spawnTo($player);
    }
}
