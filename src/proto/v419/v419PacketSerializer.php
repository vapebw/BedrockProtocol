<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;

final class v419PacketSerializer{

	private function __construct(){}

	public static function readSlot(ByteBufferReader $in) : ItemStack{
		$id = VarInt::readSignedInt($in);
		if($id === 0){
			return ItemStack::null();
		}
		$auxValue = VarInt::readSignedInt($in);
		$meta = $auxValue >> 8;
		$count = $auxValue & 0xff;

		$nbtLen = LE::readSignedShort($in);
		$nbt = null;
		if($nbtLen === -1 || $nbtLen === 0xffff){
			$nbtDataVersion = Byte::readUnsigned($in);
			if($nbtDataVersion !== 1){
				throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
			}
			$offset = $in->getOffset();
			try{
				$nbt = (new \pocketmine\nbt\LittleEndianNbtSerializer())->read($in->getData(), $offset, 512)->mustGetCompoundTag();
			}catch(\pocketmine\nbt\NbtDataException $e){
				throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
			}finally{
				$in->setOffset($offset);
			}
		}elseif($nbtLen !== 0){
			throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
		}

		$canPlaceOn = [];
		for($i = 0, $canPlaceOnCount = VarInt::readUnsignedInt($in); $i < $canPlaceOnCount; ++$i){
			$canPlaceOn[] = CommonTypes::getString($in);
		}

		$canDestroy = [];
		for($i = 0, $canDestroyCount = VarInt::readUnsignedInt($in); $i < $canDestroyCount; ++$i){
			$canDestroy[] = CommonTypes::getString($in);
		}

		$blockingTick = 0;
		if($id === 513){
			$blockingTick = VarInt::readSignedLong($in);
		}

		$extraWriter = new ByteBufferWriter();
		if($id === 513){
			$extraData = new ItemStackExtraDataShield($nbt, $canPlaceOn, $canDestroy, $blockingTick);
		}else{
			$extraData = new ItemStackExtraData($nbt, $canPlaceOn, $canDestroy);
		}
		$extraData->write($extraWriter);

		return new ItemStack($id, $meta, $count, 0, $extraWriter->getData());
	}

	public static function writeSlot(ByteBufferWriter $out, ItemStack $item) : void{
		if($item->isNull()){
			VarInt::writeSignedInt($out, 0);
			return;
		}

		VarInt::writeSignedInt($out, $item->getId());
		$auxValue = (($item->getMeta() & 0x7fff) << 8) | $item->getCount();
		VarInt::writeSignedInt($out, $auxValue);

		$nbt = null;
		$canPlaceOn = [];
		$canDestroy = [];
		$blockingTick = 0;
		$raw = $item->getRawExtraData();
		if($raw !== ""){
			$reader = new ByteBufferReader($raw);
			try{
				if($item->getId() === 513){
					$extra = ItemStackExtraDataShield::read($reader);
					$blockingTick = $extra->getBlockingTick();
				}else{
					$extra = ItemStackExtraData::read($reader);
				}
				$nbt = $extra->getNbt();
				$canPlaceOn = $extra->getCanPlaceOn();
				$canDestroy = $extra->getCanDestroy();
			}catch(\Exception $e){}
		}

		if($nbt !== null){
			LE::writeSignedShort($out, 0xffff);
			Byte::writeUnsigned($out, 1);
			$out->writeByteArray((new \pocketmine\nbt\LittleEndianNbtSerializer())->write(new \pocketmine\nbt\TreeRoot($nbt)));
		}else{
			LE::writeSignedShort($out, 0);
		}

		VarInt::writeUnsignedInt($out, count($canPlaceOn));
		foreach($canPlaceOn as $entry){
			CommonTypes::putString($out, $entry);
		}

		VarInt::writeUnsignedInt($out, count($canDestroy));
		foreach($canDestroy as $entry){
			CommonTypes::putString($out, $entry);
		}

		if($item->getId() === 513){
			VarInt::writeSignedLong($out, $blockingTick);
		}
	}
}
