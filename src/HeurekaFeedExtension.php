<?php

declare(strict_types=1);

namespace Baraja\Heureka;


use Baraja\Markdown\CommonMarkRenderer;
use Nette\Application\Application;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\ClassType;

final class HeurekaFeedExtension extends CompilerExtension
{
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('heurekaManager'))
			->setFactory(HeurekaManager::class);

		$builder->addDefinition($this->prefix('categoryManager'))
			->setFactory(CategoryManager::class);

		$renderer = $builder->addDefinition($this->prefix('feedRenderer'))
			->setFactory(FeedRenderer::class);

		if (class_exists(CommonMarkRenderer::class)) {
			$descriptionRenderer = $builder->addDefinition($this->prefix('barajaCommonMarkDescriptionRenderer'))
				->setFactory(BarajaMarkdownDescriptionRenderer::class)
				->setAutowired(BarajaMarkdownDescriptionRenderer::class);

			$renderer->addSetup('?->setDescriptionRenderer(?)', ['@self', '@' . $descriptionRenderer->getName()]);
		}
	}


	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $application */
		$application = $builder->getDefinitionByType(Application::class);

		/** @var ServiceDefinition $heurekaManager */
		$heurekaManager = $builder->getDefinitionByType(HeurekaManager::class);

		$class->getMethod('initialize')->addBody(
			'// heureka feed.' . "\n"
			. '(function () {' . "\n"
			. "\t" . 'if ($this->getService(\'http.request\')->getUrl()->getRelativeUrl() === ?) {' . "\n"
			. "\t\t" . '$this->getService(?)->onStartup[] = function(' . Application::class . ' $a): void {' . "\n"
			. "\t\t\t" . '$this->getService(?)->render();' . "\n"
			. "\t\t" . '};' . "\n"
			. "\t" . '}' . "\n"
			. '})();', [
				'xml/heureka-feed.xml',
				$application->getName(),
				$heurekaManager->getName(),
			]
		);
	}
}
