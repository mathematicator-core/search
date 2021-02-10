<?php

declare(strict_types=1);

namespace Mathematicator\Search\Controller;


use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Mathematicator\Engine\Controller\BaseController;
use Mathematicator\Engine\Entity\Box;
use Mathematicator\Engine\Entity\Source;
use Mathematicator\Statistics\StatisticsManager;

final class OEISController extends BaseController
{

	/** @var string[] */
	private static array $types = [
		'O' => 'Offset',
		'K' => 'Klíčová slova',
		'A' => 'Autor',
	];

	private StatisticsManager $statisticManager;


	public function __construct(StatisticsManager $statisticManager)
	{
		$this->statisticManager = $statisticManager;
	}


	public function actionDefault(): void
	{
		$this->setInterpret(Box::TYPE_HTML)
			->setText(
				'<a href="https://oeis.org/' . $this->getQuery() . '" target="_blank">'
				. $this->getQuery()
				. '</a>'
				. '<br>On-line Encyklopedie celočíselných posloupností',
			);

		try {
			$sequence = $this->statisticManager->getSequence($this->getQuery());
		} catch (NoResultException | NonUniqueResultException | EntityManagerException) {
			return;
		}

		$this->addBox(Box::TYPE_HTML)
			->setTitle('Posloupnost')
			->setText(implode(', ', $sequence->getSequence()) . ', ...');

		if (($formula = $sequence->getDataType('F')) !== null) {
			$this->addBox(Box::TYPE_HTML)
				->setTitle('Předpis')
				->setText($this->formatHr($formula));
		}
		if (($example = $sequence->getDataType('e')) !== null) {
			$this->addBox(Box::TYPE_HTML)
				->setTitle('Příklad')
				->setText($this->formatBr($example));
		}
		if (($comment = $sequence->getDataType('C')) !== null) {
			$this->addBox(Box::TYPE_HTML)
				->setTitle('Komentář')
				->setText($this->formatBr($comment));
		}
		if (($author = $sequence->getDataType('A')) !== null) {
			$source = new Source(
				'OEIS',
				'https://oeis.org/' . $sequence->getAId(),
				'On-line Encyklopedie celočíselných posloupností.',
			);
			$source->addAuthor($author);
			$this->addSource($source);
		}

		foreach (self::$types as $type => $label) {
			if (($data = $sequence->getDataType($type)) !== null) {
				$this->addBox(Box::TYPE_HTML)
					->setTitle($label)
					->setText(str_replace("\n", '<hr>', $this->formatLinks(htmlspecialchars($data))));
			}
		}
	}


	private function formatBr(string $data): string
	{
		$return = '';
		$lastPre = false;

		foreach (explode("\n", $this->formatLinks(htmlspecialchars($data))) as $line) {
			if (str_contains($line, '   ')) {
				$return .= ($lastPre ? '' : '<pre class="p-2 my-2" style="border:1px solid #aaa">') . $line . "\n";
				$lastPre = true;
			} else {
				$return .= ($lastPre ? '</pre>' : '') . '<p>' . $line . '</p>';
				$lastPre = false;
			}
		}

		return $return;
	}


	private function formatHr(string $data): string
	{
		return '<div class="text-center p-2 mt-2" style="background:#eee">'
			. str_replace("\n", '<hr>', $this->formatLinks(htmlspecialchars($data)))
			. '</div>';
	}


	private function formatLinks(string $data): string
	{
		return (string) preg_replace_callback('/\s(A\d{6})\s/', fn (array $row): string => ' <a href="' . $this->linkToSearch($row[1]) . '">' . $row[1] . '</a> ', $data);
	}
}
