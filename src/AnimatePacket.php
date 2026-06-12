<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class AnimatePacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::ANIMATE_PACKET;

	public const ACTION_SWING_ARM = 1;

	public const ACTION_STOP_SLEEP = 3;
	public const ACTION_CRITICAL_HIT = 4;
	public const ACTION_MAGICAL_CRITICAL_HIT = 5;
	public const ACTION_ROW_RIGHT = 128;
	public const ACTION_ROW_LEFT = 129;

	public int $action;
	public int $actorRuntimeId;
	public float $data = 0.0;
	public ?string $swingSource = null;

	public static function create(int $actorRuntimeId, int $action, float $data = 0.0, ?string $swingSource = null) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->action = $action;
		$result->data = $data;
		$result->swingSource = $swingSource;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$protocolId = CommonTypes::getStreamProtocolId($in);
		$this->action = $protocolId >= 898 ? Byte::readUnsigned($in) : VarInt::readSignedInt($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= 859 || ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT)){
			$this->data = LE::readFloat($in);
		}else{
			$this->data = 0.0;
		}
		if($protocolId >= 898){
			$this->swingSource = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}else{
			$this->swingSource = null;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$protocolId = CommonTypes::getStreamProtocolId($out);
		if($protocolId >= 898){
			Byte::writeUnsigned($out, $this->action);
		}else{
			VarInt::writeSignedInt($out, $this->action);
		}
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= 859 || ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT)){
			LE::writeFloat($out, $this->data);
		}
		if($protocolId >= 898){
			CommonTypes::writeOptional($out, $this->swingSource, CommonTypes::putString(...));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleAnimate($this);
	}
}
