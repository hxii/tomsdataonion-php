<?php

// Binary Read and Convert

$file = './payload-encrypted.txt';

$fh = fopen($file, 'r');
$raw = fread($fh, filesize($file));
fclose($fh);

$binary = [];

for ($i=0; $i < strlen($raw); $i++) {
	$byte = $raw[$i]; // Get character from string
	$ord = ord($byte); // Get decimal value for character
	$xor = $ord ^ 85; // Flip every n+1 bit (i.e. 01010101) so XOR 85
	$bin = padbin(decbin($xor)); // Convert decimal to binary and pad with zeroes
	$bin = preg_replace('/(\w+)(\w)$/','$2$1', $bin); // Shift to the right via replace
	$binary[] = $bin;
}

foreach ($binary as $item) {
	echo chr(bindec($item)); // Convert binary back to decimal and get character
}

function padbin(string $binary) : String {
	return (strlen($binary) < 8) ? str_pad($binary, 8, '0', STR_PAD_LEFT) : $binary;
}