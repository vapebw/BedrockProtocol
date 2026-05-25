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

namespace pocketmine\network\mcpe\protocol\types\login\clientdata;

use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function array_map;
use function base64_decode;

final class ClientDataToSkinDataHelper{

	/**
	 * @throws \InvalidArgumentException
	 */
	private static function safeB64Decode(string $base64, string $context) : string{
		$result = base64_decode($base64, true);
		if($result === false){
			throw new \InvalidArgumentException("$context: Malformed base64, cannot be decoded");
		}
		return $result;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromClientData(ClientData $clientData) : SkinData{
		$animations = [];
		$animatedImageData = isset($clientData->AnimatedImageData) ? $clientData->AnimatedImageData : [];
		foreach($animatedImageData as $k => $animation){
			$animations[] = new SkinAnimation(
				new SkinImage(
					isset($animation->ImageHeight) ? $animation->ImageHeight : 0,
					isset($animation->ImageWidth) ? $animation->ImageWidth : 0,
					self::safeB64Decode(isset($animation->Image) ? $animation->Image : base64_encode(""), "AnimatedImageData.$k.Image")
				),
				isset($animation->Type) ? $animation->Type : 0,
				isset($animation->Frames) ? $animation->Frames : 0.0,
				isset($animation->AnimationExpression) ? $animation->AnimationExpression : 0
			);
		}
		$skinId = isset($clientData->SkinId) ? $clientData->SkinId : "";
		$skinResourcePatch = isset($clientData->SkinResourcePatch) ? $clientData->SkinResourcePatch : base64_encode("");
		$skinImageHeight = isset($clientData->SkinImageHeight) ? $clientData->SkinImageHeight : 0;
		$skinImageWidth = isset($clientData->SkinImageWidth) ? $clientData->SkinImageWidth : 0;
		$skinDataStr = isset($clientData->SkinData) ? $clientData->SkinData : base64_encode("");
		$capeImageHeight = isset($clientData->CapeImageHeight) ? $clientData->CapeImageHeight : 0;
		$capeImageWidth = isset($clientData->CapeImageWidth) ? $clientData->CapeImageWidth : 0;
		$capeDataStr = isset($clientData->CapeData) ? $clientData->CapeData : base64_encode("");
		$skinGeometryData = isset($clientData->SkinGeometryData) ? $clientData->SkinGeometryData : base64_encode("");
		$skinGeometryDataEngineVersion = isset($clientData->SkinGeometryDataEngineVersion) ? $clientData->SkinGeometryDataEngineVersion : base64_encode("");
		$skinAnimationData = isset($clientData->SkinAnimationData) ? $clientData->SkinAnimationData : base64_encode("");
		$capeId = isset($clientData->CapeId) ? $clientData->CapeId : "";
		$armSize = isset($clientData->ArmSize) ? $clientData->ArmSize : "";
		$skinColor = isset($clientData->SkinColor) ? $clientData->SkinColor : "";
		$personaPieces = isset($clientData->PersonaPieces) ? $clientData->PersonaPieces : [];
		$pieceTintColors = isset($clientData->PieceTintColors) ? $clientData->PieceTintColors : [];
		$premiumSkin = isset($clientData->PremiumSkin) ? $clientData->PremiumSkin : false;
		$personaSkin = isset($clientData->PersonaSkin) ? $clientData->PersonaSkin : false;
		$capeOnClassicSkin = isset($clientData->CapeOnClassicSkin) ? $clientData->CapeOnClassicSkin : false;

		return new SkinData(
			$skinId,
			"",
			self::safeB64Decode($skinResourcePatch, "SkinResourcePatch"),
			new SkinImage($skinImageHeight, $skinImageWidth, self::safeB64Decode($skinDataStr, "SkinData")),
			$animations,
			new SkinImage($capeImageHeight, $capeImageWidth, self::safeB64Decode($capeDataStr, "CapeData")),
			self::safeB64Decode($skinGeometryData, "SkinGeometryData"),
			self::safeB64Decode($skinGeometryDataEngineVersion, "SkinGeometryDataEngineVersion"),
			self::safeB64Decode($skinAnimationData, "SkinAnimationData"),
			$capeId,
			null,
			$armSize,
			$skinColor,
			array_map(function(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
				return new PersonaSkinPiece(
					isset($piece->PieceId) ? $piece->PieceId : "",
					isset($piece->PieceType) ? $piece->PieceType : "",
					isset($piece->PackId) ? $piece->PackId : "",
					isset($piece->IsDefault) ? $piece->IsDefault : false,
					isset($piece->ProductId) ? $piece->ProductId : ""
				);
			}, $personaPieces),
			array_map(function(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
				return new PersonaPieceTintColor(
					isset($tint->PieceType) ? $tint->PieceType : "",
					isset($tint->Colors) ? $tint->Colors : []
				);
			}, $pieceTintColors),
			true,
			$premiumSkin,
			$personaSkin,
			$capeOnClassicSkin,
			true,
			$clientData->OverrideSkin ?? true,
		);
	}
}
