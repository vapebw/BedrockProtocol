<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419;

use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419CreativeContentPacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419StartGamePacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419TickSyncPacket;

use pocketmine\network\mcpe\protocol\proto\v419\packets\v419LevelChunkPacket;
use pocketmine\network\mcpe\protocol\proto\v419\packets\v419NetworkChunkPublisherUpdatePacket;

class v419PacketPool extends PacketPool{

	public function __construct(){
		parent::__construct();

		$this->registerPacket(new v419StartGamePacket());
		$this->registerPacket(new v419InventoryTransactionPacket());
		$this->registerPacket(new v419AvailableCommandsPacket());
		$this->registerPacket(new v419AdventureSettingsPacket());
		$this->registerPacket(new v419CreativeContentPacket());
		$this->registerPacket(new v419TickSyncPacket());
		$this->registerPacket(new v419LevelChunkPacket());
		$this->registerPacket(new v419NetworkChunkPublisherUpdatePacket());
	}
}

