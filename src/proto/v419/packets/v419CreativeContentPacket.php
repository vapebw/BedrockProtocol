<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\proto\v419\v419PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;
use function count;

class v419CreativeContentPacket extends CreativeContentPacket{
	public const NETWORK_ID = 0x91;

	protected function decodePayload(ByteBufferReader $in) : void{
		$items = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$entryId = VarInt::readSignedInt($in);
			$item = v419PacketSerializer::readSlot($in);
			$items[] = new CreativeItemEntry($entryId, $item, 0);
		}

		$propGroups = new \ReflectionProperty(CreativeContentPacket::class, 'groups');
		$propGroups->setAccessible(true);
		$propGroups->setValue($this, []);

		$propItems = new \ReflectionProperty(CreativeContentPacket::class, 'items');
		$propItems->setAccessible(true);
		$propItems->setValue($this, $items);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->getItems()));
		foreach($this->getItems() as $entry){
			VarInt::writeSignedInt($out, $entry->getEntryId());
			v419PacketSerializer::writeSlot($out, $entry->getItem());
		}
	}

	public static function fromCurrent(\pocketmine\network\mcpe\protocol\CreativeContentPacket $packet) : self{
		$result = new self();
		$propGroups = new \ReflectionProperty(\pocketmine\network\mcpe\protocol\CreativeContentPacket::class, 'groups');
		$propGroups->setAccessible(true);
		$propGroups->setValue($result, $packet->getGroups());
		$propItems = new \ReflectionProperty(\pocketmine\network\mcpe\protocol\CreativeContentPacket::class, 'items');
		$propItems->setAccessible(true);
		$propItems->setValue($result, $packet->getItems());
		return $result;
	}
}
