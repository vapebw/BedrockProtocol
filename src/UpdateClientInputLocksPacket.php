<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;

class UpdateClientInputLocksPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_CLIENT_INPUT_LOCKS_PACKET;

	private int $flags;
	private ?\pocketmine\math\Vector3 $position = null;

	
	public static function create(int $flags, ?\pocketmine\math\Vector3 $position = null) : self{
		$result = new self;
		$result->flags = $flags;
		$result->position = $position;
		return $result;
	}

	public function getFlags() : int{ return $this->flags; }

	public function getPosition() : ?\pocketmine\math\Vector3{ return $this->position; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->flags = VarInt::readUnsignedInt($in);
		if($this->protocolId < 975){
			$this->position = \pocketmine\network\mcpe\protocol\serializer\CommonTypes::getVector3($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->flags);
		if($this->protocolId < 975){
			\pocketmine\network\mcpe\protocol\serializer\CommonTypes::putVector3($out, $this->position ?? new \pocketmine\math\Vector3(0, 0, 0));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleUpdateClientInputLocks($this);
	}
}
