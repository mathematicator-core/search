<?php

declare(strict_types=1);

namespace Mathematicator\Engine\Tests;


use Mathematicator\Engine\Helpers;
use Mathematicator\Search\Translation\TranslatorHelper;
use Nette\DI\Container;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class TranslationTest extends TestCase
{
	/**
	 * @var TranslatorInterface
	 */
	private $translator;


	public function __construct(
		Container $container
	)
	{
		$this->translator = $container->getByType(Translator::class);
		$translatorHelper = $container->getByType(TranslatorHelper::class);
		$translatorHelper->init();
	}

	public function testTranslate(): void
	{
		// Check simple translation
		Assert::same('Řešení', $this->translator->trans('solution', [], null, 'cs_CZ'));

		// Check translation with parameter
		Assert::same('"5" cannot be divided by zero.', $this->translator->trans('divisionByZeroDesc', ['%number%' => 5], null, 'en_US'));

		// Check default language
		Assert::same('Řešení', $this->translator->trans('solution'));
	}

}

$container = (new Bootstrap())::boot();
(new TranslationTest($container))->run();
