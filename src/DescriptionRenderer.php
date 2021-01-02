<?php

declare(strict_types=1);

namespace Baraja\Heureka;


interface DescriptionRenderer
{
	public function render(string $haystack): string;
}
