<?php

use Kirby\Http\Uri;
use Kirby\Toolkit\Escape;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/** @var \LukasBestle\Downloads\DownloadsBlock $block */
?>
<figure class="downloads" id="<?= Escape::attr($block->htmlId()) ?>">
	<?php if ($block->hasFilters() === true || $block->hasSearch() === true): ?>
	<form action="<?= Escape::attr(Uri::current() . '#' . $block->htmlId()) ?>" method="POST">
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
		<?php foreach ($block->results() as $file): ?>
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
</figure>
