<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Tracy\Debugger;
use Tracy\ILogger;

final class HeurekaProduct
{
	private string $itemId;

	private string $product;

	private string $productName;

	private string $url;

	private float $priceVat;

	private Category $category;

	private string $manufacturer;

	private ?string $description = null;

	private ?string $imgUrl = null;

	private ?string $videoUrl = null;

	/** @var string[] */
	private array $imgUrlAlternative = [];

	private float $vat = 21.0;

	private ?string $itemType = null;

	private ?string $deliveryDate = null;

	private ?string $ean = null;

	private ?string $isbn = null;

	private ?string $productNo = null;

	/** @var string[] */
	private array $params = [];

	private ?float $heurekaCpc = null;

	/** @var Delivery[] */
	private array $delivery = [];

	/** @var string[] */
	private array $accessories = [];

	/** @var mixed[] */
	private array $customTags = [];


	public function __construct(
		string $itemId,
		string $product,
		string $productName,
		string $url,
		float $priceVat,
		Category $category,
		string $manufacturer
	) {
		$this->setitemId($itemId);
		$this->setProduct($product);
		$this->setProductName($productName);
		$this->setUrl($url);
		$this->setPriceVat($priceVat);
		$this->setCategory($category);
		$this->setManufacturer($manufacturer);
	}


	/**
	 * @return mixed[]
	 */
	public function toArray(?DescriptionRenderer $descriptionRenderer = null): array
	{
		$return = [
			'ITEM_ID' => $this->itemId,
			'PRODUCT' => $this->product,
			'PRODUCTNAME' => $this->productName,
			'DESCRIPTION' => (static function (string $description) use ($descriptionRenderer): string {
				if ($descriptionRenderer !== null) {
					try {
						return $descriptionRenderer->render($description);
					} catch (\Throwable $e) {
						if (\class_exists(Debugger::class)) {
							Debugger::log($e, ILogger::ERROR);
						}
					}
				}

				return $description;
			})($this->description ?? ''),
			'URL' => $this->url,
			'IMGURL' => $this->imgUrl,
			'PRICE_VAT' => $this->getPriceVatFormatted(),
			'VAT' => $this->getVatFormatted(),
			'MANUFACTURER' => $this->manufacturer,
			'CATEGORYTEXT' => $this->category->getCategoryText(),
		];
		if ($this->deliveryDate !== null) {
			$return['DELIVERY_DATE'] = $this->deliveryDate;
		}
		if ($this->imgUrlAlternative !== []) {
			$return['IMGURL_ALTERNATIVE'] = $this->imgUrlAlternative;
		}
		if ($this->videoUrl !== null) {
			$return['VIDEO_URL'] = $this->videoUrl;
		}
		if ($this->ean !== null) {
			$return['EAN'] = $this->ean;
		}
		if ($this->productNo !== null) {
			$return['PRODUCTNO'] = $this->productNo;
		}
		if ($this->params !== []) {
			$params = [];
			foreach ($this->params as $paramName => $paramVal) {
				$params[] = [
					'PARAM_NAME' => $paramName,
					'VAL' => $paramVal,
				];
			}
			$return['PARAM'] = $params;
		}
		if ($this->heurekaCpc !== null) {
			$return['HEUREKA_CPC'] = $this->heurekaCpc;
		}
		if ($this->delivery !== []) {
			$deliveries = [];
			foreach ($this->delivery as $delivery) {
				$deliveryItem = [
					'DELIVERY_ID' => $delivery->getId(),
					'DELIVERY_PRICE' => $delivery->getPriceFormatted(),
				];
				if ($delivery->getPriceCod() !== null) {
					$deliveryItem['DELIVERY_PRICE_COD'] = $delivery->getPriceCod();
				}
				$deliveries[] = $deliveryItem;
			}
			$return['DELIVERY'] = $deliveries;
		}
		foreach ($this->customTags as $customTagKey => $customTagValue) {
			$return[$customTagKey] = $customTagValue;
		}

		return $return;
	}


	public function getProductName(): string
	{
		return $this->productName;
	}


	/**
	 * Exact product name. It must not contain any other information, such as a free gift, case or charger, etc.
	 *
	 * [OK] Canon PowerShot SX100 červený
	 * [ERROR] Digitální fotoaparát Canon SX100 Power-shot + nabíječka
	 */
	public function setProductName(string $productName): void
	{
		if (Strings::length($productName) > 200) {
			throw new \InvalidArgumentException('Maximum "productName" length is 200 characters, but name "' . $productName . '" given.');
		}
		$this->productName = $productName;
	}


	public function getProduct(): string
	{
		return $this->product;
	}


	public function setProduct(string $product): void
	{
		if (Strings::length($product) > 200) {
			throw new \InvalidArgumentException('Maximum "product" length is 200 characters, but name "' . $product . '" given.');
		}
		$this->product = $product;
	}


	public function getItemId(): string
	{
		return $this->itemId;
	}


	public function setItemId(string $id): void
	{
		if (Strings::length($id) > 36) {
			throw new \InvalidArgumentException('Maximum "ItemId" length is 36 characters, but identifier "' . $id . '" given.');
		}
		if (!preg_match('/^[a-zA-Z0-9-_]+$/', $id)) {
			throw new \InvalidArgumentException('ItemId does not match mandatory format, because "' . $id . '" given.');
		}
		$this->itemId = $id;
	}


	public function getDescription(): ?string
	{
		return $this->description;
	}


	public function setDescription(?string $description): void
	{
		$this->description = Strings::firstUpper(trim($description ?? '')) ?: null;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function setUrl(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('URL "' . $url . '" is not valid absolute URL.');
		}
		if (Strings::length($url) > 300) {
			throw new \InvalidArgumentException('Maximum "url" length is 300 characters, but string "' . $url . '" given.');
		}
		$this->url = $url;
	}


	public function getImgUrl(): string
	{
		if ($this->imgUrl !== null) {
			return $this->imgUrl;
		}
		if (isset($this->imgUrlAlternative[0])) {
			return $this->imgUrlAlternative[0];
		}

		throw new \LogicException('Main image URL does not exist.');
	}


	/**
	 * Minimal size is 20px x 20px, but recommended is 175px x 175px.
	 * Maximal size is 4096px x 4096px (2 MB).
	 * Please never use blank images like https://www.srovnanicen.cz/static/css/image/bez-obrazku.gif.
	 */
	public function setImgUrl(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('URL "' . $url . '" is not valid absolute URL.');
		}
		if (Strings::length($url) > 255) {
			throw new \InvalidArgumentException('Maximum "imageUrl" length is 255 characters, but string "' . $url . '" given.');
		}
		$this->imgUrl = $url;
	}


	/**
	 * @return string[]
	 */
	public function getImgUrlAlternative(): array
	{
		return $this->imgUrlAlternative;
	}


	public function addImgUrlAlternative(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('URL "' . $url . '" is not valid absolute URL.');
		}
		if (Strings::length($url) > 255) {
			throw new \InvalidArgumentException('Maximum "imageUrlAlternative" length is 255 characters, but string "' . $url . '" given.');
		}
		if ($url !== $this->imgUrl && \in_array($url, $this->imgUrlAlternative, true) === false) {
			$this->imgUrlAlternative[] = $url;
		}
	}


	public function getVideoUrl(): ?string
	{
		return $this->videoUrl;
	}


	public function setVideoUrl(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('URL "' . $url . '" is not valid absolute URL.');
		}
		if (Strings::length($url) > 255) {
			throw new \InvalidArgumentException('Maximum "videoUrl" length is 255 characters, but string "' . $url . '" given.');
		}
		if (!preg_match('/^https?:\/\/(?:www\.)?(youtube\.com|yt\.be)\//', $url)) {
			throw new \InvalidArgumentException('Video URL must be video from YouTube (domain youtube.com or yt.be), but source "' . $url . '" given.');
		}
		$this->videoUrl = $url;
	}


	public function getPriceVat(): float
	{
		return $this->priceVat;
	}


	public function setPriceVat(float $priceVat): void
	{
		if ($priceVat < 0) {
			throw new \InvalidArgumentException('Price vat can not be negative.');
		}
		$this->priceVat = $priceVat;
	}


	public function getPriceVatFormatted(): string
	{
		return str_replace(',00', '', number_format($this->priceVat, 2, ',', ''));
	}


	public function getVat(): float
	{
		return $this->vat;
	}


	public function setVat(float $vat): void
	{
		if ($vat < 0) {
			throw new \InvalidArgumentException('Vat can not be negative.');
		}
		$this->vat = $vat;
	}


	public function getVatFormatted(): string
	{
		return str_replace(',00', '', number_format($this->vat, 2, ',', ''));
	}


	public function getItemType(): ?string
	{
		return $this->itemType;
	}


	public function setItemType(?string $itemType): void
	{
		$this->itemType = $itemType;
	}


	/**
	 * @return string[]
	 */
	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * @param string[]|bool[]|mixed[] $params
	 */
	public function setParams(array $params): void
	{
		$return = [];
		foreach ($params as $key => $value) {
			if (\is_string($key) === false) {
				throw new \InvalidArgumentException('Parameter key must be string, but "' . $key . '" given.');
			}
			if (\is_string($value)) {
				$return[$key] = trim($value);
			} elseif (\is_bool($value)) {
				$return[$key] = $value ? 'ano' : 'ne';
			} elseif (\is_int($value) || \is_float($value)) {
				throw new \InvalidArgumentException('Numeric parameter "' . $key . '" must contain unit, but only number "' . $value . '" given.');
			} else {
				throw new \InvalidArgumentException('Parameter value must be type of "string" or "bool", but type "' . \gettype($value) . '" given.');
			}
		}

		$this->params = $return;
	}


	public function getManufacturer(): string
	{
		return $this->manufacturer;
	}


	public function setManufacturer(string $manufacturer): void
	{
		$this->manufacturer = Strings::firstUpper($manufacturer);
	}


	public function getCategory(): Category
	{
		return $this->category;
	}


	public function setCategory(Category $category): void
	{
		$this->category = $category;
	}


	public function getEan(): ?string
	{
		return $this->ean;
	}


	public function setEan(string $ean): void
	{
		if (Helpers::validateEAN13($ean) === false) {
			throw new \InvalidArgumentException('EAN "' . $ean . '" is not valid. Please read EAN-13 specification.');
		}
		$this->ean = $ean;
	}


	public function getIsbn(): ?string
	{
		return $this->isbn;
	}


	public function setIsbn(string $isbn): void
	{
		$isbn = str_replace('-', '', $isbn);
		if (Helpers::isValidIsbn10($isbn) === false && Helpers::isValidIsbn13($isbn) === false) {
			throw new \InvalidArgumentException('ISBN "' . $isbn . '" is not valid ISBN-10 or ISBN-13.');
		}
		$this->isbn = $isbn;
	}


	public function getHeurekaCpc(): ?float
	{
		return $this->heurekaCpc;
	}


	public function setHeurekaCpc(?float $cpc): void
	{
		if ($cpc === null || abs($cpc) < 1e-10) {
			$this->heurekaCpc = null;

			return;
		}
		if ($cpc < 0) {
			throw new \InvalidArgumentException('Heureka CPC can not be negative number, but "' . $cpc . '" given.');
		}
		if ($cpc > 1_000) {
			trigger_error('Maximal Heureka CPC value is 1000, but "' . $cpc . '" given.');
			$cpc = 1_000.0;
		}
		$this->heurekaCpc = $cpc;
	}


	public function getHeurekaCpcFormatted(): ?string
	{
		return $this->heurekaCpc === null
			? null
			: str_replace(',00', '', number_format($this->heurekaCpc, 2, ',', ''));
	}


	public function getDeliveryDate(): ?string
	{
		return $this->deliveryDate;
	}


	/**
	 * Delivery time of the product in days, ie the time from receipt of payment
	 * (in case of cash on delivery from receipt of order) to dispatch of goods.
	 * The delivery time can be the date from which the product will be placed on the market.
	 *
	 * @param int|string|\DateTimeInterface|null $deliveryDate
	 */
	public function setDeliveryDate($deliveryDate): void
	{
		if ($deliveryDate === null) {
			$this->deliveryDate = null;

			return;
		}
		if (\is_string($deliveryDate)) {
			if (((int) $deliveryDate) < 1_000 && preg_match('/^\d+$/', $deliveryDate)) {
				$deliveryDate = (int) $deliveryDate;
			} else {
				try {
					$deliveryDate = new \DateTime($deliveryDate);
				} catch (\Throwable $e) {
					throw new \InvalidArgumentException('Delivery date is invalid: ' . $e->getMessage(), $e->getCode(), $e);
				}
			}
		}
		if ($deliveryDate instanceof \DateTimeInterface) {
			if (\time() > $deliveryDate->getTimestamp()) {
				throw new \InvalidArgumentException('Delivery date can not be in past.');
			}
			$this->deliveryDate = $deliveryDate->format('Y-m-d');
		} elseif (\is_int($deliveryDate)) {
			if ($deliveryDate < 0) {
				throw new \InvalidArgumentException('Delivery date can not be negative, but "' . $deliveryDate . '" given.');
			}
			$this->deliveryDate = (string) $deliveryDate;
		} elseif (\is_string($deliveryDate)) {
			$this->deliveryDate = $deliveryDate;
		} else {
			throw new \InvalidArgumentException(
				'Delivery date must be "int", "string", "null" or "DateTime", '
				. 'but type "' . \gettype($deliveryDate) . '" given.',
			);
		}
	}


	/**
	 * @return Delivery[]
	 */
	public function getDelivery(): array
	{
		return $this->delivery;
	}


	/**
	 * @param Delivery[] $deliveries
	 */
	public function setDeliveries(array $deliveries): void
	{
		foreach ($deliveries as $delivery) {
			$this->addDelivery($delivery);
		}
	}


	public function addDelivery(Delivery $delivery): void
	{
		$this->delivery[] = $delivery;
	}


	/**
	 * @return string[]
	 */
	public function getAccessories(): array
	{
		return $this->accessories;
	}


	public function addAccessory(string $accessory): void
	{
		$this->accessories[] = $accessory;
	}


	public function getProductNo(): ?string
	{
		return $this->productNo;
	}


	public function setProductNo(?string $productNo): void
	{
		$this->productNo = $productNo;
	}


	/**
	 * @return mixed[]
	 */
	public function getCustomTags(): array
	{
		return $this->customTags;
	}


	public function addCustomTag(string $tag, mixed $value): void
	{
		$this->customTags[$tag] = $value;
	}
}
