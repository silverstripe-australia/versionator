<?php

include_once __DIR__.'/vendor/autoload.php';

use Packagist\Api\Result\Package\Version;

$managed = array(
	"silverstripe/multivaluefield",
	"silverstripe/queuedjobs",
	"silverstripe/restrictedobjects",
	"silverstripe/webservices",
);

$options = getopt('f:');

if (!isset($options['f'])) {
	exit("Specify a composer file to check.\n");
}

$file = realpath($options['f']);
if (!file_exists($file)) {
	o("Could not read $file");
	exit();
}

$composer = json_decode(file_get_contents($file));

if (!$composer) {
	o("Could not parse composer details");
	exit();
}

if (!isset($composer->require)) {
	o("No requirements found, enjoy!");
	exit();
}

$client = new Packagist\Api\Client();

foreach ($composer->require as $package => $requirement) {
	if (!in_array($package, $managed)) {
		continue;
	}

	o("Checking requirement $package $requirement");
	
	$package = $client->get($package);
	
	o("Retrieved package " . $package->getName());
	
	foreach ($package->getVersions() as $version) {
		print_r($version);
		/* @var $version Version */
		$source = $version->getSource();
	}
	
}

echo "\n";

function o($txt) {
	echo "$txt\n";
}