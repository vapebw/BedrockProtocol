# BedrockProtocol
[![CI](https://github.com/pmmp/BedrockProtocol/actions/workflows/ci.yml/badge.svg)](https://github.com/pmmp/BedrockProtocol/actions/workflows/ci.yml)

An implementation of the Minecraft: Bedrock Edition protocol in PHP

This library implements all of the packets in the Minecraft: Bedrock Edition protocol, as well as a few extra things needed to support them.
However, at the time of writing, it does _not_ include the following:
- Anything related to JWT handling/verification
- Anything related to encryption
- Anything related to compression

## Decoding packets
Assuming you've decrypted and decompressed a Minecraft packet successfully, you're next going to want to decode it.
With this library, that's currently done using `PacketBatch`, like so:

```php
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\PacketPool;
use pmmp\encoding\ByteBufferReader;

foreach(PacketBatch::decodePackets(new ByteBufferReader($payload), PacketPool::getInstance()) as $packetObject){
    var_dump($packetObject); //tada
}
```

## Encoding packets
This is easy:

```php
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pmmp\encoding\ByteBufferWriter;

/** @var Packet[] $packets */
$stream = new ByteBufferWriter();
PacketBatch::encodePackets($stream, $packets);
$batchPayload = $stream->getData();
```

## Multi-Version Support
This fork implements multi-protocol support, allowing packets to dynamically branch their serialization behavior depending on the client's protocol version.

### Protocol Branching via `$protocolId`
The base `DataPacket` class includes a `$protocolId` property (defaulting to `ProtocolInfo::CURRENT_PROTOCOL`). During packet encoding and decoding, packet serialization logic can check this property to serialize fields conditionally:

```php
protected function encodePayload(ByteBufferWriter $out) : void{
	// Shared fields...
	if($this->protocolId >= ProtocolInfo::CURRENT_PROTOCOL){
		// Serialize fields present in newer versions
	}else{
		// Serialize fields or structures present in older versions
	}
}
```

This ensures a single server instance can serialize and deserialize packets correctly for multiple active protocol versions without needing separate packet classes for each version.

## Footnotes
This library is a little rough around the edges, since it's only ever been intended for PocketMine-MP usage. It's only recently that this mess has been separated from the core to allow it to be used by other things.
This means that API changes might be in order, and your feedback would be nice to drive them.
If you want to improve BedrockProtocol, please open issues with suggestions, or better, make pull requests.

