<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class SetActorDataPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{ 
	public const NETWORK_ID = ProtocolInfo::SET_ACTOR_DATA_PACKET;

	public int $actorRuntimeId;
	
	public array $metadata;
	public PropertySyncData $syncedProperties;
	public int $tick = 0;

	
	public static function create(int $actorRuntimeId, array $metadata, PropertySyncData $syncedProperties, int $tick) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->metadata = $metadata;
		$result->syncedProperties = $syncedProperties;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->metadata = CommonTypes::getEntityMetadata($in);
		$this->syncedProperties = PropertySyncData::read($in);
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putEntityMetadata($out, $this->metadata);
		$this->syncedProperties->write($out);
		VarInt::writeUnsignedLong($out, $this->tick);
	}


	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetActorData($this);
	}
}
