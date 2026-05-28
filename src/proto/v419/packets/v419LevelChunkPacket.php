<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use function count;

class v419LevelChunkPacket extends LevelChunkPacket implements ClientboundPacket{
	public const NETWORK_ID = 0x3a;

	protected function decodePayload(ByteBufferReader $in) : void{
		$chunkPosition = ChunkPosition::read($in);
		$subChunkCount = VarInt::readUnsignedInt($in);
		$clientSubChunkRequestsEnabled = false;

		$usedBlobHashes = null;
		$cacheEnabled = CommonTypes::getBool($in);
		if($cacheEnabled){
			$usedBlobHashes = [];
			$count = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $count; ++$i){
				$usedBlobHashes[] = LE::readUnsignedLong($in);
			}
		}
		$extraPayload = CommonTypes::getString($in);

		$propPos = new \ReflectionProperty(LevelChunkPacket::class, 'chunkPosition');
		$propPos->setAccessible(true);
		$propPos->setValue($this, $chunkPosition);

		$propSub = new \ReflectionProperty(LevelChunkPacket::class, 'subChunkCount');
		$propSub->setAccessible(true);
		$propSub->setValue($this, $subChunkCount);

		$propReq = new \ReflectionProperty(LevelChunkPacket::class, 'clientSubChunkRequestsEnabled');
		$propReq->setAccessible(true);
		$propReq->setValue($this, $clientSubChunkRequestsEnabled);

		$propBlob = new \ReflectionProperty(LevelChunkPacket::class, 'usedBlobHashes');
		$propBlob->setAccessible(true);
		$propBlob->setValue($this, $usedBlobHashes);

		$propPayload = new \ReflectionProperty(LevelChunkPacket::class, 'extraPayload');
		$propPayload->setAccessible(true);
		$propPayload->setValue($this, $extraPayload);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$this->getChunkPosition()->write($out);
		VarInt::writeUnsignedInt($out, $this->getSubChunkCount());

		CommonTypes::putBool($out, $this->getUsedBlobHashes() !== null);
		if($this->getUsedBlobHashes() !== null){
			VarInt::writeUnsignedInt($out, count($this->getUsedBlobHashes()));
			foreach($this->getUsedBlobHashes() as $hash){
				LE::writeUnsignedLong($out, $hash);
			}
		}
		CommonTypes::putString($out, $this->getExtraPayload());
	}

	public static function fromCurrent(LevelChunkPacket $packet) : self{
		$result = new self();
		$propPos = new \ReflectionProperty(LevelChunkPacket::class, 'chunkPosition');
		$propPos->setAccessible(true);
		$propPos->setValue($result, $packet->getChunkPosition());

		$propSub = new \ReflectionProperty(LevelChunkPacket::class, 'subChunkCount');
		$propSub->setAccessible(true);
		$propSub->setValue($result, $packet->getSubChunkCount());

		$propReq = new \ReflectionProperty(LevelChunkPacket::class, 'clientSubChunkRequestsEnabled');
		$propReq->setAccessible(true);
		$propReq->setValue($result, $packet->isClientSubChunkRequestsEnabled());

		$propBlob = new \ReflectionProperty(LevelChunkPacket::class, 'usedBlobHashes');
		$propBlob->setAccessible(true);
		$propBlob->setValue($result, $packet->getUsedBlobHashes());

		$propPayload = new \ReflectionProperty(LevelChunkPacket::class, 'extraPayload');
		$propPayload->setAccessible(true);
		$propPayload->setValue($result, $packet->getExtraPayload());

		return $result;
	}

	public static function create(ChunkPosition $chunkPosition, int $dimensionId, int $subChunkCount, bool $clientSubChunkRequestsEnabled, ?array $usedBlobHashes, string $extraPayload) : self{
		$result = new self();
		$propPos = new \ReflectionProperty(LevelChunkPacket::class, 'chunkPosition');
		$propPos->setAccessible(true);
		$propPos->setValue($result, $chunkPosition);

		$propDim = new \ReflectionProperty(LevelChunkPacket::class, 'dimensionId');
		$propDim->setAccessible(true);
		$propDim->setValue($result, $dimensionId);

		$propSub = new \ReflectionProperty(LevelChunkPacket::class, 'subChunkCount');
		$propSub->setAccessible(true);
		$propSub->setValue($result, $subChunkCount);

		$propReq = new \ReflectionProperty(LevelChunkPacket::class, 'clientSubChunkRequestsEnabled');
		$propReq->setAccessible(true);
		$propReq->setValue($result, $clientSubChunkRequestsEnabled);

		$propBlob = new \ReflectionProperty(LevelChunkPacket::class, 'usedBlobHashes');
		$propBlob->setAccessible(true);
		$propBlob->setValue($result, $usedBlobHashes);

		$propPayload = new \ReflectionProperty(LevelChunkPacket::class, 'extraPayload');
		$propPayload->setAccessible(true);
		$propPayload->setValue($result, $extraPayload);

		return $result;
	}
}
