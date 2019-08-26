<?php

declare(strict_types=1);

namespace Mathematicator\SearchController;


use Mathematicator\Engine\InvalidBoxException;
use Mathematicator\Engine\Source;
use Mathematicator\Engine\TerminateException;
use Mathematicator\Search\Box;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Tracy\Debugger;

class BaseController implements IController
{

	/**
	 * @var string
	 */
	private $query;

	/**
	 * @var Box[]
	 */
	private $boxes;

	/**
	 * @var Source[]
	 */
	private $sources = [];

	/**
	 * @var Box
	 */
	private $interpret;

	/**
	 * @var LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @param LinkGenerator $linkGenerator
	 */
	public function __construct(LinkGenerator $linkGenerator)
	{
		$this->linkGenerator = $linkGenerator;
	}

	/**
	 * @param string $type
	 * @return Box
	 * @throws TerminateException|InvalidBoxException
	 */
	public function addBox(string $type): Box
	{
		if (\count($this->boxes) >= 100) { // TODO: Implement Configurator
			throw new TerminateException(__METHOD__);
		}

		$box = new Box($type);

		$this->boxes[] = $box;

		return $box;
	}

	/**
	 * @return Box[]
	 */
	public function getBoxes(): array
	{
		return $this->boxes ?? [];
	}

	/**
	 * @return Source[]
	 */
	public function getSources(): array
	{
		return $this->sources;
	}

	public function resetBoxes(): void
	{
		$this->boxes = [];
	}

	/**
	 * @return Box|null
	 */
	public function getInterpret(): ?Box
	{
		return $this->interpret;
	}

	/**
	 * @param string $type
	 * @param null $text
	 * @return Box
	 */
	public function setInterpret(string $type, $text = null): Box
	{
		return $this->interpret = (new Box($type, 'Interpretace zadání dotazu', $text))
			->setIcon('&#xE8E2;');
	}

	/**
	 * @return string
	 */
	public function getQuery(): string
	{
		return $this->query;
	}

	/**
	 * @param string $query
	 */
	public function setQuery(string $query): void
	{
		$this->query = $query;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function actionDefault(): void
	{
		throw new \InvalidArgumentException(__METHOD__ . ': Method actionDefault does not found in result Entity.');
	}

	/**
	 * @param string $query
	 * @return string
	 */
	public function linkToSearch(string $query): string
	{
		try {
			return $this->linkGenerator->link('Front:Search:default', [
				'q' => $query,
			]);
		} catch (InvalidLinkException $e) {
			Debugger::log($e);

			return '#invalid-link';
		}
	}

	/**
	 * @param Source $source
	 */
	public function addSource(Source $source): void
	{
		$this->sources[] = $source;
	}

}
