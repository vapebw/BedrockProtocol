<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\proto\v419\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumConstraintRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandOverloadRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandParameterRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandRawData;
use function count;

class v419AvailableCommandsPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	public array $enumValues = [];
	public array $chainedSubCommandValues = [];
	public array $postfixes = [];
	public array $enums = [];
	public array $chainedSubCommandData = [];
	public array $commandData = [];
	public array $softEnums = [];
	public array $enumConstraints = [];

	protected function decodePayload(ByteBufferReader $in) : void{
		$enumValues = [];
		for($i = 0, $enumValuesCount = VarInt::readUnsignedInt($in); $i < $enumValuesCount; ++$i){
			$enumValues[] = CommonTypes::getString($in);
		}
		$this->enumValues = $enumValues;

		$postfixes = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$postfixes[] = CommonTypes::getString($in);
		}
		$this->postfixes = $postfixes;

		$enums = [];
		$enumsCount = VarInt::readUnsignedInt($in);
		$listSize = count($enumValues);
		for($i = 0; $i < $enumsCount; ++$i){
			$enumName = CommonTypes::getString($in);
			$valueIndexes = [];
			for($j = 0, $count = VarInt::readUnsignedInt($in); $j < $count; ++$j){
				if($listSize < 256){
					$valueIndexes[] = Byte::readUnsigned($in);
				}elseif($listSize < 65536){
					$valueIndexes[] = LE::readUnsignedShort($in);
				}else{
					$valueIndexes[] = LE::readUnsignedInt($in);
				}
			}
			$enums[] = new CommandEnumRawData($enumName, $valueIndexes);
		}
		$this->enums = $enums;

		$this->commandData = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$name = CommonTypes::getString($in);
			$description = CommonTypes::getString($in);
			$flags = Byte::readUnsigned($in);
			$permission = Byte::readUnsigned($in);
			$aliases = LE::readSignedInt($in);
			$overloads = [];
			for($overloadIndex = 0, $overloadCount = VarInt::readUnsignedInt($in); $overloadIndex < $overloadCount; ++$overloadIndex){
				$parameters = [];
				for($paramIndex = 0, $paramCount = VarInt::readUnsignedInt($in); $paramIndex < $paramCount; ++$paramIndex){
					$paramName = CommonTypes::getString($in);
					$typeInfo = LE::readUnsignedInt($in);
					$optional = CommonTypes::getBool($in);
					$paramFlags = Byte::readUnsigned($in);
					$parameters[] = new CommandParameterRawData($paramName, $typeInfo, $optional, $paramFlags);
				}
				$overloads[] = new CommandOverloadRawData(false, $parameters);
			}
			$this->commandData[] = new CommandRawData($name, $description, $flags, (string)$permission, $aliases, [], $overloads);
		}

		$this->softEnums = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$enumName = CommonTypes::getString($in);
			$values = [];
			for($j = 0, $valCount = VarInt::readUnsignedInt($in); $j < $valCount; ++$j){
				$values[] = CommonTypes::getString($in);
			}
			$this->softEnums[] = new CommandSoftEnum($enumName, $values);
		}

		$this->enumConstraints = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$affectedValueIndex = LE::readUnsignedInt($in);
			$enumIndex = LE::readUnsignedInt($in);
			$constraintCount = VarInt::readUnsignedInt($in);
			$constraints = [];
			for($j = 0; $j < $constraintCount; ++$j){
				$constraints[] = Byte::readUnsigned($in);
			}
			$this->enumConstraints[] = new CommandEnumConstraintRawData($enumIndex, $affectedValueIndex, $constraints);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$enumValueIndexes = [];
		$postfixIndexes = [];
		$enumIndexes = [];
		$enums = [];

		$addEnumFn = static function(string $name, array $values) use (&$enums, &$enumIndexes, &$enumValueIndexes) : void{
			if(!isset($enumIndexes[$name])){
				$enumIndexes[$name] = count($enumIndexes);
				$enums[] = ['name' => $name, 'values' => $values];
			}
			foreach($values as $str){
				$enumValueIndexes[$str] = $enumValueIndexes[$str] ?? count($enumValueIndexes);
			}
		};

		foreach($this->enums as $enum){
			$values = [];
			foreach($enum->getValueIndexes() as $idx){
				if(isset($this->enumValues[$idx])){
					$values[] = $this->enumValues[$idx];
				}
			}
			$addEnumFn($enum->getName(), $values);
		}

		foreach($this->commandData as $commandData){
			if($commandData->getAliasEnumIndex() !== -1 && isset($this->enums[$commandData->getAliasEnumIndex()])){
				$aliasEnum = $this->enums[$commandData->getAliasEnumIndex()];
				$values = [];
				foreach($aliasEnum->getValueIndexes() as $idx){
					if(isset($this->enumValues[$idx])){
						$values[] = $this->enumValues[$idx];
					}
				}
				$addEnumFn($aliasEnum->getName(), $values);
			}
			foreach($commandData->getOverloads() as $overload){
				foreach($overload->getParameters() as $parameter){
					$typeInfo = $parameter->getTypeInfo();
					if(($typeInfo & 0x200000) !== 0){
						$enumIdx = $typeInfo & 0xffff;
						if(isset($this->enums[$enumIdx])){
							$paramEnum = $this->enums[$enumIdx];
							$values = [];
							foreach($paramEnum->getValueIndexes() as $idx){
								if(isset($this->enumValues[$idx])){
									$values[] = $this->enumValues[$idx];
								}
							}
							$addEnumFn($paramEnum->getName(), $values);
						}
					}
					if(($typeInfo & 0x1000000) !== 0){
						$postfixIdx = $typeInfo & 0xffff;
						if(isset($this->postfixes[$postfixIdx])){
							$postfixStr = $this->postfixes[$postfixIdx];
							$postfixIndexes[$postfixStr] = $postfixIndexes[$postfixStr] ?? count($postfixIndexes);
						}
					}
				}
			}
		}

		VarInt::writeUnsignedInt($out, count($enumValueIndexes));
		foreach($enumValueIndexes as $val => $idx){
			CommonTypes::putString($out, (string)$val);
		}

		VarInt::writeUnsignedInt($out, count($postfixIndexes));
		foreach($postfixIndexes as $postfix => $idx){
			CommonTypes::putString($out, (string)$postfix);
		}

		VarInt::writeUnsignedInt($out, count($enums));
		$listSize = count($enumValueIndexes);
		foreach($enums as $enum){
			CommonTypes::putString($out, $enum['name']);
			VarInt::writeUnsignedInt($out, count($enum['values']));
			foreach($enum['values'] as $val){
				$idx = $enumValueIndexes[$val];
				if($listSize < 256){
					Byte::writeUnsigned($out, $idx);
				}elseif($listSize < 65536){
					LE::writeUnsignedShort($out, $idx);
				}else{
					LE::writeUnsignedInt($out, $idx);
				}
			}
		}

		VarInt::writeUnsignedInt($out, count($this->commandData));
		foreach($this->commandData as $data){
			CommonTypes::putString($out, $data->getName());
			CommonTypes::putString($out, $data->getDescription());
			Byte::writeUnsigned($out, $data->getFlags());
			Byte::writeUnsigned($out, (int)$data->getPermission());

			if($data->getAliasEnumIndex() !== -1 && isset($this->enums[$data->getAliasEnumIndex()])){
				$aliasName = $this->enums[$data->getAliasEnumIndex()]->getName();
				LE::writeSignedInt($out, $enumIndexes[$aliasName] ?? -1);
			}else{
				LE::writeSignedInt($out, -1);
			}

			VarInt::writeUnsignedInt($out, count($data->getOverloads()));
			foreach($data->getOverloads() as $overload){
				VarInt::writeUnsignedInt($out, count($overload->getParameters()));
				foreach($overload->getParameters() as $parameter){
					CommonTypes::putString($out, $parameter->getName());

					$typeInfo = $parameter->getTypeInfo();
					if(($typeInfo & 0x200000) !== 0){
						$enumIdx = $typeInfo & 0xffff;
						if(isset($this->enums[$enumIdx])){
							$enumName = $this->enums[$enumIdx]->getName();
							$actualEnumIdx = $enumIndexes[$enumName] ?? -1;
							$type = 0x200000 | 0x100000 | $actualEnumIdx;
						}else{
							$type = $typeInfo;
						}
					}elseif(($typeInfo & 0x1000000) !== 0){
						$postfixIdx = $typeInfo & 0xffff;
						if(isset($this->postfixes[$postfixIdx])){
							$postfixStr = $this->postfixes[$postfixIdx];
							$actualPostfixIdx = $postfixIndexes[$postfixStr] ?? -1;
							$type = 0x1000000 | $actualPostfixIdx;
						}else{
							$type = $typeInfo;
						}
					}else{
						$type = $typeInfo;
					}

					LE::writeUnsignedInt($out, $type);
					CommonTypes::putBool($out, $parameter->isOptional());
					Byte::writeUnsigned($out, $parameter->getFlags());
				}
			}
		}

		VarInt::writeUnsignedInt($out, count($this->softEnums));
		foreach($this->softEnums as $softEnum){
			CommonTypes::putString($out, $softEnum->getName());
			VarInt::writeUnsignedInt($out, count($softEnum->getValues()));
			foreach($softEnum->getValues() as $val){
				CommonTypes::putString($out, $val);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->enumConstraints));
		foreach($this->enumConstraints as $constraint){
			$affectedValIdx = -1;
			if(isset($this->enumValues[$constraint->getAffectedValueIndex()])){
				$affectedValIdx = $enumValueIndexes[$this->enumValues[$constraint->getAffectedValueIndex()]] ?? -1;
			}
			$enumIndex = -1;
			if(isset($this->enums[$constraint->getEnumIndex()])){
				$enumName = $this->enums[$constraint->getEnumIndex()]->getName();
				$enumIndex = $enumIndexes[$enumName] ?? -1;
			}

			LE::writeUnsignedInt($out, $affectedValIdx);
			LE::writeUnsignedInt($out, $enumIndex);

			VarInt::writeUnsignedInt($out, count($constraint->getConstraints()));
			foreach($constraint->getConstraints() as $v){
				Byte::writeUnsigned($out, $v);
			}
		}
	}

	public static function fromCurrent(AvailableCommandsPacket $packet) : self{
		$result = new self();
		$result->enumValues = $packet->enumValues;
		$result->chainedSubCommandValues = $packet->chainedSubCommandValues;
		$result->postfixes = $packet->postfixes;
		$result->enums = $packet->enums;
		$result->chainedSubCommandData = $packet->chainedSubCommandData;
		$result->commandData = $packet->commandData;
		$result->softEnums = $packet->softEnums;
		$result->enumConstraints = $packet->enumConstraints;
		return $result;
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
