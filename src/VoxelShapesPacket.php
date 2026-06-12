<?php



declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\SerializableVoxelShape;
use function count;

class VoxelShapesPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::VOXEL_SHAPES_PACKET;

	
	private array $shapes;
	
	private array $nameMap;

	private int $customShapeCount;

	
	public static function create(array $shapes, array $nameMap, int $customShapeCount = 0) : self{
		$result = new self;
		$result->shapes = $shapes;
		$result->nameMap = $nameMap;
		$result->customShapeCount = $customShapeCount;
		return $result;
	}

	public function getShapes() : array{ return $this->shapes; }

	public function getNameMap() : array{ return $this->nameMap; }

	public function getCustomShapeCount() : int{ return $this->customShapeCount; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->shapes = [];
		for($i = 0, $shapesCount = VarInt::readUnsignedInt($in); $i < $shapesCount; ++$i){
			$this->shapes[] = SerializableVoxelShape::read($in);
		}

		$this->nameMap = [];
		for($i = 0, $namesCount = VarInt::readUnsignedInt($in); $i < $namesCount; ++$i){
			$name = CommonTypes::getString($in);
			$id = LE::readUnsignedShort($in);
			$this->nameMap[$name] = $id;
		}

		if($this->protocolId >= 975){
			$this->customShapeCount = LE::readUnsignedShort($in);
		}else{
			$this->customShapeCount = 0;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->shapes));
		foreach($this->shapes as $shape){
			$shape->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->nameMap));
		foreach($this->nameMap as $name => $id){
			CommonTypes::putString($out, $name);
			LE::writeUnsignedShort($out, $id);
		}

		if($this->protocolId >= 975){
			LE::writeUnsignedShort($out, $this->customShapeCount);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleVoxelShapes($this);
	}
}
