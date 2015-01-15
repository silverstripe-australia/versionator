<?php

include_once __DIR__ . '/vendor/autoload.php';

use Packagist\Api\Result\Package\Version;
use GitWrapper\GitWrapper;

$managed = array(
	"silverstripe/multivaluefield",
	"silverstripe/queuedjobs",
	"silverstripe/restrictedobjects",
	"silverstripe/webservices",
);

$options = getopt('f:w:');

if (!isset($options['f'])) {
	exit("Specify a composer file to check.\n");
}

$file = realpath($options['f']);
if (!file_exists($file)) {
	o("Could not read $file");
	exit();
}

$workspace = isset($options['w']) ? $options['w'] : __DIR__ . '/workspace';

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

$wrapper = new GitWrapper();

$notices = array();

foreach ($composer->require as $packageName => $requiredVersion) {
	if (!in_array($packageName, $managed)) {
		continue;
	}

	$package_name = basename($packageName);
	o("Checking requirement $packageName $requiredVersion");

	$package = $client->get($packageName);

	o("Retrieved package " . $package->getName());

	$latestVersion = 0;
	$repo = null;
	$latestRef = null;
	
	foreach ($package->getVersions() as $version) {
		/* @var $version Version */
		$source = $version->getSource();

		$comp = version_compare($version->getVersion(), $latestVersion);
//		o("Comparing " . $version->getVersion() . " to $latestVersion : $comp");
		if (version_compare($version->getVersion(), $latestVersion) > 0) {
			$latestVersion = $version->getVersion();
			$repo = $version->getSource()->getUrl();
			$latestRef = $version->getSource()->getReference();
		}
		if (!$latestRef) {
			$latestRef = $version->getSource()->getReference();
		}
	}


	if (version_compare($latestVersion, $requiredVersion) > 0) {
		$notices[] = "$packageName has a fixed version available $latestVersion (currently $requiredVersion)";
	}

	// now check out the project
	if ($repo && $latestRef) {
		$path = $workspace . DIRECTORY_SEPARATOR . basename($package_name);
		if (!file_exists($path)) {
			$git = $wrapper->cloneRepository($source->getUrl(), $workspace . DIRECTORY_SEPARATOR . basename($package_name));
		} else {
			$git = $wrapper->workingCopy($path);
			$git->checkout('master', array('f' => true));
		}

		$git->clearOutput();
		// checkout the 'latest' ref as far as packagist knows about, then check to see
		// whether the repository has changes between it and the current master HEAD
		// $git->diff($latestRef, array('name-status' => true));
		$git->log($latestRef . '..master', array('pretty' => 'oneline', 'abbrev-commit' => true));
		$out = trim($git->getOutput());
		
		$lines = explode("\n", $out);
		
		if (count($lines) > 0) {
			$notices[] = "$packageName: " . count($lines) . " commits found between $latestVersion ($latestRef) and master";
			$notices[] = $out;
		}
	}
	
	$notices[] = "\n";
}


foreach ($notices as $notice) {
	o($notice);
}
echo "\n";

function o($txt) {
	echo "$txt\n";
}

function del_tree($dir) {
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file") && !is_link($dir)) ? del_tree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}
