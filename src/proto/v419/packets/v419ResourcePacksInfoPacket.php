<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v419ResourcePacksInfoPacket extends ResourcePacksInfoPacket{

	public static function fromCurrent(ResourcePacksInfoPacket $pk) : self{
		$npk = new self();
		$npk->mustAccept = $pk->mustAccept;
		$npk->hasScripts = $pk->hasScripts;
		$npk->resourcePackEntries = $pk->resourcePackEntries;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putBool($out, $this->mustAccept);
		CommonTypes::putBool($out, $this->hasScripts);

		LE::writeUnsignedShort($out, 0);

		LE::writeUnsignedShort($out, count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			CommonTypes::putString($out, $entry->getPackId()->toString());
			CommonTypes::putString($out, $entry->getVersion());
			LE::writeUnsignedLong($out, $entry->getSizeBytes());
			CommonTypes::putString($out, $entry->getEncryptionKey());
			CommonTypes::putString($out, $entry->getSubPackName());
			CommonTypes::putString($out, $entry->getContentId());
			CommonTypes::putBool($out, $entry->hasScripts());
		}
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->mustAccept = CommonTypes::getBool($in);
		$this->hasScripts = CommonTypes::getBool($in);

		$behaviorCount = LE::readUnsignedShort($in);
		while($behaviorCount-- > 0){
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			LE::readUnsignedLong($in);
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			CommonTypes::getBool($in);
		}

		$resourceCount = LE::readUnsignedShort($in);
		while($resourceCount-- > 0){
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			LE::readUnsignedLong($in);
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			CommonTypes::getString($in);
			CommonTypes::getBool($in);
		}
	}
}
