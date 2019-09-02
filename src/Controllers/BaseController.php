<?php

declare(strict_types=1);

namespace Mathematicator\SearchController;


use Mathematicator\Engine\InvalidBoxException;
use Mathematicator\Engine\Source;
use Mathematicator\Engine\TerminateException;
use Mathematicator\Search\Box;
use Mathematicator\Search\Context;
use Mathematicator\Search\Query;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\SmartObject;
use Tracy\Debugger;

/**
 * @property-read string $query
 * @property-read Query $queryEntity
 */
class BaseController implements IController
{

	use SmartObject;

	/**
	 * @var Context
	 */
	private $context;

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
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->context;
	}

	/**
	 * @param string $type
	 * @return Box
	 * @throws TerminateException|InvalidBoxException
	 */
	public function addBox(string $type): Box
	{
		return $this->context->addBox($type);
	}

	/**
	 * @param string $type
	 * @param null $text
	 * @return Box
	 */
	public function setInterpret(string $type, $text = null): Box
	{
		return $this->context->setInterpret($type, $text);
	}

	/**
	 * @return string
	 */
	public function getQuery(): string
	{
		return $this->context->getQuery();
	}

	/**
	 * @internal
	 * @param Query $query
	 * @return Context
	 */
	public function createContext(Query $query): Context
	{
		if ($this->context === null) {
			$this->context = new Context($query);
		}

		return $this->context;
	}

	/**
	 * @return Query
	 */
	public function getQueryEntity(): Query
	{
		return $this->context->getQueryEntity();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function actionDefault(): void
	{
		throw new \InvalidArgumentException(__METHOD__ . ': Method actionDefault() does not found in result Entity.');
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
		$this->context->addSource($source);
	}

}
