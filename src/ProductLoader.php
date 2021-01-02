<?php

declare(strict_types=1);

namespace Baraja\Heureka;


interface ProductLoader
{
	/**
	 * @return HeurekaProduct[]
	 */
	public function getProducts(): array;
}
