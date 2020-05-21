<?php

declare(strict_types=1);

namespace Mathematicator\Search\Translation;


use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorHelper
{

	/**
	 * @var TranslatorInterface
	 */
	private $translator;


	/**
	 * @var bool
	 */
	private $isInitialized = false;


	/**
	 * Available translations ordered by priority
	 *
	 * @var string[]
	 */
	private $languages = ['cs_CZ', 'en_US'];


	/**
	 * If no translation is available, than this languages are used instead.
	 *
	 * @var string[]
	 */
	private $fallbackLanguages = ['en_US', 'cs_CZ'];


	/**
	 * TranslatorHelper constructor.
	 * @param Translator $translator
	 */
	public function __construct(
		Translator $translator
	)
	{
		$this->translator = $translator;
	}


	public function init()
	{
		if ($this->isInitialized) {
			return;
		}

		$this->isInitialized = true;

		$this->translator->addLoader('yaml', new YamlFileLoader());

		foreach ($this->languages as $languageCode) {
			$this->translator->addResource(
				'yaml',
				__DIR__ . '/../../translations/translations.' . $languageCode . '.yml',
				$languageCode
			);
		}

		$this->translator->setFallbackLocales($this->fallbackLanguages);
		$this->translator->setLocale($this->languages[0]);
	}

}
