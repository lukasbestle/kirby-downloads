<?php

namespace LukasBestle\Downloads;

use Kirby\Cms\App;
use Kirby\Exception\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass LukasBestle\Downloads\Downloads
 */
class DownloadsTest extends TestCase
{
	protected Downloads $downloads;
	protected App $kirby;

	public function setUp(): void
	{
		$this->kirby = new App([
			'roots' => [
				'index' => '/dev/null'
			],
			'options' => [
				'lukasbestle.downloads' => [
					'path' => 'downloads',
					'fields' => [
						// fixed string
						'content.tags.split(",")' => 'Tag',
						'this.is.invalid' => 'and does not resolve to anything',

						// with at least the default language and automatic fallback
						'content.type' => [
							'label' => [
								'en' => 'Type',
							],
							'options' => [
								'manual' => 'Manual',
								'photo' => [
									'en' => 'Photo',
								],
								'invalid' => 'is not actually used',
							],
						],
						'mime' => [
							'label' => [
								'en' => 'MIME type',
							],
						],

						// with all languages
						'content.product' => [
							'label' => [
								'en' => 'Product',
								'de' => 'Produkt',
							],
						],
						'extension' => [
							'label' => [
								'en' => 'File type',
								'de' => 'Dateityp',
							],
							'options' => [
								'jpg' => [
									'en' => 'JPEG',
									'de' => 'Jäipäg',
								],
								'pdf' => [
									'en' => 'PDF',
									'de' => 'PeDeEff',
								],
							],
						],
					],
					'template' => 'download',
				],
			],
			'site' => [
				'children' => [
					[
						'slug'  => 'downloads',
						'files' => [
							[
								'filename' => 'photo.jpg',
								'content'  => [
									'product'  => '',
									'title'    => 'Nice marketing photo',
									'type'     => 'photo',
									'tags'     => 'frontal, white',
									'template' => 'download',
								],
							],
							[
								'filename' => 'manual1.pdf',
								'content'  => [
									'product'  => 'white',
									'title'    => 'Manual for the white product',
									'type'     => 'manual',
									'tags'     => 'german, white',
									'template' => 'download',
								],
							],
							[
								'filename' => 'manual2.pdf',
								'content'  => [
									'product'  => 'black',
									'title'    => 'Manual for the black product',
									'type'     => 'manual',
									'tags'     => 'german, black',
									'template' => 'download',
								],
							],
							[
								'filename' => 'cover.webp',
							],
						],
					],
				],
			],
		]);

		$this->kirby->setCurrentTranslation('de');

		$this->downloads = new Downloads($this->kirby);
	}

	/**
	 * @covers ::__construct
	 * @covers ::downloads
	 */
	public function testConstruct()
	{
		$downloads = new Downloads($this->kirby);
		$files = $downloads->downloads();
		$this->assertCount(3, $files);

		// without template option
		$kirby = $this->kirby->clone([
			'options' => [
				'lukasbestle.downloads' => [
					'template' => null,
				],
			],
		]);

		$downloads = new Downloads($kirby);
		$files = $downloads->downloads();
		$this->assertCount(4, $files);

		// with invalid template option
		$kirby = $this->kirby->clone([
			'options' => [
				'lukasbestle.downloads' => [
					'template' => 'does-not-exist',
				],
			],
		]);

		$downloads = new Downloads($kirby);
		$files = $downloads->downloads();
		$this->assertCount(0, $files);
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct_Exception1()
	{
		// with missing path
		$kirby = $this->kirby->clone([
			'options' => [
				'lukasbestle.downloads' => [
					'path' => null,
				],
			],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Downloads path is not defined');
		new Downloads($kirby);
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct_Exception2()
	{
		// with invalid path
		$kirby = $this->kirby->clone([
			'options' => [
				'lukasbestle.downloads' => [
					'path' => 'does-not-exist',
				],
			],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Could not find downloads');
		new Downloads($kirby);
	}

	/**
	 * @covers ::downloads
	 * @covers ::filterByQuery
	 * @covers ::query
	 */
	public function testDownloads_Filters()
	{
		// all files
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual1.pdf', 'downloads/manual2.pdf'], $downloads->keys());

		// result from cache
		$this->assertSame($downloads, $this->downloads->downloads($block));

		// with a simple filter
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.type',
					'value' => 'manual',
				],
			],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/manual1.pdf', 'downloads/manual2.pdf'], $downloads->keys());

		// with AND logic
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.type',
					'value' => 'manual',
				],
				[
					'field' => 'content.tags.split(",")',
					'value' => 'black',
				],
			],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/manual2.pdf'], $downloads->keys());

		// invalid filters are ignored
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.type',
					'value' => 'manual',
				],
				[
					'field' => 'delete',
					'value' => 'HAHAHAHA',
				],
				[
					'field' => 'content.title',
					'value' => 'Nice marketing photo',
				],
			],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/manual1.pdf', 'downloads/manual2.pdf'], $downloads->keys());

		$this->assertCount(4, $this->kirby->site()->find('downloads')->files());

		// with OR logic
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.tags.split(",")',
					'value' => 'black;; frontal',
				],
			],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $downloads->keys());

		// with AND and OR logic
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.tags.split(",")',
					'value' => 'black;; frontal',
				],
				[
					'field' => 'extension',
					'value' => 'pdf',
				],
			],
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/manual2.pdf'], $downloads->keys());
	}

	/**
	 * @covers ::downloads
	 */
	public function testDownloads_Manual()
	{
		$block = $this->block([
			'mode'  => 'manual',
			'files' => "- downloads/photo.jpg\n- downloads/manual2.pdf",
		]);

		$downloads = $this->downloads->downloads($block);
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $downloads->keys());

		// result from cache
		$this->assertSame($downloads, $this->downloads->downloads($block));
	}

	/**
	 * @covers ::fields
	 */
	public function testFields()
	{
		$block = $this->block([
			'uiFilters' => 'extension;; content.tags.split(",");; content.type;; this.is.notdefined'
		]);

		$this->assertSame([
			'extension' => [
				'label' => 'Dateityp',
				'options' => [
					'jpg' => 'Jäipäg',
					'pdf' => 'PeDeEff',
				],
			],
			'content.tags.split(",")' => [
				'label' => 'Tag',
				'options' => [],
			],
			'content.type' => [
				'label' => 'Type',
				'options' => [
					'manual' => 'Manual',
					'photo' => 'Photo',
					'invalid' => 'is not actually used',
				],
			],
		], $this->downloads->fields($block));
	}

	/**
	 * @backupStaticAttributes enabled
	 * @covers ::instance
	 * @covers ::__construct
	 * @covers ::kirby
	 */
	public function testInstance()
	{
		$property = new ReflectionProperty('LukasBestle\Downloads\Downloads', 'instance');
		$property->setAccessible(true);
		$property->setValue(null, null);

		$kirby = $this->kirby->clone();

		$this->assertSame($this->kirby, $this->downloads->kirby());
		$this->assertNotSame($kirby, $this->downloads->kirby());

		$downloads = Downloads::instance($kirby);
		$this->assertSame($kirby, $downloads->kirby());

		$downloads2 = Downloads::instance();
		$this->assertSame($downloads, $downloads2);

		$downloads3 = Downloads::instance($this->kirby);
		$this->assertNotSame($downloads, $downloads3);
		$this->assertSame($this->kirby, $downloads3->kirby());

		$downloads4 = new Downloads($kirby);
		$this->assertSame($kirby, $downloads4->kirby());
	}

	/**
	 * @covers ::options
	 * @covers ::query
	 */
	public function testOptions()
	{
		$block = $this->block([
			'mode'    => 'filters',
			'filters' => [
				[
					'field' => 'content.tags.split(",")',
					'value' => 'white',
				],
			],
			'uiFilters' => 'extension;; content.tags.split(",");; content.type;; content.product;; this.is.invalid;; this.is.notdefined',
		]);

		$this->assertSame([
			'extension' => [
				'label' => 'Dateityp',
				'options' => ['jpg' => 'Jäipäg', 'pdf' => 'PeDeEff'],
			],
			'content.tags.split(",")' => [
				'label' => 'Tag',
				'options' => ['frontal' => 'frontal', 'german' => 'german', 'white' => 'white'],
			],
			'content.type' => [
				'label' => 'Type',
				'options' => ['manual' => 'Manual', 'photo' => 'Photo'],
			],
			'content.product' => [
				'label' => 'Produkt',
				'options' => ['white' => 'white'],
			],
			'this.is.invalid' => [
				'label' => 'and does not resolve to anything',
				'options' => [],
			],
		], $this->downloads->options($block));
	}

	/**
	 * @covers ::results
	 * @covers ::filterByQuery
	 * @covers ::query
	 */
	public function testResults()
	{
		$block = $this->block([
			'mode'      => 'filters',
			'filters'   => [
				[
					'field' => 'content.tags.split(",")',
					'value' => 'black;; frontal',
				],
			],
			'uiFilters' => 'content.type;; content.tags.split(",")',
			'uiSearch'  => 'true',
		]);

		$blockWithoutUI = $this->block([
			'mode'      => 'filters',
			'filters'   => [
				[
					'field' => 'content.tags.split(",")',
					'value' => 'black;; frontal',
				],
			],
			'uiFilters' => '',
			'uiSearch'  => 'false',
		]);

		// all files of the block
		$results = $this->downloads->results($block, [], '');
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $results->keys());

		// with a simple filter
		$results = $this->downloads->results($block, [
			'content.type' => ['manual'],
		], '');
		$this->assertSame(['downloads/manual2.pdf'], $results->keys());

		// with AND logic
		$results = $this->downloads->results($block, [
			'content.type'            => ['manual'],
			'content.tags.split(",")' => ['black'],
		], '');
		$this->assertSame(['downloads/manual2.pdf'], $results->keys());

		$results = $this->downloads->results($block, [
			'content.type'            => ['manual'],
			'content.tags.split(",")' => ['frontal'],
		], '');
		$this->assertSame([], $results->keys());

		// invalid filters are ignored
		$results = $this->downloads->results($block, [
			'content.type'  => ['manual'],
			'delete'        => ['HAHAHAHA'],
			'content.title' => ['Nice marketing photo'],
			'extension'     => ['jpg'],
		], '');
		$this->assertSame(['downloads/manual2.pdf'], $results->keys());

		$this->assertCount(4, $this->kirby->site()->find('downloads')->files());

		// with OR logic
		$results = $this->downloads->results($block, [
			'content.type' => ['photo', 'datasheet'],
		], '');
		$this->assertSame(['downloads/photo.jpg'], $results->keys());

		// with AND and OR logic
		$results = $this->downloads->results($block, [
			'content.type'            => ['photo', 'datasheet'],
			'content.tags.split(",")' => ['frontal'],
		], '');
		$this->assertSame(['downloads/photo.jpg'], $results->keys());

		$results = $this->downloads->results($block, [
			'content.type'            => ['photo', 'datasheet'],
			'content.tags.split(",")' => ['german'],
		], '');
		$this->assertSame([], $results->keys());

		// block without UI cannot be filtered
		$results = $this->downloads->results($blockWithoutUI, [
			'content.type' => ['manual'],
		], '');
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $results->keys());

		// search
		$results = $this->downloads->results($block, [], 'nice');
		$this->assertSame(['downloads/photo.jpg'], $results->keys());

		// search with multiple terms
		$results = $this->downloads->results($block, [], 'marketing nice');
		$this->assertSame(['downloads/photo.jpg'], $results->keys());

		$results = $this->downloads->results($block, [], 'nice manual');
		$this->assertSame(['downloads/manual2.pdf', 'downloads/photo.jpg'], $results->keys());

		// search and filters
		$results = $this->downloads->results($block, [
			'content.type' => ['photo', 'manual'],
		], 'nice');
		$this->assertSame(['downloads/photo.jpg'], $results->keys());

		$results = $this->downloads->results($block, [
			'content.type' => ['manual', 'datasheet'],
		], 'nice');
		$this->assertSame([], $results->keys());

		// block without UI cannot be searched
		$results = $this->downloads->results($blockWithoutUI, [], 'nice');
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $results->keys());
	}

	/**
	 * Returns a dummy instance of the `DownloadsBlock` class
	 */
	protected function block(array $content)
	{
		return new DownloadsBlock([
			'content' => $content,
			'type'    => 'downloads',
		]);
	}
}
