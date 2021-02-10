<?php

declare(strict_types=1);

namespace Mathematicator\Search\Controller;


use Mathematicator\Engine\Controller\BaseController;
use Mathematicator\Engine\Entity\Box;
use Mathematicator\Engine\Exception\MathematicatorException;
use Mathematicator\Integral\IntegralSolver;
use Mathematicator\Tokenizer\Tokenizer;
use Nette\Tokenizer\Exception;

final class IntegralController extends BaseController
{
	private IntegralSolver $integral;

	private Tokenizer $tokenizer;


	public function __construct(IntegralSolver $integral, Tokenizer $tokenizer)
	{
		$this->integral = $integral;
		$this->tokenizer = $tokenizer;
	}


	/**
	 * @throws Exception|MathematicatorException
	 */
	public function actionDefault(): void
	{
		preg_match('/^integr(?:a|á)l\s+(.+)$/u', $this->getQuery(), $parser);

		$process = $this->integral->process($parser[1] ?? $this->getQuery());
		$this->setInterpret(Box::TYPE_LATEX, $process->getQueryLaTeX());

		$resultTokens = $this->tokenizer->tokenize($process->getResult());
		$resultObjects = $this->tokenizer->tokensToObject($resultTokens);

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Řešení integrálu')
			->setText($this->tokenizer->tokensToLatex($resultObjects))
			->setSteps($process->getSteps());
	}
}
