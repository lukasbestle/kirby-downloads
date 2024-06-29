<?php

use Kirby\Cms\App;
use Kirby\Toolkit\I18n;

$kirby = App::instance();

/** @var string $path */
$path     = $kirby->option('lukasbestle.downloads.path');
$fields   = $kirby->option('lukasbestle.downloads.fields');
$template = $kirby->option('lukasbestle.downloads.template');
$search   = $kirby->option('lukasbestle.downloads.search');

// if a template was defined, convert it into a query filter
$templateFilter = '';
if (is_string($template) === true && $template !== '') {
	$templateFilter = ".filterBy('template', '" . str_replace("'", '', $template) . "')";
}

// determine the fieldset for the file selection inside the block;
// start with just the manual selection
$selectionFields = [
	'files' => [
		'label'    => I18n::translate('lukasbestle.downloads.files'),
		'type'     => 'files',
		'query'    => "site.find('" . str_replace("'", '', $path) . "').files" . $templateFilter,
		'required' => true,
		'uploads'  => false,
	],
];

// also determine the fieldset for the UI options
$uiFields = [
	'uiInfo' => [
		'label' => false,
		'type'  => 'info',
		'text'  => I18n::translate('lukasbestle.downloads.interface.info'),
	],
];

if ($search === true) {
	$uiFields['uiSearch'] = [
		'label' => I18n::translate('lukasbestle.downloads.search'),
		'type'  => 'toggle',
		'width' => '1/2',
	];
}

// add the filters if filter fields were defined in the config
if (is_array($fields) === true) {
	// for the blueprint we just need the field queries (keys)
	// and labels (values), not the options
	array_walk($fields, function (array|string &$field) {
		if (is_array($field) === true) {
			$field = $field['label'];
		}
	});

	// prepend the mode selector and filter field to the selection fieldset
	$selectionFields = [
		'mode' => [
			'label'   => I18n::translate('lukasbestle.downloads.mode'),
			'type'    => 'toggles',
			'default' => 'filters',
			'options' => [
				[
					'icon'  => 'filter',
					'text'  => I18n::translate('lukasbestle.downloads.filters'),
					'value' => 'filters',
				],
				[
					'icon'  => 'list-bullet',
					'text'  => I18n::translate('lukasbestle.downloads.manual'),
					'value' => 'manual',
				],
			],
			'required' => true,
		],
		'filters' => [
			'label'  => I18n::translate('lukasbestle.downloads.filters'),
			'type'   => 'structure',
			'empty'  => I18n::translate('lukasbestle.downloads.filters.empty'),
			'fields' => [
				'field' => [
					'label'   => I18n::translate('lukasbestle.downloads.field'),
					'type'    => 'select',
					'options' => [
						'type'    => 'fixed',
						'options' => $fields,
					],
					'required' => true,
				],
				'value' => [
					'label'     => I18n::translate('lukasbestle.downloads.value'),
					'type'      => 'tags',
					'help'      => I18n::translate('lukasbestle.downloads.value.help'),
					'icon'      => false,
					'required'  => true,
					'separator' => ';;',
				],
			],
			'sortBy' => 'field asc',
			'when'   => [
				'mode' => 'filters',
			],
		],
	] + $selectionFields;

	// hide the manual selection based on the toggle value
	$selectionFields['files']['when'] = [
		'mode' => 'manual',
	];

	// append the UI filter selector to the UI fieldset
	$uiFields['uiFilters'] = [
		'label'   => I18n::translate('lukasbestle.downloads.filters'),
		'type'    => 'multiselect',
		'options' => [
			'type'    => 'fixed',
			'options' => $fields,
		],
		'separator' => ';;',
		'width' => '1/2',
	];
}

// prepend the info box to the selection fields
$selectionFields = [
	'selectionInfo' => [
		'label' => false,
		'type'  => 'info',
		'text'  => I18n::translate('lukasbestle.downloads.selection.info'),
	],
] + $selectionFields;

return [
	'name' => I18n::translate('lukasbestle.downloads.downloads'),
	'icon' => 'download',
	'tabs' => [
		'selection' => [
			'label'  => I18n::translate('lukasbestle.downloads.selection'),
			'fields' => $selectionFields,
		],
		'interface' => [
			'label'  => I18n::translate('lukasbestle.downloads.interface'),
			'fields' => $uiFields,
		],
	],
];
