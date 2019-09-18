<?php

declare(strict_types=1);

namespace Mathematicator\Search;


use Nette\SmartObject;
use Nette\Utils\DateTime;

class Query
{

	use SmartObject;

	/**
	 * @var string
	 */
	private $original;

	/**
	 * @var string
	 */
	private $query;

	/**
	 * @var string
	 */
	private $locale = 'cs';

	/**
	 * @var int
	 */
	private $decimals = 8;

	/**
	 * @var float
	 */
	private $latitude = 50.0755381;

	/**
	 * @var float
	 */
	private $longitude = 14.4378005;

	/**
	 * @var \DateTime
	 */
	private $dateTime;

	/**
	 * @param string $original
	 * @param string $query
	 */
	public function __construct(string $original, string $query)
	{
		$this->original = $original;
		$this->query = $this->process($query);
		$this->dateTime = DateTime::from('now');
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->query;
	}

	/**
	 * @return string
	 */
	public function getOriginal(): string
	{
		return $this->original;
	}

	/**
	 * @return string
	 */
	public function getQuery(): string
	{
		return $this->query;
	}

	/**
	 * @return string
	 */
	public function getLocale(): string
	{
		return $this->locale;
	}

	/**
	 * @return int
	 */
	public function getDecimals(): int
	{
		return $this->decimals;
	}

	/**
	 * @return bool
	 */
	public function isDefaultDecimals(): bool
	{
		return $this->decimals === 8;
	}

	/**
	 * @return float
	 */
	public function getLatitude(): float
	{
		return $this->latitude;
	}

	/**
	 * @return float
	 */
	public function getLongitude(): float
	{
		return $this->longitude;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTime(): \DateTime
	{
		return $this->dateTime;
	}

	/**
	 * @param string $query
	 * @return string
	 */
	private function process(string $query): string
	{
		$query = (string) preg_replace_callback(
			'/\s+na\s+(\d+)\s+(?:míst[oay]?)|\s+to\s+(\d+)\s+digits?/u',
			function (array $match): string {
				$this->decimals = (int) ($match[1] ? : $match[2]);

				return '';
			}, $query
		);

		return $query;
	}

}
