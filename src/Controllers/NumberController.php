<?php

declare(strict_types=1);

namespace Mathematicator\SearchController;


use App\VikiTron\Model\Number\NumberHelper;
use Math\Steps\Model\RomanIntSteps;
use Mathematicator\Engine\DivisionByZero;
use Mathematicator\Engine\Helper\Czech;
use Mathematicator\Engine\Helper\DateTime;
use Mathematicator\Engine\Translator;
use Mathematicator\Numbers\NumberFactory;
use Mathematicator\Numbers\SmartNumber;
use Mathematicator\Search\Box;
use Model\Math\Step\StepFactory;
use Nette\Application\LinkGenerator;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class NumberController extends BaseController
{

	/**
	 * @var Translator
	 */
	private $translator;

	/**
	 * @var NumberHelper
	 */
	private $numberHelper;

	/**
	 * @var SmartNumber
	 */
	private $number;

	/**
	 * @var NumberFactory
	 */
	private $numberFactory;

	/**
	 * @var RomanIntSteps
	 */
	private $romanToIntSteps;

	/**
	 * @var StepFactory
	 */
	private $stepFactory;

	/**
	 * @param LinkGenerator $linkGenerator
	 * @param NumberHelper $number
	 * @param NumberFactory $numberFactory
	 * @param RomanIntSteps $romanToIntSteps
	 * @param StepFactory $stepFactory
	 * @param Translator $translator
	 */
	public function __construct(
		LinkGenerator $linkGenerator,
		NumberHelper $number,
		NumberFactory $numberFactory,
		RomanIntSteps $romanToIntSteps,
		StepFactory $stepFactory,
		Translator $translator
	)
	{
		parent::__construct($linkGenerator);
		$this->numberHelper = $number;
		$this->numberFactory = $numberFactory;
		$this->romanToIntSteps = $romanToIntSteps;
		$this->stepFactory = $stepFactory;
		$this->translator = $translator;
	}

	public function actionDefault(): void
	{
		$number = $this->getQuery();
		$isRoman = false;
		if ($this->numberHelper->isRoman($this->getQuery())) {
			$number = NumberHelper::romanToInt($this->getQuery());
			$isRoman = true;

			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Převod do Arabských číslic')
				->setText($number)
				->setSteps($this->romanToIntSteps->getRomanToIntSteps($this->getQuery()));
		}

		try {
			$this->number = $this->numberFactory->create($number);
			$this->actionNumericalField($this->number);
		} catch (DivisionByZero $e) {
			$this->actionDivisionByZero($number);
		}

		if ($this->number->isInteger()) {
			$this->setInterpret(
				Box::TYPE_LATEX,
				$isRoman
					? '\\text{' . Strings::upper($this->getQuery()) . '} = ' . $this->number->getString()
					: $this->number->getString()
			);

			$this->actionInteger();
		} elseif ($this->number->isFloat()) {
			$fraction = $this->number->getFraction();
			$this->setInterpret(
				Box::TYPE_LATEX,
				'\frac{' . $fraction[0] . '}{' . $fraction[1]
				. '} ≈ ' . Strings::substring($this->number->getFloat(),
					0, 50
				)
			);

			$this->actionFloat($this->number->getFloat());
		}
	}

	private function actionInteger(): void
	{
		$int = $this->number->getInteger();

		if ($int >= 1750 && $int <= 2300) {
			$this->actionYear((int) date('Y'), (int) $int);
		}

		if ($int === '42') {
			$this->addBox(Box::TYPE_TEXT)
				->setTitle('Ahoj, stopaři!')
				->setText('Odpověď na Základní otázku života, Vesmíru a tak vůbec');
		}

		if ($int <= 1000000) {
			$this->numberSystem($int);
			$this->alternativeRewrite();
		} else {
			if ($int < 18446744073709551616) { // 2^64
				$this->timestamp($int);
			}

			$this->bigNumber($int);
		}

		$this->primeFactorization();

		if ($int <= 1000000) {
			$this->divisors();
		}

		if ($int <= 50 && $int > 0) {
			$this->graphicInt();
		}
	}

	private function actionDivisionByZero(string $number): void
	{
		preg_match('/^(?<top>.+?)\/(?<bottom>.+)$/', $number, $match);

		$this->setInterpret(
			Box::TYPE_LATEX,
			'\frac{' . $match['top'] . '}{' . $match['bottom'] . '}\ \simeq\ ???'
		);

		$step = $this->stepFactory->create();
		$step->setTitle('Dělení nulou');
		$step->setDescription($this->translator->getTranslate('divisionByZero', [
			'count' => (int) $match['top'],
		]));

		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Řešení')
			->setText('Tento příklad nelze v reálných číslech vyřešit z důvodu dělení nulou.')
			->setSteps([$step]);
	}

	/**
	 * @param float $float
	 */
	private function actionFloat(float $float): void
	{
		if (abs($float - M_PI) < 0.1) {
			$this->aboutPi();
		}

		if ($this->number->getFraction()[1] !== 1) {
			$this->convertToFraction();
		}
	}

	/**
	 * @param SmartNumber $number
	 */
	private function actionNumericalField(SmartNumber $number): void
	{
		$steps = [];
		$step = $this->stepFactory->create();
		$step->setTitle('Číselné obory');
		$step->setDescription('Určíme číselný obor podle tabulky.');
		$steps[] = $step;

		$step = $this->stepFactory->create();
		$step->setTitle('Přehled číselných oborů');

		$numberTypesHtml = '';
		$numberTypes = [
			'N' => 'Přirozená čísla: \(1, 2, 3, 100, 105, 1006\), ...',
			'Z' => 'Celá čísla: \(-10, -1, 0, 1, 2, 3\), ...',
			'Q' => 'Racionální čísla: \(-10, -1, 0, \frac{1}{3}, \frac{5}{2}, 2.5, 3\), ...',
			'R' => 'Reálná čísla: \(-10, -1, 0, 1/3, \frac{5}{2}, 2.5, \pi, e, \sqrt{2}, 3\), ...',
			'R \setminus Q' => 'Iracionální čísla: \(\pi, e, \sqrt{2}\), ...',
			'C' => 'Komplexní čísla: \(-10, -1, 0, \frac{1}{3}, \frac{5}{2}, 2,5, \pi, e, \sqrt{2}, 3, 1+2i, 3-10i, 2i\), ...',
		];

		foreach ($numberTypes as $type => $description) {
			$numberTypesHtml .= '<tr>'
				. '<th class="text-center">\(\mathbb{' . $type . '}\)</th>'
				. '<td>' . $description . '</td>'
				. '</tr>';
		}

		$step->setDescription('<table>' . $numberTypesHtml . '</table>', true);

		$steps[] = $step;

		$step = $this->stepFactory->create();
		$stepDescription = [];

		if ($number->isInteger()) {
			$text = 'Celé reálné číslo';
			$stepDescription[] = 'Je celé číslo.';

			if ($number->getInteger() > 0) {
				$text = 'Přirozené celé reálné číslo';
				$stepDescription[] = 'Je větší než nula.';
			}
		} else {
			$text = 'Iracionální reálné číslo';
			$stepDescription[] = 'Není celé číslo.';

			if ($number->getFloat() === round($number->getFloat(), 3)) {
				$text = 'Racionální reálné číslo (vyjádřitelné zlomkem)';
				$stepDescription[] = 'Má konečně dlouhý desetinný rozvoj (počet cifer za desetinnou čárkou), proto lze vyjádřit zlomkem.';
			}
		}

		$step->setTitle('Splněné předpoklady');
		$step->setDescription($stepDescription === []
			? ''
			: '<ul><li>' . implode('</li><li>', $stepDescription) . '</li></ul>',
			true
		);
		$steps[] = $step;

		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Číselný obor')
			->setText($text)
			->setSteps($steps);
	}

	/**
	 * @param int $currentYear
	 * @param int $year
	 */
	private function actionYear(int $currentYear, int $year): void
	{
		$diff = abs($currentYear - $year);
		$step = $this->stepFactory->create();
		$stepDescription = null;

		if ($diff === 0) {
			$text = 'Rok ' . $year . ' je právě teď.';
			$stepText = null;
			$stepDescription = 'Rok ' . $year . ' je podle kalendáře aktuální.';
		} elseif ($currentYear < $year) {
			$text = 'Za ' . Czech::inflection($diff, ['rok', 'roky', 'let']);
			$stepText = $year . ' - ' . $currentYear . ' = ' . $diff;
			$stepDescription = 'Od požadovaného roku odečteme aktuální rok.';
		} else {
			$text = 'Před ' . Czech::inflection($diff, ['rokem', 'lety', 'lety']);
			$stepText = $currentYear . '-' . $year . ' = ' . $diff;
			$stepDescription = 'Od aktuálního roku odečteme požadovaný rok.';
		}

		$step->setTitle('Řešení');
		$step->setDescription($stepDescription);
		$step->setLatex($stepText);

		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Čas od dnes')
			->setText($text)
			->setSteps([$step]);
	}

	/**
	 * @param string $int
	 */
	private function numberSystem(string $int): void
	{
		$bin[] = Strings::upper(decbin($int)) . '_{2}';
		$bin[] = Strings::upper(decoct($int)) . '_{8}';
		$bin[] = Strings::upper($int) . '_{10}';
		$bin[] = '\\text{' . Strings::upper(dechex($int)) . '}_{16}';

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Převod číselných soustav')
			->setText(implode("\n", $bin));
	}

	private function alternativeRewrite(): void
	{
		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Převod do Římských číslic')
			->setText(NumberHelper::intToRoman($this->number->getInteger()))
			->setSteps($this->romanToIntSteps->getIntToRomanSteps($this->number->getInteger()));
	}

	/**
	 * @param string $int
	 */
	private function timestamp(string $int): void
	{
		$currentTimestamp = \time();
		$dateDiff = abs($currentTimestamp - $int);

		$timestamp = '<b>' . DateTime::getDateTimeIso((int) $int) . '</b>'
			. '<br><br>'
			. ($currentTimestamp < $int
				? 'Bude za ' . $dateDiff . ' sekund (' . DateTime::formatTimeAgo($currentTimestamp - $dateDiff) . ')'
				: 'Bylo před ' . $dateDiff . ' sekundami (' . DateTime::formatTimeAgo($int) . ').'
			)
			. ((int) date('Y', $int) >= 2038
				? '<br><br>Pozor: Po roce 2038 nemusí tento timestamp fungovat na 32-bitových počítačích, protože překračuje maximální hodnotu, kterou je možné uložit do 32-bitového integeru.'
				: '');

		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Unix Timestamp')
			->setText($timestamp);
	}

	private function primeFactorization(): void
	{
		$int = $this->number->getInteger();
		$factors = $this->numberHelper->pfactor($int);

		if (\count($factors) === 1) {
			$this->addBox(Box::TYPE_TEXT)
				->setTitle('Prvočíselný rozklad')
				->setText('Číslo ' . $int . ' je prvočíslo, proto nelze dále rozložit.');
		} else {
			$outputFactor = '';
			$items = 0;

			foreach (array_count_values($factors) as $b => $e) {
				if ($outputFactor) {
					$outputFactor .= ' * ';
				}
				$items += $e;
				if (preg_match('/^(.+)E[+-]?(.+)$/', $b, $bParser)) {
					$outputFactor .= '\left({' . $bParser[1] . '}^{' . $bParser[2] . '}\right)';
				} else {
					$outputFactor .= $b . ($e > 1 ? '^{' . $e . '}' : '');
				}
			}

			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Prvočíselný rozklad | ' . Czech::inflection($items, ['člen', 'členy', 'členů']))
				->setText($outputFactor);
		}
	}

	private function divisors(): void
	{
		$int = $this->number->getInteger();
		$divisors = $this->sort($this->numberHelper->getDivisors($int));

		$divisor = ['!Dělitel'];
		$share = ['!Podíl'];

		for ($i = 0; isset($divisors[$i]); $i++) {
			$divisor[] = '=' . $divisors[$i] . '=';
			$share[] = '=' . ($int / $divisors[$i]) . '=';
		}

		$this->addBox(Box::TYPE_TABLE)
			->setTitle(
				'Dělitelé čísla ' . $int
				. ' | ' . Czech::inflection(\count($divisors), ['dělitel', 'dělitelé', 'dělitelů'])
				. ' | Součet: ' . array_sum($divisors)
			)
			->setTable([
				$divisor,
				$share,
			]);

		// TODO: 'hiddenContent' => 'Vlastnosti dělitelnosti'
	}

	/**
	 * @param int[]|string[] $array
	 * @return int[]|string[]
	 */
	private function sort(array $array): array
	{
		$toStr = [];

		foreach ($array as $item) {
			$toStr[] = (string) $item;
		}

		sort($toStr);

		return $toStr;
	}

	private function graphicInt(): void
	{
		$int = $this->number->getInteger();
		$render = '';
		for ($i = 1; $i <= $int; $i++) {
			$render .= '<div style="float: left; width: 8px; height: 8px; background: #EA4437; margin: 3px;"></div>';
		}

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Grafická reprezentace')
			->setText('<div style="overflow: auto;">' . $render . '</div>');
	}

	private function aboutPi(): void
	{
		$this->addBox(Box::TYPE_TEXT)
			->setTitle('Přibližná hodnota π | Ludolfovo číslo | Přesnost: 100')
			->setText('π ≈ ' . str_replace(' ', '<wbr> ', $this->numberHelper->getPi(100)) . ' …');
	}

	private function convertToFraction(): void
	{
		$factor = $this->number->getFraction();

		$this->addBox(Box::TYPE_LATEX)
			->setTitle('Zlomkový zápis | Nejlepší odhad')
			->setText('\frac{' . $factor[0] . '}{' . $factor[1] . '} ≈ ' . ($factor[0] / $factor[1]));

		if ($factor[0] > $factor[1] && Validators::isNumericInt($factor[0]) && Validators::isNumericInt($factor[1])) {
			$int = (int) floor($factor[0] / $factor[1]);
			$fraction = $factor[0] - $int * $factor[1];

			$this->addBox(Box::TYPE_LATEX)
				->setTitle('Složený zlomek')
				->setText($int . '\ \frac{' . $fraction . '}{' . $factor[1] . '}');
		}
	}

	private function bigNumber(string $int): void
	{
		$countNumbers = \strlen($int);
		$uniqueNumbers = \count(array_unique(str_split($int)));

		if ($uniqueNumbers <= 4 && $uniqueNumbers >= 2) {
			for ($i = $countNumbers - 1; $i >= 4; $i--) {
				if ($countNumbers % $i === 0 && $countNumbers / $i >= 4) {
					$this->addBox(Box::TYPE_HTML)
						->setTitle('Vizualizace | Rozměr: ' . $i . ' x ' . ($countNumbers / $i))
						->setText(
							$this->renderTable($int, $countNumbers / $i, $i)
						);
				}
			}
		}
	}

	/**
	 * @param string $data
	 * @param int $x
	 * @param int $y
	 * @return string
	 */
	private function renderTable(string $data, int $x, int $y): string
	{
		$return = '';
		$iterator = 0;
		$colors = ['green', 'black', 'red', 'blue'];
		$colorCache = [];

		for ($i = 0; $i < $x; $i++) {
			for ($j = 0; $j < $y; $j++) {
				$char = $data[$iterator];

				if (isset($colorCache[$char])) {
					$color = $colorCache[$char];
				} else {
					$color = $colorCache[$char] = $colors[\count($colorCache)];
				}

				$return .= '<span style="color:' . $color . '">' . $char . '</span>';
				$iterator++;
			}
			$return .= '<br>';
		}

		return $return;
	}

}