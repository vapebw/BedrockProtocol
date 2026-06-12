<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;


final class PlayerBlockActionWithBlockInfo implements PlayerBlockAction{
	public function __construct(
		private int $actionType,
		private BlockPosition $blockPosition,
		private int $face
	){
		if(!self::isValidActionType($actionType)){
			throw new \InvalidArgumentException("Invalid action type for " . self::class);
		}
	}

	public function getActionType() : int{ return $this->actionType; }

	public function getBlockPosition() : BlockPosition{ return $this->blockPosition; }

	public function getFace() : int{ return $this->face; }

	public static function read(ByteBufferReader $in, int $actionType) : self{
		$protocolId = CommonTypes::getStreamProtocolId($in);
		if($protocolId < 944){
			$blockPosition = CommonTypes::getSignedBlockPosition($in);
		}else{
			$blockPosition = CommonTypes::getBlockPosition($in);
		}
		$face = VarInt::readSignedInt($in);
		return new self($actionType, $blockPosition, $face);
	}

	public function write(ByteBufferWriter $out) : void{
		$protocolId = CommonTypes::getStreamProtocolId($out);
		if($protocolId < 944){
			CommonTypes::putSignedBlockPosition($out, $this->blockPosition);
		}else{
			CommonTypes::putBlockPosition($out, $this->blockPosition);
		}
		VarInt::writeSignedInt($out, $this->face);
	}

	public static function isValidActionType(int $actionType) : bool{
		return match($actionType){
			PlayerAction::ABORT_BREAK,
			PlayerAction::START_BREAK,
			PlayerAction::CRACK_BREAK,
			PlayerAction::PREDICT_DESTROY_BLOCK,
			PlayerAction::CONTINUE_DESTROY_BLOCK => true,
			default => false
		};
	}
}
