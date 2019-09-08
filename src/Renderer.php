<?php

declare(strict_types=1);

namespace Mathematicator\Search;


use Mathematicator\Engine\MathematicatorException;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class Renderer
{

	/**
	 * @param mixed $data
	 * @param string $type
	 * @return string
	 * @throws MathematicatorException
	 */
	public function render($data, string $type): string
	{
		static $services = [
			Box::TYPE_TEXT => 'renderText',
			Box::TYPE_LATEX => 'renderLatex',
			Box::TYPE_HTML => 'renderHtml',
			Box::TYPE_KEYWORD => 'renderKeyword',
			Box::TYPE_IMAGE => 'renderImage',
			Box::TYPE_TABLE => 'renderTable',
		];

		if (isset($services[$type])) {
			return $this->{$services[$type]}($data);
		}

		throw new MathematicatorException('Unknown box type "' . $type . '"');
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function renderTable(string $data): string
	{
		$return = '';

		foreach (\json_decode($data) as $row) {
			$return .= '<tr>';
			foreach ($row as $column) {
				if (Strings::startsWith($column, '!')) {
					$return .= '<th style="text-align:right;max-width:200px">' . preg_replace('/^!/', '', $column) . '</th>';
				} else {
					$return .= '<td>'
						. preg_replace_callback(
							'/^(?<left>[=])?(?<content>.+?)(?<right>[=])?$/',
							static function (array $row): string {
								if ($row['left'] === $row['right']) {
									if ($row['left'] === '=') {
										return '<div style="text-align:center">' . $row['content'] . '</div>';
									}

									return (string) $row['content'];
								}

								return (string) $row[0];
							}, $column)
						. '</td>';
				}
			}
			$return .= '</tr>';
		}

		return '<table>' . $return . '</table>';
	}

	/**
	 * @param string $title
	 * @return string
	 */
	public function renderTitle(string $title): string
	{
		$title = $title ?? 'Box bez názvu';
		$return = '';
		$iterator = 0;

		foreach (explode('|', $title) as $item) {
			$item = trim($item);
			if ($iterator > 0 && preg_match('/.+\:\s+.+/', $item, $itemParser)) {
				$return .= '<span class="search-box-header-hightlight">' . $item . '</span>';
			} else {
				$return .= '<span class="search-box-header-text">' . $item . '</span>';
			}

			$iterator++;
		}

		return $return;
	}

	/**
	 * @internal
	 * @param string $data
	 * @return string
	 */
	public function renderText(string $data): string
	{
		return TextRenderer::process($data);
	}

	/**
	 * @internal
	 * @param string $data
	 * @return string
	 */
	public function renderLatex(string $data): string
	{
		$return = '';

		foreach (explode("\n", $data) as $line) {
			if (Validators::isNumeric($line)) {
				$return .= '<div>' . str_replace('\ ', '&nbsp;', $this->numberFormat($line)) . '</div>';
			} else {
				$return .= '<div>\(' . preg_replace_callback('/(-?\d*[.]?\d+)/', function ($number) {
						return $this->numberFormat($number[1]);
					}, $line) . '\)</div>';
			}
		}

		return $return;
	}

	/**
	 * @internal
	 * @param string $data
	 * @return string
	 */
	public function renderKeyword(string $data): string
	{
		$return = '';

		foreach (explode(';', $data) as $item) {
			$return .= '<span style="margin:.25em;border:1px solid #E6CF67;background:#FFF2BF;padding:.25em .5em;color:#735E00;display:inline-block">'
				. htmlspecialchars(trim($item))
				. '</span>';
		}

		return $return;
	}

	/**
	 * @internal
	 * @param string $data
	 * @return string
	 */
	public function renderImage(string $data): string
	{
		if (strncmp($data, 'data:', 5) === 0) {
			return '<img src="' . $data . '">';
		}

		return $this->renderText($data);
	}

	/**
	 * @internal
	 * @param string $number
	 * @param bool $isLookLeft
	 * @return string
	 */
	public function numberFormat(string $number, bool $isLookLeft = true): string
	{
		$return = null;

		if (\strlen($number) <= 3) {
			$return = $number;
		} elseif (preg_match('/^-?\d+\z/', $number)) {
			$return = '';

			if ($isLookLeft === true) {
				while (true) {
					if (preg_match('/^(\d+)(\d{3})$/', $number, $temp)) {
						$number = $temp[1];
						$return = $temp[2] . '\ ' . $return;
					} else {
						$return = $number . '\ ' . $return;
						break;
					}
				}
			} else {
				while (true) {
					if (preg_match('/^(\d{3})(\d+)$/', $number, $temp)) {
						$number = $temp[2];
						$return = $return . '\ ' . $temp[1];
					} else {
						$return = $return . '\ ' . $number;
						break;
					}
				}
			}
		} elseif (preg_match('/^0*(?<left>.+?)\.(?<right>.+?)0*$/', $number, $parser)) {
			$return = (string) preg_replace('/\.0*$/', '',
				$this->numberFormat($parser['left'])
				. '.' . $this->numberFormat($parser['right'], false)
			);
		} else {
			$formattedNumber = (string) preg_replace('/\.0+$/', '', number_format((float) $number, 64, '.', ' '));

			$return = $formattedNumber === 'inf'
				? (string) preg_replace('/(\d{3})/', '$1 ', $number)
				: $formattedNumber;
		}

		return $return === null ? $number : (string) preg_replace('/(^\\\\\s*)|(\\\\\s*$)/', '', $return);
	}

	/**
	 * @internal
	 * @param string $data
	 * @return string
	 */
	public function renderHtml(string $data): string
	{
		// TODO: Implement automatic escaping and tag-whitelist!
		return $data;
	}

}
