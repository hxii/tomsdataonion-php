<?php

$file = 'payload.txt';

$raw = file_get_contents($file);

// for ($i=0; $i < strlen($raw); $i++) {
$binary = '';
for ($i=0; $i < strlen($raw); $i++) {
	$byte = $raw[$i]; // Get character from string
	$ord = ord($byte); // Get decimal value for character
	$bin = padbin(decbin($ord)); // Convert decimal to binary and pad with zeroes
	$data = substr($bin, 0, -1); // Get chars 0-6
	$parity = substr($bin, -1); // Get char 7 - parity bit
	$onesOdd = substr_count($data, 1) % 2; // Is number of '1' in $data odd?
	if (($onesOdd && $parity) || (!$onesOdd && !$parity)) {
		// If number of '1' is odd and parity bit is 1
		// or number of '1' is even and parity bit is 0
		// add binary data.
		$binary .= $data;
	}
}

$binary = str_split($binary, 8); // Split data back to binary groups (8bits)

foreach ($binary as $item) {
	echo chr(bindec($item)); // Convert binary back to decimal and get character
}

function padbin(string $binary) : String {
	return (strlen($binary) < 8) ? str_pad($binary, 8, '0', STR_PAD_LEFT) : $binary;
}