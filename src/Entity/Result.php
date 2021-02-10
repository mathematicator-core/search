<?php

declare(strict_types=1);

namespace Mathematicator\Search\Entity;


use Mathematicator\Engine\Entity\Box;

final class Result
{
	private float $time;

	private string $query;

	private int $length = 0;

	private int $userRequests = 0;

	private ?Box $interpret = null;

	private string $matchedRoute;

	/** @var Box[] */
	private array $boxes = [];


	public function getQuery(): string
	{
		return $this->query;
	}


	public function setQuery(string $query): self
	{
		$this->query = $query;

		return $this;
	}


	public function getLength(): int
	{
		return $this->length;
	}


	public function setLength(int $length): self
	{
		$this->length = $length;

		return $this;
	}


	public function getUserRequests(): int
	{
		return $this->userRequests;
	}


	public function setUserRequests(int $userRequests): self
	{
		$this->userRequests = $userRequests;

		return $this;
	}


	public function getInterpret(): ?Box
	{
		return $this->interpret;
	}


	public function setInterpret(Box $interpret): self
	{
		$this->interpret = $interpret;

		return $this;
	}


	public function getMatchedRoute(): string
	{
		return $this->matchedRoute;
	}


	public function setMatchedRoute(string $matchedRoute): self
	{
		$this->matchedRoute = $matchedRoute;

		return $this;
	}


	/**
	 * @return Box[]
	 */
	public function getBoxes(): array
	{
		return $this->boxes;
	}


	/**
	 * @param Box[] $boxes
	 */
	public function setBoxes(array $boxes): self
	{
		$this->boxes = $boxes;

		return $this;
	}


	public function getTime(): float
	{
		return $this->time;
	}


	public function setTime(float $time): self
	{
		$this->time = $time;

		return $this;
	}
}
