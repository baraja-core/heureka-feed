<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Baraja\SelectboxTree\SelectboxTree;
use Baraja\XmlToPhp\Convertor;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Validators;

final class CategoryManager
{
	private string $feedUrl = 'https://www.heureka.cz/direct/xml-export/shops/heureka-sekce.xml';

	private Cache $cache;


	public function __construct(Storage $storage)
	{
		$this->cache = new Cache($storage, 'heureka');
	}


	/**
	 * @return Category[]
	 */
	public function getCategories(): array
	{
		static $cache;
		if ($cache === null) {
			$cache = [];
			foreach (Helpers::buildCategoryList($this->getFeedArray()) as $category) {
				$cache[$category->getId()] = $category;
			}
		}

		return $cache;
	}


	public function getCategory(int $id): Category
	{
		if (($category = $this->getCategories()[$id] ?? null) === null) {
			throw new \InvalidArgumentException('Category "' . $id . '" does not exist.');
		}

		return $category;
	}


	public function getSelectableCategories(): array
	{
		$return = [];
		foreach ($this->getCategories() as $category) {
			if ($category->getCategoryText() !== null) {
				$return[$category->getId()] = $category->getName();
			}
		}

		return $return;
	}


	/**
	 * @return string[] (key => name)
	 */
	public function getCategoriesSelectbox(): array
	{
		if (($return = $this->cache->load('selectbox-tree')) === null) {
			$return = (new SelectboxTree)->process(Helpers::buildCategorySelectboxList($this->getFeedArray()));
			$this->cache->save('selectbox-tree', $return, [
				Cache::EXPIRATION => '1 hour',
			]);
		}

		return (array) $return;
	}


	/**
	 * @return mixed[]
	 */
	public function getFeedArray(): array
	{
		return Convertor::covertToArray($this->getFeed())['CATEGORY'] ?? [];
	}


	public function getFeed(): string
	{
		if (($cache = $this->cache->load('feed')) === null) {
			$cache = (string) file_get_contents($this->feedUrl);
			$this->cache->save('feed', $cache, [
				Cache::EXPIRATION => '8 hours',
			]);
		}

		return (string) $cache;
	}


	public function setFeedUrl(string $feedUrl): void
	{
		if (Validators::isUrl($feedUrl) === false) {
			throw new \InvalidArgumentException('Feed URL must be valid absolute URL.');
		}
		$this->feedUrl = $feedUrl;
	}
}
