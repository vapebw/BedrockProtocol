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

namespace pocketmine\network\mcpe\protocol\types\biome\chunkgen;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class BiomeClimateData{

	public function __construct(
		private float $temperature,
		private float $downfall,
		private float $snowAccumulationMin,
		private float $snowAccumulationMax,
	){}

	public function getTemperature() : float{ return $this->temperature; }

	public function getDownfall() : float{ return $this->downfall; }

	public function getSnowAccumulationMin() : float{ return $this->snowAccumulationMin; }

	public function getSnowAccumulationMax() : float{ return $this->snowAccumulationMax; }

	public static function read(ByteBufferReader $in) : self{
		$protocolId = \pocketmine\network\mcpe\protocol\serializer\CommonTypes::getStreamProtocolId($in);
		$temperature = LE::readFloat($in);
		$downfall = LE::readFloat($in);
		if($protocolId < 844){
			$redSporeDensity = LE::readFloat($in);
			$blueSporeDensity = LE::readFloat($in);
			$ashDensity = LE::readFloat($in);
			$whiteAshDensity = LE::readFloat($in);
		}
		$snowAccumulationMin = LE::readFloat($in);
		$snowAccumulationMax = LE::readFloat($in);

		return new self(
			$temperature,
			$downfall,
			$snowAccumulationMin,
			$snowAccumulationMax
		);
	}

	public function write(ByteBufferWriter $out) : void{
		$protocolId = \pocketmine\network\mcpe\protocol\serializer\CommonTypes::getStreamProtocolId($out);
		LE::writeFloat($out, $this->temperature);
		LE::writeFloat($out, $this->downfall);
		if($protocolId < 844){
			LE::writeFloat($out, 0.0); // redSporeDensity
			LE::writeFloat($out, 0.0); // blueSporeDensity
			LE::writeFloat($out, 0.0); // ashDensity
			LE::writeFloat($out, 0.0); // whiteAshDensity
		}
		LE::writeFloat($out, $this->snowAccumulationMin);
		LE::writeFloat($out, $this->snowAccumulationMax);
	}
}
