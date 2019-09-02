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
		$this->query = $query;
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

}