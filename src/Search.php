<?php

declare(strict_types=1);

namespace Mathematicator\Search;


use Mathematicator\Engine\Engine;
use Mathematicator\Engine\EngineMultiResult;
use Mathematicator\Engine\EngineResult;
use Mathematicator\Engine\EngineSingleResult;
use Mathematicator\Engine\InvalidDataException;
use Mathematicator\Engine\NoResultsException;
use Mathematicator\Router\DynamicRoute;
use Mathematicator\Router\Router;
use Mathematicator\Search\Translation\TranslatorHelper;
use Mathematicator\SearchController\CrossMultiplicationController;
use Mathematicator\SearchController\DateController;
use Mathematicator\SearchController\IntegralController;
use Mathematicator\SearchController\MandelbrotSetController;
use Mathematicator\SearchController\NumberController;
use Mathematicator\SearchController\NumberCounterController;
use Mathematicator\SearchController\OEISController;
use Mathematicator\SearchController\SequenceController;
use Mathematicator\SearchController\TreeController;
use Symfony\Component\Translation\Translator;
use Tracy\Debugger;

class Search
{

	/** @var Engine */
	private $engine;


	/** @var Translator */
	private $translator;


	/**
	 * @param Engine $engine
	 * @param Router $router
	 * @param TranslatorHelper $translatorHelper
	 * @param Translator $translator
	 */
	public function __construct(
		Engine $engine,
		Router $router,
		TranslatorHelper $translatorHelper,
		Translator $translator
	) {
		$this->engine = $engine;
		$this->translator = $translator;

		$translatorHelper->init();

		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, '(?:strom|tree)\s+.+', TreeController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, 'integr(?:a|á)l\s+.+', IntegralController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, '-?[0-9]*[.]?[0-9]+([Ee]\d+)?', NumberController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, '\d+\/\d+', NumberController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, '[IVXLCDMivxlcdm]{2,}', NumberController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_STATIC, ['pi', 'ludolfovo cislo'], NumberController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_STATIC, ['inf'], NumberCounterController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, 'A\d{6}', OEISController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_TOKENIZE, null, NumberCounterController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, 'now|\d{1,2}\.\d{1,2}\.\d{4}|\d{4}-\d{1,2}-\d{1,2}', DateController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_REGEX, '(\-?[0-9]*[.]?[0-9]+([^0-9\.\-]+)?){3,}', SequenceController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_STATIC, ['mandelbrotova mnozina', 'mandelbrot set'], MandelbrotSetController::class));
		$router->addDynamicRoute(new DynamicRoute(DynamicRoute::TYPE_STATIC, ['trojclenka'], CrossMultiplicationController::class));
	}


	/**
	 * Set language for translator
	 *
	 * @param string $lang
	 */
	public function setLocale($lang): void
	{
		$this->translator->setLocale($lang);
	}


	/**
	 * @param string $query
	 * @return EngineResult|EngineResult[]
	 * @throws InvalidDataException|NoResultsException
	 */
	public function search(string $query)
	{
		if (class_exists('\Tracy\Debugger')) {
			Debugger::timer('search_request');
		}

		if (($engineResult = $this->engine->compute($query)) instanceof EngineMultiResult) {
			return [
				'left' => $engineResult->getResult('left'),
				'right' => $engineResult->getResult('right'),
			];
		}

		return $engineResult;
	}


	/**
	 * @param string $query
	 * @return AutoCompleteResult
	 * @throws InvalidDataException|NoResultsException
	 */
	public function searchAutocomplete(string $query): AutoCompleteResult
	{
		$searchResult = $this->search($query);
		/** @var EngineSingleResult $resultEntity */
		$resultEntity = \is_array($searchResult) ? $searchResult['left'] : $searchResult;

		return (new AutoCompleteResult)
			->setResult((new Result)->setBoxes($resultEntity->getBoxes()));
	}
}
