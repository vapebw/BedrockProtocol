<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket implements ClientboundPacket{
	public const NETWORK_ID = 0x79;

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = CommonTypes::getBlockPosition($in);
		$this->radius = VarInt::readUnsignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putBlockPosition($out, $this->blockPosition);
		VarInt::writeUnsignedInt($out, $this->radius);
	}

	public static function fromCurrent(NetworkChunkPublisherUpdatePacket $packet) : self{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->radius = $packet->radius;
		$result->savedChunks = $packet->savedChunks;
		return $result;
	}
}
