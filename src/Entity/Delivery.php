<?php

declare(strict_types=1);

namespace Baraja\Heureka;


final class Delivery
{
	public const CESKA_POSTA = 'CESKA_POSTA'; // Česká pošta - Balík Do ruky

	public const CESKA_POSTA_NAPOSTU_DEPOTAPI = 'CESKA_POSTA_NAPOSTU_DEPOTAPI'; // Česká pošta - Balík Na poštu

	public const CESKA_POSTA_DOPORUCENA_ZASILKA = 'CESKA_POSTA_DOPORUCENA_ZASILKA'; // Česká pošta - Doporučená zásilka

	public const CSAD_LOGISTIK_OSTRAVA = 'CSAD_LOGISTIK_OSTRAVA'; // ČSAD Logistik Ostrava

	public const DPD = 'DPD'; // DPD (nejedná se o DPD ParcelShop)

	public const DHL = 'DHL'; // DHL

	public const DSV = 'DSV'; // DSV

	public const FOFR = 'FOFR'; // FOFR

	public const GEBRUDER_WEISS = 'GEBRUDER_WEISS'; // Gebrüder Weiss

	public const GEIS = 'GEIS'; // Geis (nejedná se o Geis Point)

	public const GLS = 'GLS'; // GLS

	public const HDS = 'HDS'; // HDS

	public const PPL = 'PPL'; // PPL

	public const SEEGMULLER = 'SEEGMULLER'; // Seegmuller

	public const TNT = 'TNT'; // TNT

	public const TOPTRANS = 'TOPTRANS'; // TOPTRANS

	public const UPS = 'UPS'; // UPS

	public const FEDEX = 'FEDEX'; // FedEX

	public const RABEN_LOGISTICS = 'RABEN_LOGISTICS'; // Raben Logistics

	public const ZASILKOVNA = 'ZASILKOVNA'; // Zásilkovna

	public const DPD_PICKUP = 'DPD_PICKUP'; // DPD Pickup

	public const BALIKOVNA_DEPOTAPI = 'BALIKOVNA_DEPOTAPI'; // Balíkovna

	public const VLASTNI_PREPRAVA = 'VLASTNI_PREPRAVA'; // Vlastní přeprava

	public const WEDO = 'WEDO'; // WeDo (IN TIME & Uloženka)

	public const SUPPORTED_IDS = [
		self::CESKA_POSTA => self::CESKA_POSTA,
		self::CESKA_POSTA_NAPOSTU_DEPOTAPI => self::CESKA_POSTA_NAPOSTU_DEPOTAPI,
		self::CESKA_POSTA_DOPORUCENA_ZASILKA => self::CESKA_POSTA_DOPORUCENA_ZASILKA,
		self::CSAD_LOGISTIK_OSTRAVA => self::CSAD_LOGISTIK_OSTRAVA,
		self::DPD => self::DPD,
		self::DHL => self::DHL,
		self::DSV => self::DSV,
		self::FOFR => self::FOFR,
		self::GEBRUDER_WEISS => self::GEBRUDER_WEISS,
		self::GEIS => self::GEIS,
		self::GLS => self::GLS,
		self::HDS => self::HDS,
		self::PPL => self::PPL,
		self::SEEGMULLER => self::SEEGMULLER,
		self::TNT => self::TNT,
		self::TOPTRANS => self::TOPTRANS,
		self::UPS => self::UPS,
		self::FEDEX => self::FEDEX,
		self::RABEN_LOGISTICS => self::RABEN_LOGISTICS,
		self::ZASILKOVNA => self::ZASILKOVNA,
		self::DPD_PICKUP => self::DPD_PICKUP,
		self::BALIKOVNA_DEPOTAPI => self::BALIKOVNA_DEPOTAPI,
		self::VLASTNI_PREPRAVA => self::VLASTNI_PREPRAVA,
		self::WEDO => self::WEDO,
	];

	private string $id;

	private float $price;

	private ?float $priceCod;


	public function __construct(string $id, float $price, ?float $priceCod = null)
	{
		if (isset(self::SUPPORTED_IDS[$id]) === false) {
			throw new \InvalidArgumentException(
				'Delivery identifier "' . $id . '" is not supported. '
				. 'Did you mean "' . implode('", "', array_keys(self::SUPPORTED_IDS)) . '"?',
			);
		}
		$this->id = $id;
		$this->price = $price;
		$this->priceCod = $priceCod;
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getPrice(): float
	{
		return $this->price;
	}


	public function getPriceFormatted(): string
	{
		return str_replace(',00', '', number_format($this->price, 2, ',', ''));
	}


	public function getPriceCod(): ?float
	{
		return $this->priceCod;
	}


	public function getPriceCodFormatted(): ?string
	{
		return $this->priceCod === null
			? null
			: str_replace(',00', '', number_format($this->priceCod, 2, ',', ''));
	}
}
