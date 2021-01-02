<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Baraja\Markdown\CommonMarkRenderer;

final class BarajaMarkdownDescriptionRenderer implements DescriptionRenderer
{
	public CommonMarkRenderer $commonMarkRenderer;


	public function __construct(CommonMarkRenderer $commonMarkRenderer)
	{
		$this->commonMarkRenderer = $commonMarkRenderer;
	}


	public function render(string $haystack): string
	{
		return strip_tags($this->commonMarkRenderer->render($haystack));
	}
}
