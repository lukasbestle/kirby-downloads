<?php

use Kirby\Cms\App;
use Kirby\Exception\Exception;

/**
 * Kirby Downloads Plugin
 * Block to embed a list of downloads from a central storage page
 * with filters and search
 *
 * @package   Kirby Downloads Plugin
 * @author    Lukas Bestle <project-kirbydownloads@lukasbestle.com>
 * @link      https://github.com/lukasbestle/kirby-downloads
 * @copyright Lukas Bestle
 * @license   https://opensource.org/licenses/MIT
 */

// validate the Kirby version; the supported versions are
// updated manually when verified to work with the plugin
$kirbyVersion = App::version();
if (
	$kirbyVersion !== null &&
	(
		version_compare($kirbyVersion, '3.8.0-rc.1', '<') === true ||
		(
			version_compare($kirbyVersion, '4.0.0-alpha', '>=') === true &&
			version_compare($kirbyVersion, '4.1.0-rc.1', '<') === true
		) ||
		version_compare($kirbyVersion, '6.0.0-alpha', '>=') === true
	)
) {
	throw new Exception(
		'The installed version of the Kirby Downloads plugin ' .
		'is not compatible with Kirby ' . $kirbyVersion
	);
}

// autoload classes
require_once __DIR__ . '/autoload.php';

// register the plugin
App::plugin('lukasbestle/downloads', [
	'api'          => require __DIR__ . '/src/config/api.php',
	'blockModels'  => require __DIR__ . '/src/config/blockModels.php',
	'blueprints'   => require __DIR__ . '/src/config/blueprints.php',
	'options'      => require __DIR__ . '/src/config/options.php',
	'snippets'     => require __DIR__ . '/src/config/snippets.php',
	'translations' => require __DIR__ . '/src/config/translations.php',
]);
