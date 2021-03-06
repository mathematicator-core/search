<?php

declare(strict_types=1);

namespace Mathematicator\Search\Controller;


use Mathematicator\Calculator\Calculator;
use Mathematicator\Calculator\Entity\CalculatorResult;
use Mathematicator\Calculator\MathFunction\FunctionDoesNotExistsException;
use Mathematicator\Calculator\Numbers\NumberHelper;
use Mathematicator\Engine\Controller\BaseController;
use Mathematicator\Engine\Entity\Box;
use Mathematicator\Engine\Entity\Query;
use Mathematicator\Engine\Exception\DivisionByZeroException;
use Mathematicator\Engine\Exception\MathematicatorException;
use Mathematicator\Engine\Exception\MathErrorException;
use Mathematicator\Engine\Exception\UndefinedOperationException;
use Mathematicator\Engine\Helper\Czech;
use Mathematicator\Engine\MathFunction\FunctionManager;
use Mathematicator\Engine\Step\Step;
use Mathematicator\Numbers\Latex\MathLatexToolkit;
use Mathematicator\Tokenizer\Token\ComparatorToken;
use Mathematicator\Tokenizer\Token\EquationToken;
use Mathematicator\Tokenizer\Token\InfinityToken;
use Mathematicator\Tokenizer\Token\IToken;
use Mathematicator\Tokenizer\Token\NumberToken;
use Mathematicator\Tokenizer\Token\OperatorToken;
use Mathematicator\Tokenizer\Tokenizer;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class NumberCounterController extends BaseController
{
	private Tokenizer $tokenizer;

	private Calculator $calculator;

	private NumberHelper $number;

	private bool $haveResult = false;


	public function __construct(
		Tokenizer $tokenizer,
		Calculator $calculator,
		NumberHelper $number
	) {
		$this->tokenizer = $tokenizer;
		$this->calculator = $calculator;
		$this->number = $number;
	}


	public function actionDefault(): void
	{
		$tokens = $this->tokenizer->tokenize($this->getQuery());
		$objects = $this->tokenizer->tokensToObject($tokens);

		$this->setInterpret(Box::TYPE_LATEX)
			->setText($this->tokenizer->tokensToLatex($objects));

		$calculator = [];
		$steps = [];

		try {
			$calculatorResult = $this->calculate($objects);
			$calculator = $calculatorResult->getResultTokens();
			$steps = $calculatorResult->getSteps();
		} catch (DivisionByZeroException $e) {
			$fraction = $e->getFraction();

			$step = new Step(null, null);
			$step->setTitle($this->translator->translate('divisionByZero'));
			$step->setDescription($this->translator->translate('divisionByZeroDesc', [
				'%number%' => $fraction[0],
			]));

			$this->addBox(Box::TYPE_TEXT)
				->setTitle($this->translator->translate('solution'))
				->setText('Tento příklad nelze v reálných číslech vyřešit z důvodu dělení nulou.')
				->setSteps([$step]);

			$this->addBox(Box::TYPE_LATEX)
				->setTitle($this->translator->translate('solution'))
				->setText('\frac{' . $fraction[0] . '}{' . $fraction[1] . '} = \frac{1}{0} \simeq \infty');

			$this->haveResult = true;
		} catch (UndefinedOperationException $e) {
			$this->actionUndefinedSolution();
			$this->haveResult = true;
		} catch (FunctionDoesNotExistsException $e) {
			$supportedFunctions = '';

			foreach (FunctionManager::getFunctionNames() as $function) {
				if (preg_match('/^\w+$/', $function)) {
					$supportedFunctions .= ($supportedFunctions ? ', ' : '')
						. '<code>' . $function . '()</code>';
				}
			}

			$this->addBox(Box::TYPE_TEXT)
				->setTitle('Funkce neexistuje')
				->setText('<p>Funkci <b>' . $e->getFunction() . '()</b> neumíme zpracovat.</p>'
					. '<p>Seznam podporovaných funkcí:</p>'
					. $supportedFunctions);

			$this->haveResult = true;
		} catch (MathErrorException | MathematicatorException $e) {
			$this->addBox(Box::TYPE_TEXT)
				->setTitle($this->translator->translate('solution'))
				->setText(
					'Tato úloha nemá řešení, protože provádíte nepovolenou matematickou operaci.'
					. "\n\n" . 'Detaily: ' . $e->getMessage(),
				);

			$this->haveResult = true;
		}

		if (\count($calculator) === 1) {
			$this->renderResultToken($calculator[0], $steps);

			if ($this->isSimpleProblem($objects)) {
				$this->actionSimpleProblem($objects);
			} elseif ($this->isAddNumbers($objects)) {
				$this->actionAddNumbers($objects);
			}
		} elseif (isset($calculator[0], $calculator[1], $calculator[2])
			&& ($calculator[1] instanceof EquationToken || $calculator[1] instanceof ComparatorToken)
		) {
			if ($calculator[0] instanceof NumberToken && $calculator[2] instanceof NumberToken) {
				$this->actionBoolean($calculator[0], $calculator[2], $calculator[1], $steps);
			} else {
				$this->addBox(Box::TYPE_LATEX)
					->setTitle('Řešení výroku')
					->setText($calculator[0]->getToken() . ' ' . $calculator[1]->getToken() . ' ' . $calculator[2]->getToken());
			}
			$this->haveResult = true;
		} elseif ($calculator !== [] && $calculator !== $objects) {
			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Upravený zápis')
				->setText($this->tokenizer->tokensToLatex($calculator))
				->setSteps($steps);

			// TODO: $this->plotFunction($calculator);

			$this->haveResult = true;
		}

		if ($this->haveResult === false) {
			$this->actionError($steps);
		}
	}


	/**
	 * Bridge for define types of possible exceptions.
	 *
	 * @param IToken[] $tokens
	 * @throws MathematicatorException|DivisionByZeroException|UndefinedOperationException|FunctionDoesNotExistsException|MathErrorException
	 */
	private function calculate(array $tokens, int $basicTtl = 3): CalculatorResult
	{
		return $this->calculator->calculate($tokens, $this->getQueryEntity(), $basicTtl);
	}


	/**
	 * @param Step[] $steps
	 */
	private function actionError(array $steps): void
	{
		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Nepodařilo se nalézt řešení')
			->setText('Tento vstup bohužel neumíme upravit.')
			->setSteps($steps);
	}


	/**
	 * @param IToken[] $tokens
	 */
	private function actionSimpleProblem(array $tokens): void
	{
		$buffer = '';
		foreach ($tokens as $token) {
			$buffer .= '<div style="border:1px solid #aaa;float:left;min-height:70px;margin:4px;padding:4px">';
			if ($token instanceof NumberToken) {
				$int = $token->getNumber()->toBigInteger();
				if ($int->isGreaterThanOrEqualTo(0)) {
					$buffer .= $this->renderNumber((string) $int);
				} else {
					$buffer .= '<div style="overflow:auto;margin:-8px">'
						. '<div style="float:left;min-height:70px;margin:4px 4px 4px 12px">'
						. '<span style="font-size:27pt;padding:8px">-</span>'
						. '</div>'
						. '<div style="border-left:1px solid #aaa;float:left;min-height:70px;margin:4px;padding:4px">'
						. $this->renderNumber((string) $int->abs())
						. '</div></div>';
				}
			} else {
				$buffer .= '<span style="font-size:27pt;padding:8px">' . $token->getToken() . '</span>';
			}

			$buffer .= '</div>';
		}

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Grafická reprezentace příkladu')
			->setText('<div style="overflow:auto">' . $buffer . '</div>');

		$this->haveResult = true;
	}


	/**
	 * @param IToken[]|NumberToken[] $tokens
	 */
	private function actionAddNumbers(array $tokens): void
	{
		/** @var NumberToken $numberToken */
		$numberToken = $tokens[0];

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Sčítání pod sebou')
			->setText(
				$this->number->getAddStepAsHtml(
					(string) $numberToken->getNumber()->getInput(),
					(string) $numberToken->getNumber()->getInput(),
					true,
				),
			);

		$this->haveResult = true;
	}


	private function actionUndefinedSolution(): void
	{
		$this->addBox(Box::TYPE_TEXT)
			->setTitle($this->translator->translate('solution'))
			->setText('Nemá žádné řešení, jde o neurčitý výraz. Není definováno.');

		$undefinedForms = [
			'\frac{0}{0}',
			'\frac{\infty}{\infty}',
			'0\ \cdot\ \infty',
			'\infty\ -\ \infty',
			'{1}^{\infty}',
			'{\infty}^{0}',
			'{(-1)}^{\infty}',
			'{0}^{i}',
			'{0}^{0}',
			'{z}^{\infty}\ \text{for}\ \left\|z\right\|\ =\ 1',
			'\sqrt[0]{x}',
		];

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Přehled neurčitých výrazů')
			->setText(implode("\n", $undefinedForms));

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Limita typu 0<sup>0</sup>')
			->setText(implode("\n", [
				'\lim\limits_{x\to{0}^{+}} {x}^{x}\ =\ 1',
				'\lim\limits_{x\to{0}^{+}} {0}^{x}\ =\ 0',
			]));
	}


	/**
	 * @param Step[] $steps
	 */
	private function actionBoolean(IToken $tokenA, IToken $tokenB, ComparatorToken $comparator, array $steps): bool
	{
		$numberA = $tokenA->getToken();
		$numberB = $tokenB->getToken();

		$isTrue = static function (IToken $a, IToken $b, ComparatorToken $comparator) {
			$numberA = $a->getToken();
			$numberB = $b->getToken();

			if ($comparator instanceof EquationToken || $comparator->getToken() === '=') {
				return $numberA === $numberB;
			}

			switch ($comparator->getToken()) {
				case '<<':
					return (float) $numberA < (float) $numberB;

				case '>>':
					return (float) $numberA > (float) $numberB;

				case '<=>':
				case '<>':
				case '!==':
				case '!=':
					return $numberA !== $numberB;

				case '<=':
					return (float) $numberA <= (float) $numberB;

				case '>=':
					return (float) $numberA >= (float) $numberB;

				case '<':
					return (float) $numberA < (float) $numberB;

				case '>':
					return (float) $numberA > (float) $numberB;
			}

			return false;
		};

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Řešení výroku')
			->setText($isTrue($tokenA, $tokenB, $comparator)
				? '<b style="color:green">PRAVDA</b>'
				: '<b style="color:red">NEPRAVDA</b>')
			->setSteps($steps);

		if ($numberA === $numberB) {
			$this->addBox(Box::TYPE_LATEX)
				->setTitle($this->translator->translate('solution'))
				->setText($numberA);

			return true;
		}

		$overlap = '';
		if (Validators::isNumeric($numberA) && Validators::isNumeric($numberB)) {
			for ($i = 0; isset($numberA[$i], $numberB[$i]); $i++) {
				if ($numberA[$i] === $numberB[$i]) {
					$overlap .= $numberA[$i];
				} else {
					break;
				}
			}
		}

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Porovnání řešení')
			->setText($numberA . "\n" . $numberB);

		if (\strlen($overlap) > 2) {
			$this->addBox(Box::TYPE_LATEX)
				->setTitle(
					'Překryv řešení | Přesnost: '
					. Czech::inflection(\strlen($overlap), ['místo', 'místa', 'míst']),
				)
				->setText($overlap);
		}

		$calculatorResult = $this->calculator->calculateString(
			new Query($numberA . '-' . $numberB, $numberA . '-' . $numberB),
		);
		$calculator = $calculatorResult->getResultTokens();
		$steps = $calculatorResult->getSteps();

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Rozdíl řešení')
			->setText($calculator[0]->getToken())
			->setSteps($steps);

		$calculatorShareResult = $this->calculator->calculateString(
			new Query($numberA . '-' . $numberB, $numberA . '-' . $numberB),
		);
		$calculatorShare = $calculatorShareResult->getResultTokens();
		$stepsShare = $calculatorShareResult->getSteps();

		/** @var NumberToken[] $calculatorShare */
		if ($calculatorShare[0] instanceof NumberToken) {
			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Podíl řešení')
				->setText(MathLatexToolkit::frac($numberA, $numberB)->equals($calculatorShare[0]->getToken())
					. '\ ≈\ ' . $calculatorShare[0]->getNumber()->toLatex())
				->setSteps($stepsShare);
		}

		return true;
	}


	/**
	 * @param Step[] $steps
	 */
	private function renderResultToken(IToken $token, array $steps = []): void
	{
		if ($token instanceof NumberToken) {
			if ($token->getNumber()->isInteger()) {
				$result = '\(' . $token->getNumber()->toBigInteger() . '\)';
			} else {
				$fraction = $token->getNumber()->toBigRational();
				$result = '\(' . ($fraction->getNumerator()->isLessThan(0) ? '-' : '')
					. MathLatexToolkit::frac((string) $fraction->getNumerator()->abs(), (string) $fraction->getDenominator())
					. ' ≈ '
					. preg_replace('/^(.+)[eE](.+)$/', '$1\ \cdot\ {10}^{$2}', (string) $token->getNumber()->toLatex()) . '\)'
					. '<br><br><span class="text-secondary">Upozornění: Řešení může být zobrazeno jen přibližně.</span>';
			}

			$this->addBox(Box::TYPE_HTML)
				->setTitle($this->translator->translate('solution'))
				->setText($result)
				->setSteps($steps);

			if ($token->getNumber()->isInteger()) {
				$int = $token->getNumber()->toBigInteger();
				$numberLength = \strlen((string) $int);
				if ($numberLength > 8) {
					$this->addBox(Box::TYPE_TEXT)
						->setTitle('Délka čísla')
						->setText(Czech::inflection($numberLength, ['cifra', 'cifry', 'cifer']));

					if (preg_match('/^(\d)((\d{1,7}).*?)$/', (string) $int, $intParser)) {
						$this->addBox(Box::TYPE_LATEX)
							->setTitle('Desetinná aproximace')
							->setText($intParser[1] . '.' . $intParser[3] . '\ \cdot\ ' . MathLatexToolkit::pow(10, \strlen($intParser[2])));
					}

					if (Strings::endsWith((string) $int, '0')) {
						$zeros = (string) preg_replace('/^\d+?(0+)$/', '$1', (string) $int);
						$trailingZerosBox = $this->addBox(Box::TYPE_LATEX)
							->setTitle('Počet nul na konci')
							->setText((string) ($zeros ? \strlen($zeros) : 0));

						if (preg_match('/^(\d+)\s*\!$/', $this->getQuery(), $factorialParser)) {
							$trailingZerosBox->setSteps($this->getStepsFactorialTrailingZeros((int) $factorialParser[1]));
						} else {
							$trailingZerosBox->setSteps([
								new Step(
									'Manuální výpočet',
									null,
									'Pro tuto úlohu neznáme elegantní způsob, jak zjistit počet nul na konci, proto je potřeba celkový počet spočítat ručně přímo z výsledku.',
								),
							]);
						}
					}
				}
			}

			$this->haveResult = true;
		}

		if ($token instanceof InfinityToken) {
			$this->addBox(Box::TYPE_LATEX)
				->setTitle($this->translator->translate('solution'))
				->setText('\infty')
				->setSteps($steps);

			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Číselný obor')
				->setText('\mathbb{R}\ \cup\left\{+\infty,-\infty\right\}' . "\n" . '{\mathbb{R}}^{*}');

			$this->haveResult = true;
		}
	}


	/**
	 * @param IToken[] $tokens
	 */
	private function isSimpleProblem(array $tokens): bool
	{
		if (($tokensCount = \count($tokens)) < 3 || $tokensCount > 12) {
			return false;
		}
		foreach ($tokens as $token) {
			if (!(
				(
					$token instanceof OperatorToken
					&& \in_array($token->getToken(), ['+', '-'], true)
				) || (
					$token instanceof NumberToken
					&& $token->getNumber()->isInteger()
					&& $token->getNumber()->toBigInteger()->isLessThanOrEqualTo(20)
				)
			)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param IToken[] $tokens
	 */
	private function isAddNumbers(array $tokens): bool
	{
		return \count($tokens) === 3
			&& $tokens[0] instanceof NumberToken
			&& $tokens[1] instanceof OperatorToken
			&& $tokens[1]->getToken() === '+'
			&& $tokens[2] instanceof NumberToken;
	}


	private function renderNumber(string $int): string
	{
		$render = '';
		for ($i = 1; $i <= $int; $i++) {
			$render .= '<div style="float: left; width: 8px; height: 8px; background: #EA4437; margin: 3px;"></div>';
		}

		return '<div style="max-width:70px">' . $render . '</div>';
	}


	/**
	 * @return Step[]
	 */
	private function getStepsFactorialTrailingZeros(int $factorial): array
	{
		$return = [];
		$return[] = new Step(
			'Výpočet počtu nul na konci pro faktoriál ' . $factorial . '!',
			'\begin{aligned} f(n) &= \sum_{i=1}^k \left\lfloor{\frac{n}{5^i}}\right\rfloor = \left\lfloor{\frac{n}{5}}\right\rfloor+\left\lfloor{\frac{n}{5^2}}\right\rfloor+\left\lfloor{\frac{n}{5^3}}\right\rfloor+\dots+\left\lfloor{\frac{n}{5^k}}\right\rfloor \end{aligned}',
			'Počet nul, kterými končí faktoriál libovolného celého čísla \(n\) lze vypočítat součtem řady zlomků. Řadu je potřeba sčítat až do hodnoty \(k=\left\lfloor \log_5{n} \right\rfloor\).',
		);

		$fractions = '';
		$fractionValues = '';
		$count = 0;

		for ($i = 5; $factorial / $i >= 1; $i *= 5) {
			$count += (int) ($factorial / $i);
			$fractions .= ($fractions ? ' + ' : '') . '\left\lfloor{\frac{' . $factorial . '}{' . $i . '}}\right\rfloor';
			$fractionValues .= ($fractionValues ? ' + ' : '') . ((int) ($factorial / $i));
		}

		$return[] = new Step(
			'Sestavíme řadu zlomků',
			'\begin{aligned} f(n) &= \sum_{i=1}^k \left\lfloor{\frac{n}{5^i}}\right\rfloor = ' . $fractions . ' \end{aligned}',
			'Řešíme úlohu pro \(n = ' . $factorial . '\). U zlomků si všimněte závorky, která značí zaokrouhlení směrem dolů.',
		);

		$return[] = new Step(
			'Vypočítáme hodnotu zlomků a sečteme',
			$fractionValues . ' = ' . $count,
			null,
		);

		return $return;
	}
}
