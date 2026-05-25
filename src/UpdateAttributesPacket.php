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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute;
use function count;

class UpdateAttributesPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

	public int $actorRuntimeId;
	/** @var UpdateAttribute[] */
	public array $entries = [];
	public int $tick = 0;

	/**
	 * @generate-create-func
	 * @param UpdateAttribute[] $entries
	 */
	public static function create(int $actorRuntimeId, array $entries, int $tick) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->entries = $entries;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$len = VarInt::readUnsignedInt($in);
		if($this->protocolId === 419){
			for($i = 0; $i < $len; ++$i){
				$min = LE::readFloat($in);
				$max = LE::readFloat($in);
				$current = LE::readFloat($in);
				$default = LE::readFloat($in);
				$id = CommonTypes::getString($in);
				$this->entries[] = new \pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute($id, $min, $max, $current, $min, $max, $default, []);
			}
		}else{
			for($i = 0; $i < $len; ++$i){
				$this->entries[] = \pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute::read($in);
			}
		}
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeUnsignedInt($out, count($this->entries));
		if($this->protocolId === 419){
			foreach($this->entries as $entry){
				LE::writeFloat($out, $entry->getMin());
				LE::writeFloat($out, $entry->getMax());
				LE::writeFloat($out, $entry->getCurrent());
				LE::writeFloat($out, $entry->getDefault());
				CommonTypes::putString($out, $entry->getId());
			}
		}else{
			foreach($this->entries as $entry){
				$entry->write($out);
			}
		}
		VarInt::writeUnsignedLong($out, $this->tick);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleUpdateAttributes($this);
	}
}
