<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v419AdventureSettingsPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = 0x37;

	public const PERMISSION_NORMAL = 0;
	public const PERMISSION_OPERATOR = 1;
	public const PERMISSION_HOST = 2;
	public const PERMISSION_AUTOMATION = 3;
	public const PERMISSION_ADMIN = 4;

	public const BITFLAG_SECOND_SET = 1 << 16;

	public const WORLD_IMMUTABLE = 0x01;
	public const NO_PVP = 0x02;

	public const AUTO_JUMP = 0x20;
	public const ALLOW_FLIGHT = 0x40;
	public const NO_CLIP = 0x80;
	public const WORLD_BUILDER = 0x100;
	public const FLYING = 0x200;
	public const MUTED = 0x400;

	public const BUILD_AND_MINE = 0x01 | self::BITFLAG_SECOND_SET;
	public const DOORS_AND_SWITCHES = 0x02 | self::BITFLAG_SECOND_SET;
	public const OPEN_CONTAINERS = 0x04 | self::BITFLAG_SECOND_SET;
	public const ATTACK_PLAYERS = 0x08 | self::BITFLAG_SECOND_SET;
	public const ATTACK_MOBS = 0x10 | self::BITFLAG_SECOND_SET;
	public const OPERATOR = 0x20 | self::BITFLAG_SECOND_SET;
	public const TELEPORT = 0x80 | self::BITFLAG_SECOND_SET;

	public int $flags = 0;
	public int $commandPermission = self::PERMISSION_NORMAL;
	public int $flags2 = -1;
	public int $playerPermission = 1;
	public int $customFlags = 0;
	public int $entityUniqueId = 0;

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->flags = VarInt::readUnsignedInt($in);
		$this->commandPermission = VarInt::readUnsignedInt($in);
		$this->flags2 = VarInt::readUnsignedInt($in);
		$this->playerPermission = VarInt::readUnsignedInt($in);
		$this->customFlags = VarInt::readUnsignedInt($in);
		$this->entityUniqueId = LE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->flags);
		VarInt::writeUnsignedInt($out, $this->commandPermission);
		VarInt::writeUnsignedInt($out, $this->flags2);
		VarInt::writeUnsignedInt($out, $this->playerPermission);
		VarInt::writeUnsignedInt($out, $this->customFlags);
		LE::writeSignedLong($out, $this->entityUniqueId);
	}

	public function getFlag(int $flag) : bool{
		if(($flag & self::BITFLAG_SECOND_SET) !== 0){
			return ($this->flags2 & $flag) !== 0;
		}
		return ($this->flags & $flag) !== 0;
	}

	public function setFlag(int $flag, bool $value) : void{
		if(($flag & self::BITFLAG_SECOND_SET) !== 0){
			$flagSet =& $this->flags2;
		}else{
			$flagSet =& $this->flags;
		}
		if($value){
			$flagSet |= $flag;
		}else{
			$flagSet &= ~$flag;
		}
	}

	public static function fromUpdateAbilities(\pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket $packet) : self{
		$result = new self();
		$data = $packet->getData();
		$result->commandPermission = $data->getCommandPermission();
		$result->playerPermission = $data->getPlayerPermission();
		$result->entityUniqueId = $data->getTargetActorUniqueId();
		$abilities = [];
		foreach($data->getAbilityLayers() as $layer){
			foreach($layer->getBoolAbilities() as $ability => $value){
				$abilities[$ability] = $value;
			}
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_BUILD])){
			$result->setFlag(self::BUILD_AND_MINE, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_BUILD]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_DOORS_AND_SWITCHES])){
			$result->setFlag(self::DOORS_AND_SWITCHES, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_DOORS_AND_SWITCHES]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_OPEN_CONTAINERS])){
			$result->setFlag(self::OPEN_CONTAINERS, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_OPEN_CONTAINERS]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ATTACK_PLAYERS])){
			$result->setFlag(self::ATTACK_PLAYERS, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ATTACK_PLAYERS]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ATTACK_MOBS])){
			$result->setFlag(self::ATTACK_MOBS, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ATTACK_MOBS]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_OPERATOR])){
			$result->setFlag(self::OPERATOR, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_OPERATOR]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_TELEPORT])){
			$result->setFlag(self::TELEPORT, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_TELEPORT]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ALLOW_FLIGHT])){
			$result->setFlag(self::ALLOW_FLIGHT, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_ALLOW_FLIGHT]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_FLYING])){
			$result->setFlag(self::FLYING, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_FLYING]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_NO_CLIP])){
			$result->setFlag(self::NO_CLIP, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_NO_CLIP]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_WORLD_BUILDER])){
			$result->setFlag(self::WORLD_BUILDER, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_WORLD_BUILDER]);
		}
		if(isset($abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_MUTED])){
			$result->setFlag(self::MUTED, $abilities[\pocketmine\network\mcpe\protocol\types\AbilitiesLayer::ABILITY_MUTED]);
		}
		return $result;
	}

	public static function fromUpdateAdventureSettings(\pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket $packet) : self{
		$result = new self();
		$result->setFlag(self::ATTACK_MOBS, !$packet->isNoAttackingMobs());
		$result->setFlag(self::ATTACK_PLAYERS, !$packet->isNoAttackingPlayers());
		$result->setFlag(self::WORLD_IMMUTABLE, $packet->isWorldImmutable());
		$result->setFlag(self::AUTO_JUMP, $packet->isAutoJump());
		return $result;
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
