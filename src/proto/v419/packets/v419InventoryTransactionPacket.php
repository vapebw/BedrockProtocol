<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\proto\v419\v419PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use function count;

class v419InventoryTransactionPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = 0x1e;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

	public int $requestId;
	public array $requestChangedSlots;
	public bool $hasItemStackIds;
	public TransactionData $trData;

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->requestId = VarInt::readSignedInt($in);
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = VarInt::readUnsignedInt($in);
		$this->hasItemStackIds = CommonTypes::getBool($in);

		$actions = [];
		$actionCount = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $actionCount; ++$i){
			$action = new NetworkInventoryAction();
			$action->sourceType = VarInt::readUnsignedInt($in);
			switch($action->sourceType){
				case NetworkInventoryAction::SOURCE_CONTAINER:
					$action->windowId = VarInt::readSignedInt($in);
					break;
				case NetworkInventoryAction::SOURCE_WORLD:
					$action->sourceFlags = VarInt::readUnsignedInt($in);
					break;
				case NetworkInventoryAction::SOURCE_CREATIVE:
					break;
				case NetworkInventoryAction::SOURCE_TODO:
					$action->windowId = VarInt::readSignedInt($in);
					break;
				default:
					throw new PacketDecodeException("Unknown inventory action source type {$action->sourceType}");
			}
			$action->inventorySlot = VarInt::readUnsignedInt($in);
			$oldItem = v419PacketSerializer::readSlot($in);
			$newItem = v419PacketSerializer::readSlot($in);
			$newItemStackId = 0;
			if($this->hasItemStackIds){
				$newItemStackId = VarInt::readSignedInt($in);
			}
			$action->oldItem = new ItemStackWrapper(0, $oldItem);
			$action->newItem = new ItemStackWrapper($newItemStackId, $newItem);
			$actions[] = $action;
		}

		switch($transactionType){
			case self::TYPE_NORMAL:
				$this->trData = NormalTransactionData::new($actions);
				break;
			case self::TYPE_MISMATCH:
				$this->trData = MismatchTransactionData::new();
				break;
			case self::TYPE_USE_ITEM:
				$actionType = VarInt::readUnsignedInt($in);
				$blockPosition = CommonTypes::getBlockPosition($in);
				$face = VarInt::readSignedInt($in);
				$hotbarSlot = VarInt::readSignedInt($in);
				$itemInHand = v419PacketSerializer::readSlot($in);
				$playerPosition = CommonTypes::getVector3($in);
				$clickPosition = CommonTypes::getVector3($in);
				$blockRuntimeId = VarInt::readUnsignedInt($in);
				$itemInHandWrapper = new ItemStackWrapper(0, $itemInHand);
				$this->trData = UseItemTransactionData::new(
					$actions,
					$actionType,
					TriggerType::UNKNOWN,
					$blockPosition,
					$face,
					$hotbarSlot,
					$itemInHandWrapper,
					$playerPosition,
					$clickPosition,
					$blockRuntimeId,
					PredictedResult::SUCCESS,
					0
				);
				break;
			case self::TYPE_USE_ITEM_ON_ENTITY:
				$actorRuntimeId = CommonTypes::getActorRuntimeId($in);
				$actionType = VarInt::readUnsignedInt($in);
				$hotbarSlot = VarInt::readSignedInt($in);
				$itemInHand = v419PacketSerializer::readSlot($in);
				$playerPosition = CommonTypes::getVector3($in);
				$clickPosition = CommonTypes::getVector3($in);
				$itemInHandWrapper = new ItemStackWrapper(0, $itemInHand);
				$this->trData = UseItemOnEntityTransactionData::new(
					$actions,
					$actorRuntimeId,
					$actionType,
					$hotbarSlot,
					$itemInHandWrapper,
					$playerPosition,
					$clickPosition
				);
				break;
			case self::TYPE_RELEASE_ITEM:
				$actionType = VarInt::readUnsignedInt($in);
				$hotbarSlot = VarInt::readSignedInt($in);
				$itemInHand = v419PacketSerializer::readSlot($in);
				$headPosition = CommonTypes::getVector3($in);
				$itemInHandWrapper = new ItemStackWrapper(0, $itemInHand);
				$this->trData = ReleaseItemTransactionData::new(
					$actions,
					$actionType,
					$hotbarSlot,
					$itemInHandWrapper,
					$headPosition
				);
				break;
			default:
				throw new PacketDecodeException("Unknown transaction type $transactionType");
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->requestId);
		if($this->requestId !== 0){
			VarInt::writeUnsignedInt($out, count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		VarInt::writeUnsignedInt($out, $this->trData->getTypeId());

		$hasItemStackIds = false;
		foreach($this->trData->getActions() as $action){
			if($action->newItem->getStackId() !== 0){
				$hasItemStackIds = true;
				break;
			}
		}
		CommonTypes::putBool($out, $hasItemStackIds);

		VarInt::writeUnsignedInt($out, count($this->trData->getActions()));
		foreach($this->trData->getActions() as $action){
			VarInt::writeUnsignedInt($out, $action->sourceType);
			switch($action->sourceType){
				case NetworkInventoryAction::SOURCE_CONTAINER:
					VarInt::writeSignedInt($out, $action->windowId);
					break;
				case NetworkInventoryAction::SOURCE_WORLD:
					VarInt::writeUnsignedInt($out, $action->sourceFlags);
					break;
				case NetworkInventoryAction::SOURCE_CREATIVE:
					break;
				case NetworkInventoryAction::SOURCE_TODO:
					VarInt::writeSignedInt($out, $action->windowId);
					break;
				default:
					throw new \InvalidArgumentException("Unknown inventory action source type {$action->sourceType}");
			}
			VarInt::writeUnsignedInt($out, $action->inventorySlot);
			v419PacketSerializer::writeSlot($out, $action->oldItem->getItemStack());
			v419PacketSerializer::writeSlot($out, $action->newItem->getItemStack());
			if($hasItemStackIds){
				VarInt::writeSignedInt($out, $action->newItem->getStackId());
			}
		}

		$tr = $this->trData;
		if($tr instanceof UseItemTransactionData){
			VarInt::writeUnsignedInt($out, $tr->getActionType());
			CommonTypes::putBlockPosition($out, $tr->getBlockPosition());
			VarInt::writeSignedInt($out, $tr->getFace());
			VarInt::writeSignedInt($out, $tr->getHotbarSlot());
			v419PacketSerializer::writeSlot($out, $tr->getItemInHand()->getItemStack());
			CommonTypes::putVector3($out, $tr->getPlayerPosition());
			CommonTypes::putVector3($out, $tr->getClickPosition());
			VarInt::writeUnsignedInt($out, $tr->getBlockRuntimeId());
		}elseif($tr instanceof UseItemOnEntityTransactionData){
			CommonTypes::putActorRuntimeId($out, $tr->getActorRuntimeId());
			VarInt::writeUnsignedInt($out, $tr->getActionType());
			VarInt::writeSignedInt($out, $tr->getHotbarSlot());
			v419PacketSerializer::writeSlot($out, $tr->getItemInHand()->getItemStack());
			CommonTypes::putVector3($out, $tr->getPlayerPosition());
			CommonTypes::putVector3($out, $tr->getClickPosition());
		}elseif($tr instanceof ReleaseItemTransactionData){
			VarInt::writeUnsignedInt($out, $tr->getActionType());
			VarInt::writeSignedInt($out, $tr->getHotbarSlot());
			v419PacketSerializer::writeSlot($out, $tr->getItemInHand()->getItemStack());
			CommonTypes::putVector3($out, $tr->getHeadPosition());
		}
	}

	public function toModern() : InventoryTransactionPacket{
		return InventoryTransactionPacket::create($this->requestId, $this->requestChangedSlots, $this->trData);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
