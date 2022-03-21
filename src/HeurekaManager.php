<?php

declare(strict_types=1);

namespace Baraja\Heureka;


final class HeurekaManager
{
	public function __construct(
		private FeedRenderer $feedRenderer,
	) {
	}


	public function render(): void
	{
		$this->feedRenderer->render();
	}
}
