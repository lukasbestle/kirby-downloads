<?php

use Kirby\Filesystem\F;

F::loadClasses([
	'LukasBestle\Downloads\Downloads'      => __DIR__ . '/src/classes/Downloads.php',
	'LukasBestle\Downloads\DownloadsBlock' => __DIR__ . '/src/classes/DownloadsBlock.php',
]);
