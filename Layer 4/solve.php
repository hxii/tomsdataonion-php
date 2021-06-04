<?php

$raw = file_get_contents('payload.txt');

// $ipv4_header = substr($raw, 0, 20);
// $udp_header = substr($raw, 20, 8);

$packets = getPackets($raw);
filterPackets($packets);
foreach ($packets as $packet) {
	print_r($packet);
	// print($packet['data']);
}

function str2hex(string $string) {
	$return = '';
	for ($i = 0; $i < strlen($string); $i++) {
		// $hex     = sprintf("%x", mb_ord($string[$i])); // Character => decimal value => hexadecimal value
		// var_dump($string[$i]);
		// var_dump(mb_ord($string[$i]));
		// var_dump(ord($string[$i]));
		// var_dump(dechex(ord($string[$i])));
		$hex     = unpack("H*", $string[$i]);
		// var_dump($hex);
		// die();
		$return .= str_pad($hex[1], 2, '0', STR_PAD_LEFT); // Pad with leading zero if shorter than two
	}
	return $return;
}

function str2bin(string $string) {
	$return = '';
	for ($i = 0; $i < strlen($string); $i++) {
		$bin = unpack('I*', $string[$i]);
		$return .= padbin($bin);
	}
}

function padbin(string $binary, $padding = 16) : String {
	return (strlen($binary) < $padding) ? str_pad($binary, $padding, '0', STR_PAD_LEFT) : $binary;
}

function padhex($hex, int $padding = 2) : String {
	if (strlen($hex) < $padding) {
		$hex = str_pad($hex, $padding, '0', STR_PAD_LEFT);
	}
	return $hex;
}

function parseUDPHeader(string $header) : Array {
	return [
		'source_port'      => substr($header, 0, 4)
		'destination_port' => substr($header, 4, 4),
		'length'           => substr($header, 8, 4),
		'checksum'         => substr($header, 12, 4),
	];
}

function parseIPV4Header(string $header) : Array {
	// 45 00 00 59 00 00 40 00 20 11 43 00 0A 01 01 0A 0A 01 01 00
	// 45 00 00 44 ad 0b 00 00 40 11 72 72 ac 14 02 fd ac 14 00 06
	return [
		'version'        => substr($header, 0, 1), // 4
		'header_length'  => substr($header, 1, 1), // 5
		'type'           => substr($header, 2, 2), // 00
		'total_length'   => substr($header, 4, 4), // 00 44
		'identification' => substr($header, 8, 4), // ad 0b
		'flags'          => substr($header, 12, 4), // 00 00
		'ttl'            => substr($header, 16, 2), // 40
		'protocol'       => substr($header, 18, 2), // 11
		'checksum'       => substr($header, 20, 4), // 72 72
		'source'         => substr($header, 24, 8), // ac 14 02 fd
		'destination'    => substr($header, 32, 8), // ac 14 02 fd
	];
}

function pseudoHeader(array $packet) {
	$header = [
		'source' => $packet['ipv4_header']['source'],
		'destination' => $packet['ipv4_header']['destination'],
		'zeroes' => '00',
		'protocol' => $packet['ipv4_header']['protocol'],
		'udp_length' => $packet['udp_header']['length'],
		'source_port' => $packet['udp_header']['source_port'],
		'destination_port' => $packet['udp_header']['destination_port'],
	];
}

function hex2IP(string $hex) : String {
	$groups = str_split($hex, 2);
	foreach ($groups as &$item) {
		$item = base_convert($item, 16, 10);
	}
	return implode('.', $groups);
}

function getPacket(&$payload) {
	$ipv4_header = substr($payload, 0, 20); // Get first 20 bytes (0-20) - IPv4 header
	$udp_header  = substr($payload, 20, 8); // Get next 8 bytes (20-28) - UDP header
	if ($ipv4_header && $udp_header) { // If we were able to get the substring
		$header  = parseIPV4Header(str2hex($ipv4_header)); // Parse IPv4 header
		$udp     = parseUDPHeader(str2hex($udp_header)); // Parse UDP header
		$length  = base_convert($header['total_length'], 16, 10); // Convert length to decimal
		$data    = substr($payload, 28, $length - 28); // Get data based on total_length - header length
		$payload = substr($payload, $length); // Remove packet from payload
		return [ // Return packet
			'ipv4_header' => $header,
			'udp_header'  => $udp,
			'data'        => $data
		];
	}
	return false; // Or return false if we couldn't get the headers
}

function getPackets(string $payload) : Array {
	$packets = []; // Init empty array
	while ($packet = getPacket($payload)) { // While we are able to get packets
		$packets[] = $packet; // Add packet to packets array
	}
	return $packets; // Return packets array
}

function filterPackets(array &$packets) {
	$packets = array_filter($packets, function($item) {
		return (hex2IP($item['ipv4_header']['source']) == '10.1.1.10')
		&& (hex2IP($item['ipv4_header']['destination']) == '10.1.1.200');
	});
}