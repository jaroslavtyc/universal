<?php
$directoriesToLoad = array(__DIR__ . '/../extensions/php');
foreach ($directoriesToLoad as $directoryToLoad) {
	foreach (scandir($directoryToLoad) as $extensionFile) {
		if (preg_match('~\.php$~', $extensionFile)) {
			require_once($directoryToLoad . '/' . $extensionFile);
		}
	}
}