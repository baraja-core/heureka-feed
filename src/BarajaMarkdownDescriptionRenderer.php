<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Baraja\Markdown\CommonMarkRenderer;

final class BarajaMarkdownDescriptionRenderer implements DescriptionRenderer
{
	public function __construct(
		private CommonMarkRenderer $commonMarkRenderer
	) {
	}


	public function render(string $haystack): string
	{
		return strip_tags($this->commonMarkRenderer->render($haystack));
	}
}
