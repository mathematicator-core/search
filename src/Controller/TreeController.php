<?php

declare(strict_types=1);

namespace Mathematicator\Search\Controller;


use Mathematicator\Engine\Controller\BaseController;
use Mathematicator\Engine\Entity\Box;
use Mathematicator\Tokenizer\Tokenizer;

final class TreeController extends BaseController
{
	private Tokenizer $tokenizer;


	public function __construct(Tokenizer $tokenizer)
	{
		$this->tokenizer = $tokenizer;
	}


	public function actionDefault(): void
	{
		if (!preg_match('/^(?:strom|tree)\s+(.+)$/', $this->getQuery(), $parser)) {
			throw new \LogicException('Invalid query.');
		}

		$tokens = $this->tokenizer->tokenize($parser[1] ?? '');
		$objects = $this->tokenizer->tokensToObject($tokens);

		$this->setInterpret(Box::TYPE_LATEX, $this->tokenizer->tokensToLatex($objects));

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Interní interpretace dotazu ve stromu')
			->setText($this->tokenizer->renderTokensTree($objects));
	}
}
