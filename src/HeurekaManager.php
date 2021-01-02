<?php

declare(strict_types=1);

namespace Baraja\Heureka;


final class HeurekaManager
{
	private FeedRenderer $feedRenderer;


	public function __construct(FeedRenderer $feedRenderer)
	{
		$this->feedRenderer = $feedRenderer;
	}


	public function render(): void
	{
		$this->feedRenderer->render();
	}
}
