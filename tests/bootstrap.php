<?php

declare(strict_types=1);

namespace Mathematicator\Engine\Tests;


require __DIR__ . '/../vendor/autoload.php';

use FrontModule\LinkGeneratorMock;
use Nette\Configurator;
use Nette\DI\Container;
use Tester\Environment;

Environment::setup();

class Bootstrap
{
	public static function boot(): Container
	{
		$configurator = new Configurator();

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__ . '/../src')
			->addDirectory(__DIR__ . '/Mocks')
			->register();

		$configurator
			/**
			 * TODO: common.neon fails: Nette\DI\InvalidConfigurationException: Found section 'orm.annotations'
			 * in configuration, but corresponding extension is missing.
			 */
			->addConfig(__DIR__ . '/../common.neon');

		$container = $configurator->createContainer();

		return $container;
	}
}
