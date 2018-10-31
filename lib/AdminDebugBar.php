<?php
namespace ZealByte\Debugger
{
	use Pimple\Container;
	use DebugBar\DataCollector\ExceptionsCollector;
	use DebugBar\DataCollector\MemoryCollector;
	use DebugBar\DataCollector\MessagesCollector;
	use DebugBar\DataCollector\PhpInfoCollector;
	use DebugBar\DataCollector\RequestDataCollector;
	use DebugBar\DataCollector\TimeDataCollector;

	/**
	 * Debug bar subclass which adds all included collectors
	 */
	class AdminDebugBar extends AbstractDebugBar
	{
		public function __construct (Container $app)
		{
			$this->applyDebuggerStorage($app);
			$this->addCollector(new PhpInfoCollector());
			$this->addCollector(new MessagesCollector());
			$this->addCollector(new RequestDataCollector());
			$this->addCollector(new TimeDataCollector());
			$this->addCollector(new MemoryCollector());
			$this->addCollector(new ExceptionsCollector());
		}

		public function applyTraceableCollectors (Container $app)
		{
			$this->applyMonologCollector($app);
			$this->applyDoctrineCollector($app);
			$this->applyTemplateCollector($app);
		}

	}
}
