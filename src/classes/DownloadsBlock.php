<?php

namespace LukasBestle\Downloads;

use Kirby\Cms\App;
use Kirby\Cms\Block;
use Kirby\Cms\Files;
use Kirby\Exception\Exception;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

/**
 * DownloadsBlock
 * Model class for the `downloads` block
 *
 * @package   Kirby Downloads Plugin
 * @author    Lukas Bestle <project-kirbydownloads@lukasbestle.com>
 * @link      https://github.com/lukasbestle/kirby-downloads
 * @copyright Lukas Bestle
 * @license   https://opensource.org/licenses/MIT
 */
class DownloadsBlock extends Block
{
	/**
	 * Returns the URL to the UI filtering/searching endpoint
	 * for progressive enhancement
	 */
	public function apiEndpoint(): string
	{
		// TODO: Remove when support for Kirby <3.9.2 is dropped
		// @codeCoverageIgnoreStart
		$kirbyVersion = App::version();
		if (
			$kirbyVersion !== null &&
			version_compare($kirbyVersion, '3.9.2-rc.1', '<') === true
		) {
			throw new Exception('Using the API endpoint for the Downloads plugin requires Kirby 3.9.2+');
		}
		// @codeCoverageIgnoreEnd

		$parent = $this->parent()->id();
		// TODO: Remove psalm suppression comment when support for Kirby <3.9.2 is dropped
		/** @psalm-suppress all */
		$field  = $this->field()?->key();

		if (!$parent || !$field) {
			throw new Exception('Parent model or parent field is not known');
		}

		return $this->kirby()->url('api') . '/downloads/' .
			str_replace('/', '+', $parent) . '/' .
			$field . '/' .
			$this->id();
	}

	/**
	 * Returns the UI filters and their possible options:
	 * the first level has the form name as key;
	 * the value contains the label and a list of options
	 * with their name and their active state
	 *
	 * @return array<string, array{label: string, options: array<string, bool>}>
	 */
	public function filters(): array
	{
		$options = Downloads::instance()->options($this);

		// convert the data into more useful HTML form values
		$filters = [];
		foreach ($options as $name => $data) {
			$name = $this->htmlId() . '_filter_' . Str::slug($name);

			$options = [];
			$values  = A::wrap($this->kirby()->request()->get($name));
			foreach ($data['options'] as $option) {
				$options[$option] = in_array($option, $values) === true;
			}

			$filters[$name] = [
				'label'   => $data['label'],
				'options' => $options,
			];
		}

		return $filters;
	}

	/**
	 * Returns whether at least one filter or the search
	 * have user input in them
	 *
	 * @return bool
	 */
	public function hasActiveForm(): bool
	{
		$htmlId = $this->htmlId();

		foreach ($this->kirby()->request()->data() as $key => $value) {
			if (Str::startsWith($key, $htmlId) === true) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether at least one UI filter was enabled
	 */
	public function hasFilters(): bool
	{
		return $this->content()->uiFilters()->isNotEmpty();
	}

	/**
	 * Returns whether UI search was enabled
	 */
	public function hasSearch(): bool
	{
		return $this->content()->uiSearch()->toBool();
	}

	/**
	 * Returns the block ID used for permalinks
	 */
	public function htmlId(): string
	{
		return 'downloads-' . $this->id();
	}

	/**
	 * Returns the block's downloads matching the current UI filters
	 */
	public function results(): Files
	{
		$downloads = Downloads::instance();
		$request   = $this->kirby()->request();
		$prefix    = $this->htmlId();

		$filters = [];
		foreach (array_keys($downloads->fields($this)) as $key) {
			$values = $request->get($prefix . '_filter_' . Str::slug($key));

			if ($values !== null) {
				$filters[$key] = A::wrap($values);
			}
		}

		return $downloads->results(
			$this,
			$filters,
			$request->get($prefix . '_search', '')
		);
	}

	/**
	 * Returns the currently searched value from the request data
	 */
	public function searchValue(): string
	{
		return $this->kirby()->request()->get($this->htmlId() . '_search', '');
	}
}
