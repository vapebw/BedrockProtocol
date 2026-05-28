<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;

class v419AvailableActorIdentifiersPacket extends AvailableActorIdentifiersPacket implements ClientboundPacket{
	public const NETWORK_ID = 0x77;

	private static ?CacheableNbt $cachedIdentifiers = null;

	public static function fromCurrent(AvailableActorIdentifiersPacket $packet) : self{
		if(self::$cachedIdentifiers === null){
			$path = \pocketmine\BEDROCK_DATA_PATH . '/entity_identifiers-1.16.100.nbt';
			$state = (new NetworkNbtSerializer())->read(Filesystem::fileGetContents($path))->mustGetCompoundTag();
			self::$cachedIdentifiers = new CacheableNbt($state);
		}
		return new self();
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		throw new \RuntimeException("v419AvailableActorIdentifiersPacket is clientbound only");
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		if(self::$cachedIdentifiers === null){
			$path = \pocketmine\BEDROCK_DATA_PATH . '/entity_identifiers-1.16.100.nbt';
			$state = (new NetworkNbtSerializer())->read(Filesystem::fileGetContents($path))->mustGetCompoundTag();
			self::$cachedIdentifiers = new CacheableNbt($state);
		}
		$out->writeByteArray(self::$cachedIdentifiers->getEncodedNbt());
	}
}
