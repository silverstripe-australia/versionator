<?php

include_once __DIR__ . '/vendor/autoload.php';

use Packagist\Api\Result\Package\Version;
use GitWrapper\GitWrapper;

// These are set to the ba-sis modules by default.

$managed = array(
	"nglasl/silverstripe-extensible-search",
	"silverstripe-australia/memberprofiles",
	"sheadawson/silverstripe-blocks",
	"sheadawson/silverstripe-datachange-tracker",
	"sheadawson/silverstripe-linkable",
	"sheadawson/silverstripe-timednotices",
	"silverstripe-australia/grouped-cms-menu",
	"silverstripe-australia/metadata",
	"silverstripe-australia/silverstripe-multisites",
	"silverstripe-australia/sitemap",
	"silverstripe/advancedworkflow",
	"silverstripe/listingpage",
	"silverstripe/queuedjobs",
	"silverstripe/secureassets",
	"silverstripe/taxonomy",
	"silverstripe/userforms",
	"silverstripe/versionedfiles",
	"unclecheese/betterbuttons",
	"undefinedoffset/sortablegridfield",
	"sheadawson/quickaddnew",
	"silverstripe-australia/gridfieldextensions",
	"silverstripe/display-logic",
	"silverstripe/multivaluefield",
	"silverstripe/timepickerfield",
	"silverstripe/restrictedobjects",
	"silverstripe/frontend-dashboards",
	"silverstripe/microblog",
	"silverstripe-australia/ba-sis",
);

echo "\n";

$options = getopt('f:w:');

if (!isset($options['f'])) {
	exit("Specify a composer file to check.\n");
}

$file = realpath($options['f']);
if (!file_exists($file)) {
	o("Could not read $file\n");
	exit();
}

$workspace = isset($options['w']) ? $options['w'] : __DIR__ . '/workspace';

$composer = json_decode(file_get_contents($file));

if (!$composer) {
	o("Could not parse composer details\n");
	exit();
}

if (!isset($composer->require)) {
	o("No requirements found, enjoy!\n");
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

	o("Retrieved package " . $package->getName() . "\n");

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


	if (version_compare($latestVersion, $requiredVersion) > 0 && $latestVersion) {
		$notices[] = "\033[31m✘ $packageName has an updated, fixed tag version available $latestVersion (currently $requiredVersion)\033[0m";
	} else if (!$latestVersion) {
		$notices[] = "\033[31m✘ $packageName has no proper version available\033[0m";
	} else {
		$notices[] = "\033[32m✔ $packageName looks to be using the latest available version\033[0m";
	}

	// now check out the project
	if ($repo && $latestRef) {
		$path = $workspace . DIRECTORY_SEPARATOR . basename($package_name);
		if (!file_exists($path)) {
			$git = $wrapper->cloneRepository($source->getUrl(), $workspace . DIRECTORY_SEPARATOR . basename($package_name));
		} else {
			$git = $wrapper->workingCopy($path);
			$git->checkout('master', array('f' => true));
			$git->pull();
			$git->fetch(array('tags' => true));
		}

		$git->clearOutput();
		// checkout the 'latest' ref as far as packagist knows about, then check to see
		// whether the repository has changes between it and the current master HEAD
		// $git->diff($latestRef, array('name-status' => true));
		$git->log($latestRef . '..master', array('pretty' => 'oneline', 'abbrev-commit' => true));
		$out = trim($git->getOutput());
		
		if (strlen($out)) {
			$lines = explode("\n", $out);
			if (count($lines) > 0) {
				$notices[] = "\033[31m✘ $packageName: " . count($lines) . " commits found between $latestVersion ($latestRef) and master\033[0m";
				$notices[] = "$out\n";
			}
		} else {
			$notices[] = "\033[32m✔ $packageName @ $latestVersion appears to be up-to-date\033[0m\n";
		}
	}
}

foreach ($notices as $notice) {
	o($notice);
}

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
