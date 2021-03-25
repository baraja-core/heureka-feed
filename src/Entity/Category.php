<?php

declare(strict_types=1);

namespace Baraja\Heureka;


final class Category
{
	public function __construct(
		private int $id,
		private ?self $parent,
		private string $name,
		private ?string $fullName
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function __toString(): string
	{
		return $this->getCategoryText();
	}


	public function getParentId(): ?int
	{
		return $this->parent === null ? null : $this->parent->getId();
	}


	public function getParent(): ?self
	{
		return $this->parent;
	}


	public function setParent(self $parent): void
	{
		$this->parent = $parent;
	}


	/** Resolve full path like "Heureka.cz | Elektronika | Počítače a kancelář | Kancelářské potřeby | Diáře". */
	public function getCategoryText(): string
	{
		return $this->fullName ?? 'Heureka.cz | ' . $this->getName();
	}


	/**
	 * Return array of categories:
	 *  [ID => Name]
	 *
	 * @return string[]
	 */
	public function getParentsPath(): array
	{
		$return = [];
		$category = $this;

		do {
			$return[$category->getId()] = $category->getName();
			$category = $category->getParent();
		} while ($category !== null);

		return array_reverse($return, true);
	}
}
