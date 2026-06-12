<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class ClientboundDataDrivenUIShowScreenPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DATA_DRIVEN_UI_SHOW_SCREEN_PACKET;

	private string $screenId;
	private int $formId;
	private ?int $dataInstanceId;

	
	public static function create(string $screenId, int $formId, ?int $dataInstanceId) : self{
		$result = new self;
		$result->screenId = $screenId;
		$result->formId = $formId;
		$result->dataInstanceId = $dataInstanceId;
		return $result;
	}

	public function getScreenId() : string{ return $this->screenId; }

	public function getFormId() : int{ return $this->formId; }

	public function getDataInstanceId() : ?int{ return $this->dataInstanceId; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->screenId = CommonTypes::getString($in);
		if($this->protocolId >= 944){
			$this->formId = LE::readUnsignedInt($in);
			$this->dataInstanceId = CommonTypes::readOptional($in, LE::readUnsignedInt(...));
		}else{
			$this->formId = 0;
			$this->dataInstanceId = null;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->screenId);
		if($this->protocolId >= 944){
			LE::writeUnsignedInt($out, $this->formId);
			CommonTypes::writeOptional($out, $this->dataInstanceId, LE::writeUnsignedInt(...));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundDataDrivenUIShowScreen($this);
	}
}
