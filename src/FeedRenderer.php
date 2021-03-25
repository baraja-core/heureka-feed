<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Nette\Http\Response;
use Spatie\ArrayToXml\ArrayToXml;

final class FeedRenderer
{
	public function __construct(
		private Response $response,
		private ?ProductLoader $productLoader = null,
		private ?DescriptionRenderer $descriptionRenderer = null
	) {
	}


	public function setProductLoader(ProductLoader $productLoader): void
	{
		$this->productLoader = $productLoader;
	}


	public function setDescriptionRenderer(DescriptionRenderer $descriptionRenderer): void
	{
		$this->descriptionRenderer = $descriptionRenderer;
	}


	public function render(): void
	{
		if ($this->productLoader === null) {
			throw new \RuntimeException(
				'Product loader (implementing "' . ProductLoader::class . '") is not available. '
				. 'Did you registered it as DIC service?',
			);
		}

		$return = [];
		$usedIds = [];
		foreach ($this->productLoader->getProducts() as $item) {
			if (isset($usedIds[$item->getItemId()]) === true) {
				throw new \LogicException('Product ItemId "' . $item->getItemId() . '" is not unique.');
			}
			$usedIds[$item->getItemId()] = true;
			$return[] = $item->toArray($this->descriptionRenderer);
		}

		$xml = ArrayToXml::convert(['SHOPITEM' => $return], 'SHOP', true, 'utf-8');
		$this->response->setHeader('Content-type', 'text/xml; charset=utf-8');
		echo $xml;
		die;
	}
}
