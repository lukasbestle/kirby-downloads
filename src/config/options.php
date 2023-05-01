<?php

return [
	// Path to the page where all downloads are stored;
	// REQUIRED
	'path' => 'downloads',

	// Associative array of fields that can be used as filters in
	// the block with field name and label (optionally localized);
	// defaults to none (only manual selection of files)
	'fields' => null,

	// Number of downloads to list per pagination page;
	// can be set to `null` to disable pagination
	'paginate' => 15,

	// File template of the download files inside the `path`;
	// defaults to any template
	'template' => null,
];
