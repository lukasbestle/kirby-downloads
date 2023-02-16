<?php

namespace LukasBestle\Downloads;

use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Exception\Exception;
use Kirby\Toolkit\A;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/**
 * Downloads
 * Collection for the download files with filters and search
 *
 * @package   Kirby Downloads Plugin
 * @author    Lukas Bestle <project-kirbydownloads@lukasbestle.com>
 * @link      https://github.com/lukasbestle/kirby-downloads
 * @copyright Lukas Bestle
 * @license   https://opensource.org/licenses/MIT
 */
class Downloads
{
	/**
	 * Cache of block downloads
	 *
	 * @var array<string, \Kirby\Cms\Files>
	 */
	protected array $cache = [];

	/**
	 * List of available downloads
	 */
	protected Files $downloads;

	/**
	 * Singleton class instance
	 */
	protected static self|null $instance;

	/**
	 * Kirby App instance
	 */
	protected App $kirby;

	/**
	 * Class constructor
	 *
	 * @param \Kirby\Cms\App|null $kirby Kirby App instance to use (optional)
	 */
	public function __construct(?App $kirby = null)
	{
		/** @psalm-suppress PossiblyNullPropertyAssignmentValue */
		$this->kirby = $kirby ?? App::instance();

		$path     = $this->kirby->option('lukasbestle.downloads.path');
		$template = $this->kirby->option('lukasbestle.downloads.template');

		$downloads = $this->kirby->site()->find($path)?->files();

		if ($template !== null) {
			$downloads = $downloads?->filterBy('template', $template);
		}

		if ($downloads === null) {
			throw new Exception('Could not find downloads');
		}

		$this->downloads = $downloads;
	}

	/**
	 * Returns all downloads or a block's downloads
	 */
	public function downloads(?DownloadsBlock $block = null): Files
	{
		if ($block === null) {
			return $this->downloads;
		}

		if (isset($this->cache[$block->id()]) === true) {
			return $this->cache[$block->id()];
		}

		// filter mode
		if ($block->content()->mode()->value() === 'filters') {
			$fields = $this->kirby->option('lukasbestle.downloads.fields');

			$downloads = $this->downloads;

			// chain all filters to get results with `AND` logic
			foreach ($block->content()->filters()->toStructure() as $filter) {
				$field = $filter->field()->value();
				$values = $filter->value()->split(';;');

				// the block's fields need to be a subset of the configured fields
				if (isset($fields[$field]) !== true) {
					continue;
				}

				$downloads = static::filterByQuery($downloads, $field, $values);
			}

			return $this->cache[$block->id()] = $downloads;
		}

		// manual mode
		return $this->cache[$block->id()] = $block->content()->files()->toFiles();
	}

	/**
	 * Returns the UI filters for the provided block instance
	 *
	 * @return array<string, string>
	 */
	public function fields(DownloadsBlock $block): array
	{
		$fields = $this->kirby->option('lukasbestle.downloads.fields');

		$result = [];
		foreach ($block->content()->uiFilters()->split(';;') as $field) {
			/** @var string $field */

			// the block's fields need to be a subset of the configured fields
			if (isset($fields[$field]) !== true) {
				continue;
			}

			/** @var string $label */
			$label = I18n::translate($fields[$field], $fields[$field]);
			$result[$field] = $label;
		}

		return $result;
	}

	/**
	 * Returns the singleton class instance
	 *
	 * @param \Kirby\Cms\App|null $kirby Kirby App instance to use (optional)
	 */
	public static function instance(?App $kirby = null): self
	{
		if (
			isset(self::$instance) === true &&
			($kirby === null || self::$instance->kirby() === $kirby)
		) {
			return self::$instance;
		}

		return self::$instance = new self($kirby);
	}

	/**
	 * Returns the Kirby App instance
	 */
	public function kirby(): App
	{
		return $this->kirby;
	}

	/**
	 * Returns the UI filters and their possible options
	 * for the provided block instance
	 *
	 * @return array<string, array{label: string, options: string[]}>
	 */
	public function options(DownloadsBlock $block): array
	{
		$fields = $this->fields($block);

		// convert the fields into the nested return value structure
		$result = [];
		foreach ($fields as $query => $label) {
			$result[$query] = ['label' => $label, 'options' => []];
		}

		// collect all possible values for each of the fields
		foreach ($this->downloads($block) as $download) {
			foreach ($fields as $query => $label) {
				/** @var list<string|null> $values */
				$values = A::wrap(static::query($download, $query));

				foreach ($values as $value) {
					if ($value === null) {
						continue; // @codeCoverageIgnore
					}

					if (in_array($value, $result[$query]['options']) !== true) {
						$result[$query]['options'][] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the block's downloads filtered by UI filters and UI search
	 *
	 * @param array<string, string[]> $filters
	 */
	public function results(DownloadsBlock $block, array $filters, string $search): Files
	{
		// first filter the results by the block settings
		$downloads = $this->downloads($block);

		// then filter by the UI filters
		$fields = $this->fields($block);
		foreach ($filters as $key => $values) {
			// only allow UI filtering by the configured fields and queries
			if (isset($fields[$key]) !== true) {
				continue;
			}

			$downloads = static::filterByQuery($downloads, $key, $values);
		}

		// filter by the search values if a query was passed and if enabled
		if ($search && $block->content()->uiSearch()->toBool() === true) {
			$downloads = $downloads->search($search, [
				'fields' => ['filename', 'title']
			]);
		}

		/** @var \Kirby\Cms\Files $downloads */
		return $downloads;
	}

	/**
	 * Filters a collection of downloads by querying a value
	 * with a query string without prefix and checking if the
	 * result is contained in a list of allowed values
	 */
	protected static function filterByQuery(Files $downloads, string $query, array $values): Files
	{
		return $downloads->filter(function (File $download) use ($query, $values) {
			$actualValues = A::wrap(static::query($download, $query));

			return count(array_intersect($values, array_filter($actualValues))) > 0;
		});
	}

	/**
	 * Runs a query string without prefix on a file object
	 */
	protected static function query(File $download, string $query): string|array|null
	{
		$value = Str::query('download.' . $query, ['download' => $download]);

		if ($value instanceof Field) {
			return $value->value();
		}

		return $value;
	}
}
