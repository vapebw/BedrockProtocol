<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\entity;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

final class BlockPosMetadataProperty implements MetadataProperty{
	use GetTypeIdFromConstTrait;

	public const ID = EntityMetadataTypes::POS;

	public function __construct(
		private BlockPosition $value
	){}

	public function getValue() : BlockPosition{
		return $this->value;
	}

	public static function read(ByteBufferReader $in) : self{
		$protocolId = CommonTypes::getStreamProtocolId($in);
		if($protocolId < 944){
			return new self(CommonTypes::getSignedBlockPosition($in));
		}
		return new self(CommonTypes::getBlockPosition($in));
	}

	public function write(ByteBufferWriter $out) : void{
		$protocolId = CommonTypes::getStreamProtocolId($out);
		if($protocolId < 944){
			CommonTypes::putSignedBlockPosition($out, $this->value);
		}else{
			CommonTypes::putBlockPosition($out, $this->value);
		}
	}

	public function equals(MetadataProperty $other) : bool{
		return $other instanceof self and $other->value->equals($this->value);
	}
}
