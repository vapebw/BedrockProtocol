<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\utils\Filesystem;
use function count;

class v419StartGamePacket extends StartGamePacket implements ClientboundPacket{
	public const NETWORK_ID = 0x0b;

	public array $itemTable = [];

	private static ?array $cachedBlockPalette = null;

	private static function getBlockPalette1_16_100() : array{
		if(self::$cachedBlockPalette !== null){
			return self::$cachedBlockPalette;
		}
		$raw = Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_1_16_100_NBT);
		$nbtSerializer = new NetworkNbtSerializer();
		$entries = [];
		foreach($nbtSerializer->readMultiple($raw) as $root){
			$tag = $root->mustGetCompoundTag();
			$name = $tag->getString("name");
			$statesTag = $tag->getCompoundTag("states") ?? CompoundTag::create();
			$blockTag = CompoundTag::create()
				->setString("name", $name)
				->setTag("states", $statesTag);
			$entries[] = new BlockPaletteEntry($name, new CacheableNbt($blockTag));
		}
		self::$cachedBlockPalette = $entries;
		return $entries;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->playerGamemode = VarInt::readSignedInt($in);
		$this->playerPosition = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);

		$settings = new LevelSettings();
		$settings->seed = VarInt::readSignedInt($in);
		$settings->spawnSettings = SpawnSettings::read($in);
		$settings->generator = VarInt::readSignedInt($in);
		$settings->worldGamemode = VarInt::readSignedInt($in);
		$settings->difficulty = VarInt::readSignedInt($in);
		$settings->spawnPosition = CommonTypes::getBlockPosition($in);
		$settings->hasAchievementsDisabled = CommonTypes::getBool($in);
		$settings->time = VarInt::readSignedInt($in);
		$settings->eduEditionOffer = VarInt::readSignedInt($in);
		$settings->hasEduFeaturesEnabled = CommonTypes::getBool($in);
		$settings->eduProductUUID = CommonTypes::getString($in);
		$settings->rainLevel = LE::readFloat($in);
		$settings->lightningLevel = LE::readFloat($in);
		$settings->hasConfirmedPlatformLockedContent = CommonTypes::getBool($in);
		$settings->isMultiplayerGame = CommonTypes::getBool($in);
		$settings->hasLANBroadcast = CommonTypes::getBool($in);
		$settings->xboxLiveBroadcastMode = VarInt::readSignedInt($in);
		$settings->platformBroadcastMode = VarInt::readSignedInt($in);
		$settings->commandsEnabled = CommonTypes::getBool($in);
		$settings->isTexturePacksRequired = CommonTypes::getBool($in);
		$settings->gameRules = CommonTypes::getGameRules($in, true);
		$settings->experiments = Experiments::read($in);
		$settings->hasBonusChestEnabled = CommonTypes::getBool($in);
		$settings->hasStartWithMapEnabled = CommonTypes::getBool($in);
		$settings->defaultPlayerPermission = VarInt::readSignedInt($in);
		$settings->serverChunkTickRadius = LE::readSignedInt($in);
		$settings->hasLockedBehaviorPack = CommonTypes::getBool($in);
		$settings->hasLockedResourcePack = CommonTypes::getBool($in);
		$settings->isFromLockedWorldTemplate = CommonTypes::getBool($in);
		$settings->useMsaGamertagsOnly = CommonTypes::getBool($in);
		$settings->isFromWorldTemplate = CommonTypes::getBool($in);
		$settings->isWorldTemplateOptionLocked = CommonTypes::getBool($in);
		$settings->onlySpawnV1Villagers = CommonTypes::getBool($in);
		$settings->vanillaVersion = CommonTypes::getString($in);
		$settings->limitedWorldWidth = LE::readSignedInt($in);
		$settings->limitedWorldLength = LE::readSignedInt($in);
		$settings->isNewNether = CommonTypes::getBool($in);
		if(CommonTypes::getBool($in)){
			$settings->experimentalGameplayOverride = CommonTypes::getBool($in);
		}else{
			$settings->experimentalGameplayOverride = null;
		}
		$this->levelSettings = $settings;

		$this->levelId = CommonTypes::getString($in);
		$this->worldName = CommonTypes::getString($in);
		$this->premiumWorldTemplateId = CommonTypes::getString($in);
		$this->isTrial = CommonTypes::getBool($in);
		$playerMovementType = VarInt::readSignedInt($in);
		$this->playerMovementSettings = new PlayerMovementSettings($playerMovementType, true);
		$this->currentTick = LE::readSignedLong($in);
		$this->enchantmentSeed = VarInt::readSignedInt($in);

		$this->blockPalette = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$blockName = CommonTypes::getString($in);
			$state = CommonTypes::getNbtCompoundRoot($in);
			$this->blockPalette[] = new BlockPaletteEntry($blockName, new CacheableNbt($state));
		}

		$this->itemTable = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$stringId = CommonTypes::getString($in);
			$numericId = LE::readSignedShort($in);
			$isComponentBased = CommonTypes::getBool($in);
			$this->itemTable[] = new ItemTypeEntry($stringId, $numericId, $isComponentBased);
		}

		$this->multiplayerCorrelationId = CommonTypes::getString($in);
		$this->enableNewInventorySystem = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->playerGamemode);
		CommonTypes::putVector3($out, $this->playerPosition);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);

		$settings = $this->levelSettings;
		VarInt::writeSignedInt($out, (int)$settings->seed);
		$settings->spawnSettings->write($out);
		VarInt::writeSignedInt($out, $settings->generator);
		VarInt::writeSignedInt($out, $settings->worldGamemode);
		VarInt::writeSignedInt($out, $settings->difficulty);
		CommonTypes::putBlockPosition($out, $settings->spawnPosition);
		CommonTypes::putBool($out, $settings->hasAchievementsDisabled);
		VarInt::writeSignedInt($out, $settings->time);
		VarInt::writeSignedInt($out, $settings->eduEditionOffer);
		CommonTypes::putBool($out, $settings->hasEduFeaturesEnabled);
		CommonTypes::putString($out, $settings->eduProductUUID);
		LE::writeFloat($out, $settings->rainLevel);
		LE::writeFloat($out, $settings->lightningLevel);
		CommonTypes::putBool($out, $settings->hasConfirmedPlatformLockedContent);
		CommonTypes::putBool($out, $settings->isMultiplayerGame);
		CommonTypes::putBool($out, $settings->hasLANBroadcast);
		VarInt::writeSignedInt($out, $settings->xboxLiveBroadcastMode);
		VarInt::writeSignedInt($out, $settings->platformBroadcastMode);
		CommonTypes::putBool($out, $settings->commandsEnabled);
		CommonTypes::putBool($out, $settings->isTexturePacksRequired);
		CommonTypes::putGameRules($out, $settings->gameRules, true);
		$settings->experiments->write($out);
		CommonTypes::putBool($out, $settings->hasBonusChestEnabled);
		CommonTypes::putBool($out, $settings->hasStartWithMapEnabled);
		VarInt::writeSignedInt($out, $settings->defaultPlayerPermission);
		LE::writeSignedInt($out, $settings->serverChunkTickRadius);
		CommonTypes::putBool($out, $settings->hasLockedBehaviorPack);
		CommonTypes::putBool($out, $settings->hasLockedResourcePack);
		CommonTypes::putBool($out, $settings->isFromLockedWorldTemplate);
		CommonTypes::putBool($out, $settings->useMsaGamertagsOnly);
		CommonTypes::putBool($out, $settings->isFromWorldTemplate);
		CommonTypes::putBool($out, $settings->isWorldTemplateOptionLocked);
		CommonTypes::putBool($out, $settings->onlySpawnV1Villagers);
		CommonTypes::putString($out, $settings->vanillaVersion);
		LE::writeSignedInt($out, $settings->limitedWorldWidth);
		LE::writeSignedInt($out, $settings->limitedWorldLength);
		CommonTypes::putBool($out, $settings->isNewNether);
		CommonTypes::putBool($out, $settings->experimentalGameplayOverride !== null);
		if($settings->experimentalGameplayOverride !== null){
			CommonTypes::putBool($out, $settings->experimentalGameplayOverride);
		}

		CommonTypes::putString($out, $this->levelId);
		CommonTypes::putString($out, $this->worldName);
		CommonTypes::putString($out, $this->premiumWorldTemplateId);
		CommonTypes::putBool($out, $this->isTrial);
		VarInt::writeSignedInt($out, 2);
		LE::writeSignedLong($out, $this->currentTick);
		VarInt::writeSignedInt($out, $this->enchantmentSeed);

		$palette = $this->blockPalette;
		VarInt::writeUnsignedInt($out, count($palette));
		$nbtWriter = new NetworkNbtSerializer();
		foreach($palette as $entry){
			CommonTypes::putString($out, $entry->getName());
			$out->writeByteArray($nbtWriter->write(new TreeRoot($entry->getStates()->mustGetCompoundTag())));
		}

		VarInt::writeUnsignedInt($out, count($this->itemTable));
		foreach($this->itemTable as $entry){
			CommonTypes::putString($out, $entry->getStringId());
			LE::writeSignedShort($out, $entry->getNumericId());
			CommonTypes::putBool($out, $entry->isComponentBased());
		}

		CommonTypes::putString($out, $this->multiplayerCorrelationId);
		CommonTypes::putBool($out, $this->enableNewInventorySystem);
	}

	public static function fromCurrent(\pocketmine\network\mcpe\protocol\StartGamePacket $packet, array $itemTable) : self{
		$result = new self();
		foreach(get_object_vars($packet) as $key => $value){
			$result->$key = $value;
		}
		$result->itemTable = $itemTable;
		$result->blockPalette = self::getBlockPalette1_16_100();
		return $result;
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleStartGame($this);
	}
}
