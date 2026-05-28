<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use function count;

class v419ResourcePackStackPacket extends ResourcePackStackPacket{

	public static function fromCurrent(ResourcePackStackPacket $pk) : self{
		$npk = new self();
		$npk->mustAccept = $pk->mustAccept;
		$npk->resourcePackStack = $pk->resourcePackStack;
		$npk->baseGameVersion = $pk->baseGameVersion;
		$npk->experiments = $pk->experiments;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putBool($out, $this->mustAccept);

		VarInt::writeUnsignedInt($out, 0);

		VarInt::writeUnsignedInt($out, count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out);
		}

		CommonTypes::putString($out, $this->baseGameVersion);
		$this->experiments->write($out);
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->mustAccept = CommonTypes::getBool($in);

		$behaviorPackCount = VarInt::readUnsignedInt($in);
		while($behaviorPackCount-- > 0){
			ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = VarInt::readUnsignedInt($in);
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->baseGameVersion = CommonTypes::getString($in);
		$this->experiments = Experiments::read($in);
	}
}
