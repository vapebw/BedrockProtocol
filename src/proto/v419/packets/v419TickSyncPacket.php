<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v419TickSyncPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = 0x17;

	private int $clientSendTime = 0;
	private int $serverReceiveTime = 0;

	public static function request(int $clientTime) : self{
		return self::create($clientTime, 0);
	}

	private static function create(int $clientSendTime, int $serverReceiveTime) : self{
		$result = new self();
		$result->clientSendTime = $clientSendTime;
		$result->serverReceiveTime = $serverReceiveTime;
		return $result;
	}

	public static function response(int $clientSendTime, int $serverReceiveTime) : self{
		return self::create($clientSendTime, $serverReceiveTime);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->clientSendTime = LE::readSignedLong($in);
		$this->serverReceiveTime = LE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		LE::writeSignedLong($out, $this->clientSendTime);
		LE::writeSignedLong($out, $this->serverReceiveTime);
	}
}
