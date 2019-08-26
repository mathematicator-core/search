<?php

declare(strict_types=1);

namespace Mathematicator\SearchController;


use Mathematicator\Engine\InvalidDataException;
use Mathematicator\Engine\Source;
use Mathematicator\Engine\TerminateException;
use Mathematicator\Search\Box;

interface IController
{

	/**
	 * @throws TerminateException
	 */
	public function actionDefault(): void;

	/**
	 * @param string $query
	 * @throws InvalidDataException
	 */
	public function setQuery(string $query);

	/**
	 * @return Box|null
	 */
	public function getInterpret(): ?Box;

	/**
	 * @return Box[]
	 */
	public function getBoxes(): array;

	/**
	 * @return Source[]
	 */
	public function getSources(): array;

	public function resetBoxes();

}
