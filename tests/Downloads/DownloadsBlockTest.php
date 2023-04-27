<?php

namespace LukasBestle\Downloads;

use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Exception\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass LukasBestle\Downloads\DownloadsBlock
 */
class DownloadsBlockTest extends TestCase
{
	protected App $kirby;

	public function setUp(): void
	{
		$this->kirby = new App([
			'options' => [
				'lukasbestle.downloads' => [
					'path' => 'downloads',
					'fields' => [
						// fixed string
						'content.tags.split(",")' => 'Tag',

						// with at least the default language and automatic fallback
						'content.type' => [
							'en' => 'Type',
						],
						'mime' => [
							'en' => 'MIME type',
						],

						// with all languages
						'content.product' => [
							'en' => 'Product',
							'de' => 'Produkt',
						],
						'extension' => [
							'en' => 'File type',
							'de' => 'Dateityp',
						],
					],
					'template' => 'download',
				],
			],
			'site' => [
				'children' => [
					[
						'slug'     => 'test1',
						'children' => [
							[
								'slug' => 'test2',
							],
						],
					],
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
			'urls' => [
				'index' => 'https://example.com',
			],
		]);

		$this->kirby->setCurrentTranslation('de');

		Downloads::instance($this->kirby);
	}

	/**
	 * @covers ::apiEndpoint
	 */
	public function testApiEndpoint()
	{
		// TODO: Remove when support for Kirby <3.9.2 is dropped
		if (version_compare(App::version(), '3.9.2-rc.1', '<') === true) {
			$this->markTestSkipped('Kirby version is older than 3.9.2.');
		}

		$block = new DownloadsBlock([
			'content' => [],
			'id'      => '12345678-90ab-cdef-1234-567890abcdef',
			'parent'  => $parent = $this->kirby->page('test1/test2'),
			'field'   => new Field($parent, 'text', 'abcde'),
			'type'    => 'downloads',
		]);

		$this->assertSame(
			'https://example.com/api/downloads/test1+test2/text/12345678-90ab-cdef-1234-567890abcdef',
			$block->apiEndpoint()
		);

		$block = new DownloadsBlock([
			'content' => [],
			'id'      => '12345678-90ab-cdef-1234-567890abcdef',
			'parent'  => $parent = $this->kirby->page('test1/test2'),
			'type'    => 'downloads',
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Parent model or parent field is not known');
		$block->apiEndpoint();
	}

	/**
	 * @coversNothing
	 */
	public function testFields()
	{
		$block = new DownloadsBlock([
			'content' => [
				'mode'    => $mode = 'manual',
				'filters' => $filters = [
					[
						'field' => 'content.type',
						'value' => 'manual',
					],
				],
				'files'     => $files = '- downloads/photo.jpg',
				'uiFilters' => $uiFilters = 'extension;; content.type',
				'uiSearch'  => $uiSearch = 'false',

			],
			'id'       => $id = '12345678-90ab-cdef-1234-567890abcdef',
			'isHidden' => false,
			'parent'   => $parent = $this->kirby->page('test1/test2'),
			'field'    => new Field($parent, 'text', 'abcde'),
			'type'     => 'downloads',
		]);

		$this->assertSame($mode, $block->content()->mode()->value());
		$this->assertSame($filters, $block->content()->filters()->value());
		$this->assertSame($files, $block->content()->files()->value());
		$this->assertSame($uiFilters, $block->content()->uiFilters()->value());
		$this->assertSame($uiSearch, $block->content()->uiSearch()->value());

		$this->assertSame($id, $block->id());
	}

	/**
	 * @covers ::filters
	 */
	public function testFilters()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => 'pdf',
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-tags-split' => ['frontal', 'german'],
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-type' => 'manual',
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-product' => 'black',
				]
			]
		]);

		$this->kirby->setCurrentTranslation('de');

		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.tags.split(",")',
						'value' => 'white'
					],
				],
				'uiFilters' => 'extension;; content.tags.split(",");; content.type;; content.product;; this.is.invalid',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertSame([
			'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => [
				'label' => 'Dateityp',
				'options' => [
					'jpg' => false,
					'pdf' => true,
				],
			],
			'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-tags-split' => [
				'label' => 'Tag',
				'options' => [
					'frontal' => true,
					'german'  => true,
					'white'   => false,
				],
			],
			'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-type' => [
				'label' => 'Type',
				'options' => [
					'manual' => true,
					'photo'  => false,
				],
			],
			'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_content-product' => [
				'label' => 'Produkt',
				'options' => [
					'white' => false,
				],
			],
		], $block->filters());
	}

	/**
	 * @covers ::hasActiveForm
	 */
	public function testHasActiveForm_No()
	{
		$block = new DownloadsBlock([
			'content' => [
				'uiFilters' => 'extension;; content.type'
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertFalse($block->hasActiveForm());
	}

	/**
	 * @covers ::hasActiveForm
	 */
	public function testHasActiveForm_YesFilter()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => 'pdf',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'uiFilters' => 'extension;; content.type'
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertTrue($block->hasActiveForm());
	}

	/**
	 * @covers ::hasActiveForm
	 */
	public function testHasActiveForm_YesSearch()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_search' => 'nice',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'uiFilters' => 'extension;; content.type'
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertTrue($block->hasActiveForm());
	}

	/**
	 * @covers ::hasFilters
	 */
	public function testHasFilters_No()
	{
		$block = new DownloadsBlock([
			'content' => [
				'uiFilters' => '',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertFalse($block->hasFilters());
	}

	/**
	 * @covers ::hasFilters
	 */
	public function testHasFilters_Yes()
	{
		$block = new DownloadsBlock([
			'content' => [
				'uiFilters' => 'extension;; content.type',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertTrue($block->hasFilters());
	}

	/**
	 * @covers ::hasSearch
	 */
	public function testHasSearch_No()
	{
		$block = new DownloadsBlock([
			'content' => [
				'uiSearch' => '',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertFalse($block->hasSearch());

		$block = new DownloadsBlock([
			'content' => [
				'uiSearch' => 'false',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertFalse($block->hasSearch());
	}

	/**
	 * @covers ::hasSearch
	 */
	public function testHasSearch_Yes()
	{
		$block = new DownloadsBlock([
			'content' => [
				'uiSearch' => 'true',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertTrue($block->hasSearch());
	}

	/**
	 * @covers ::htmlId
	 */
	public function testHtmlId()
	{
		$block = new DownloadsBlock([
			'content' => [],
			'id'      => '12345678-90ab-cdef-1234-567890abcdef',
			'parent'  => $parent = $this->kirby->page('test1/test2'),
			'field'   => new Field($parent, 'text', 'abcde'),
			'type'    => 'downloads',
		]);

		$this->assertSame('downloads-12345678-90ab-cdef-1234-567890abcdef', $block->htmlId());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_Filters()
	{
		// simple filter
		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.type',
						'value' => 'manual',
					],
				],
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame(['downloads/manual1.pdf', 'downloads/manual2.pdf'], $downloads->keys());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_Manual()
	{
		// manual selection
		$block = new DownloadsBlock([
			'content' => [
				'mode'  => 'manual',
				'files' => "- downloads/photo.jpg\n- downloads/manual2.pdf",
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $this->kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame(['downloads/photo.jpg', 'downloads/manual2.pdf'], $downloads->keys());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_UIFilters1()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => 'pdf',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.tags.split(",")',
						'value' => 'white',
					],
				],
				'uiFilters' => 'extension;; content.type'
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame(['downloads/manual1.pdf'], $downloads->keys());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_UIFilters2()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => ['jpg', 'png'],
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.tags.split(",")',
						'value' => 'white',
					],
				],
				'uiFilters' => 'extension;; content.type'
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame(['downloads/photo.jpg'], $downloads->keys());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_UISearch()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_search' => 'nice',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.tags.split(",")',
						'value' => 'white',
					],
				],
				'uiFilters' => 'extension;; content.type',
				'uiSearch'  => 'true',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame(['downloads/photo.jpg'], $downloads->keys());
	}

	/**
	 * @covers ::results
	 */
	public function testResults_UISearchAndFilters()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_filter_extension' => ['pdf'],
					'downloads-12345678-90ab-cdef-1234-567890abcdef_search' => 'nice',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [
				'mode'    => 'filters',
				'filters' => [
					[
						'field' => 'content.tags.split(",")',
						'value' => 'white',
					],
				],
				'uiFilters' => 'extension;; content.type',
				'uiSearch'  => 'true',
			],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$downloads = $block->results();
		$this->assertSame([], $downloads->keys());
	}

	/**
	 * @covers ::searchValue
	 */
	public function testSearchValue()
	{
		$kirby = $this->kirby->clone([
			'request' => [
				'query' => [
					'downloads-12345678-90ab-cdef-1234-567890abcdef_search' => 'this is a test',
				]
			]
		]);

		$block = new DownloadsBlock([
			'content' => [],
			'id'     => '12345678-90ab-cdef-1234-567890abcdef',
			'parent' => $parent = $kirby->page('test1/test2'),
			'field'  => new Field($parent, 'text', 'abcde'),
			'type'   => 'downloads',
		]);

		$this->assertSame('this is a test', $block->searchValue());
	}
}
