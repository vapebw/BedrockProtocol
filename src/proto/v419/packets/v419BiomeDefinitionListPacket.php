<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;

class v419BiomeDefinitionListPacket extends BiomeDefinitionListPacket implements ClientboundPacket{
	public const NETWORK_ID = 0x7a;

	private static ?CacheableNbt $cachedDefs = null;

	public static function fromCurrent(BiomeDefinitionListPacket $packet) : self{
		if(self::$cachedDefs === null){
			$path = \pocketmine\BEDROCK_DATA_PATH . '/biome_definitions-1.16.100.nbt';
			$state = (new NetworkNbtSerializer())->read(Filesystem::fileGetContents($path))->mustGetCompoundTag();
			self::$cachedDefs = new CacheableNbt($state);
		}
		return new self();
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		throw new \RuntimeException("v419BiomeDefinitionListPacket is clientbound only");
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		if(self::$cachedDefs === null){
			$path = \pocketmine\BEDROCK_DATA_PATH . '/biome_definitions-1.16.100.nbt';
			$state = (new NetworkNbtSerializer())->read(Filesystem::fileGetContents($path))->mustGetCompoundTag();
			self::$cachedDefs = new CacheableNbt($state);
		}
		$out->writeByteArray(self::$cachedDefs->getEncodedNbt());
	}
}
