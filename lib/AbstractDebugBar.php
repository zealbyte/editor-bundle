<?php
namespace ZealByte\Debugger
{
	use Pimple\Container;
	use Doctrine\DBAL\Logging\DebugStack;
	use DebugBar\DebugBar;
	use DebugBar\Storage\FileStorage;
	use DebugBar\DataCollector\ExceptionsCollector;
	use DebugBar\DataCollector\MemoryCollector;
	use DebugBar\DataCollector\MessagesCollector;
	use DebugBar\DataCollector\PhpInfoCollector;
	use DebugBar\DataCollector\RequestDataCollector;
	use DebugBar\DataCollector\TimeDataCollector;
	use DebugBar\Bridge\MonologCollector;
	use DebugBar\Bridge\Twig\TraceableTwigEnvironment;
	use DebugBar\Bridge\Twig\TwigCollector;
	use DebugBar\Bridge\DoctrineCollector;
	use ZealByte\Debugger\DataCollector\ProfilerCollector;

	/**
	 * Debug bar subclass which adds all included collectors
	 */
	abstract class AbstractDebugBar extends DebugBar
	{
		private $useStorage = false;
		private $twigProfiler;

		public function __construct (Container $app)
		{
		}

		public function applyTraceableCollectors (Container $app)
		{
		}

		public function applyZealByteExtensions (Container $app)
		{
		}

		public function getTwigProfiler ()
		{
			return $this->twigProfiler;
		}

		protected function applyDebuggerStorage (Container $app)
		{
			if ($app['debugger.storage']) {
				$this->setStorage(new FileStorage($app['debugger.storage']));
				$this->useStorage = true;
				//$this->stackData();
				//$this->sendDataInHeaders(true);
			}
		}

		protected function applyMonologCollector (Container $app)
		{
			if (isset($app['monolog'])) {
				$app['debugger']['messages']->aggregate(new MonologCollector($app['monolog']));
			}
		}

		protected function applyDoctrineCollector (Container $app)
		{
			if (isset($app['db'])) {
				$debugStack = new DebugStack();
				$app['db']->getConfiguration()->setSQLLogger($debugStack);
				$this->addCollector(new DoctrineCollector($debugStack));
			}
		}

		protected function applyTemplateCollector (Container $app)
		{
			if (isset($app['twig'])) {
				$app->extend('twig', function($twig, $app) {
					$this->twigProfiler = new \Twig_Profiler_Profile();
					$twig->addExtension(new \Twig_Extension_Profiler($this->twigProfiler));

					$this->addCollector(new ProfilerCollector($this->twigProfiler));

					return $twig;
				});
			}
		}

	}
}
