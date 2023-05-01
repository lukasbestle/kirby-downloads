<?php

use Kirby\Toolkit\Escape;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/** @var \LukasBestle\Downloads\DownloadsBlock $block */
$results = $block->results();
?>
<figure class="downloads" id="<?= Escape::attr($block->htmlId()) ?>">
	<?php if ($block->hasFilters() === true || $block->hasSearch() === true): ?>
	<form action="<?= Escape::attr($block->parent()->url() . '#' . $block->htmlId()) ?>" method="GET">
		<?php if ($block->hasFilters() === true): ?>
		<?php foreach ($block->filters() as $name => $data): ?>
		<fieldset>
			<legend><?= Escape::html($data['label']) ?></legend>

			<?php foreach ($data['options'] as $option => $active): ?>
			<input
				type="checkbox"
				id="<?= Escape::attr($name . '_' . Str::slug($option)) ?>"
				name="<?= Escape::attr($name) ?>[]"
				value="<?= Escape::attr($option) ?>"
				<?php if ($active === true): ?>checked<?php endif ?>
			>
			<label for="<?= Escape::attr($name . '_' . Str::slug($option)) ?>"><?= Escape::html($option) ?></label>
			<?php endforeach ?>
		</fieldset>
		<?php endforeach ?>
		<?php endif ?>

		<?php if ($block->hasSearch() === true): ?>
		<label for="<?= Escape::attr($block->htmlId() . '_search') ?>">
			<?= Escape::html(I18n::translate('lukasbestle.downloads.search')) ?>
		</label>
		<input
			type="text"
			id="<?= Escape::attr($block->htmlId() . '_search') ?>"
			name="<?= Escape::attr($block->htmlId() . '_search') ?>"
			value="<?= Escape::attr($block->searchValue()) ?>"
		>
		<?php endif ?>

		<input type="submit" value="<?= I18n::translate('lukasbestle.downloads.filter') ?>">
	</form>
	<?php endif ?>

	<ul>
		<?php foreach ($results as $file): ?>
		<li>
			<a href="<?= Escape::attr($file->url()) ?>" download>
				<?= Escape::html($file->title()->or($file->filename())) ?>
				<small>
					<?= $file->title()->value() ? Escape::html($file->filename()) . ' · ' : '' ?>
					<?= Escape::html($file->niceSize()) ?>
				</small>
			</a>
		</li>
		<?php endforeach ?>
	</ul>

	<?php if ($results->pagination()->hasPages()): ?>
	<nav class="pagination">
		<?php if ($results->pagination()->hasPrevPage()): ?>
		<a class="prev" href="<?= $results->pagination()->prevPageURL() ?>">
			‹ <?= I18n::translate('lukasbestle.downloads.pagination.previous') ?>
		</a>
		<?php endif ?>

		<?php if ($results->pagination()->hasNextPage()): ?>
		<a class="next" href="<?= $results->pagination()->nextPageURL() ?>">
			<?= I18n::translate('lukasbestle.downloads.pagination.next') ?> ›
		</a>
		<?php endif ?>
	</nav>
	<?php endif ?>
</figure>
