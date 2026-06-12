<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class BlockPickRequestPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::BLOCK_PICK_REQUEST_PACKET;

	public BlockPosition $blockPosition;
	public bool $addUserData = false;
	public int $hotbarSlot;

	
	public static function create(BlockPosition $blockPosition, bool $addUserData, int $hotbarSlot) : self{
		$result = new self;
		$result->blockPosition = $blockPosition;
		$result->addUserData = $addUserData;
		$result->hotbarSlot = $hotbarSlot;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		if($this->protocolId < 944){
			$this->blockPosition = CommonTypes::getSignedBlockPosition($in);
		}else{
			$this->blockPosition = CommonTypes::getBlockPosition($in);
		}
		$this->addUserData = CommonTypes::getBool($in);
		$this->hotbarSlot = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		if($this->protocolId < 944){
			CommonTypes::putSignedBlockPosition($out, $this->blockPosition);
		}else{
			CommonTypes::putBlockPosition($out, $this->blockPosition);
		}
		CommonTypes::putBool($out, $this->addUserData);
		Byte::writeUnsigned($out, $this->hotbarSlot);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleBlockPickRequest($this);
	}
}
