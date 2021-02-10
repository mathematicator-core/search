<?php

declare(strict_types=1);

namespace Mathematicator\Search\Entity;


class AutoCompleteResult
{
	private Result $result;

	/** @var VideoResult[] */
	private array $videos = [];


	public function getResult(): Result
	{
		return $this->result;
	}


	public function setResult(Result $result): self
	{
		$this->result = $result;

		return $this;
	}


	/**
	 * @return VideoResult[]
	 */
	public function getVideos(): array
	{
		return $this->videos;
	}


	/**
	 * @param VideoResult[] $videos
	 */
	public function setVideos(array $videos): self
	{
		$this->videos = $videos;

		return $this;
	}
}
