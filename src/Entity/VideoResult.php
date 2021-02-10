<?php

declare(strict_types=1);

namespace Mathematicator\Search\Entity;


class VideoResult
{
	private string $name;

	private string $link;

	private string $thumbnail;

	private string $description;

	private float $score = 0;


	public function getLink(): string
	{
		return $this->link;
	}


	public function setLink(string $link): self
	{
		$this->link = $link;

		return $this;
	}


	public function getThumbnail(): string
	{
		return $this->thumbnail;
	}


	public function setThumbnail(string $thumbnail): self
	{
		$this->thumbnail = $thumbnail;

		return $this;
	}


	public function getDescription(): string
	{
		return $this->description;
	}


	public function setDescription(?string $description = null): self
	{
		$this->description = $description ?? '';

		return $this;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function setName(?string $name = null): self
	{
		$this->name = $name ?? '';

		return $this;
	}


	public function getScore(): float
	{
		return $this->score;
	}


	public function setScore(float $score): self
	{
		$this->score = $score;

		return $this;
	}
}
