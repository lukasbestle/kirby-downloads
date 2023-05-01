<?php

use Kirby\Cms\File;
use Kirby\Http\Response;

return [
	'routes' => [
		[
			/**
			 * Returns results for a filter/search query from
			 * a downloads block
			 */
			'pattern' => 'downloads/(:any)/(:any)/(:any)',
			'method'  => 'GET',
			'auth'    => false,
			'action'  => function (string $page, string $field, string $id): array|Response {
				/** @psalm-scope-this Kirby\Http\Route */
				$block = $this->kirby()
					->site()
					->find(str_replace('+', '/', $page))
					?->content()
					->get($field)
					?->toBlocks()
					->get($id);

				if ($block !== null) {
					$results = $block->results('page');

					$resultsArray = $results->toArray(function (File $download) {
						return [
							'filename' => $download->filename(),
							'title'    => $download->content()->title()->value(),
							'size'     => $download->size(),
							'niceSize' => $download->niceSize(),
							'url'      => $download->url(),
						];
					});

					return [
						'results' => $resultsArray,
						'pagination' => $results->pagination()?->toArray()
					];
				}

				return new Response('Not found', 'text/plain', 404);
			}
		],
	],
];
