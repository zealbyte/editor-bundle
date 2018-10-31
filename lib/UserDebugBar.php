<?php
namespace ZealByte\Debugger
{
	use Pimple\Container;
	use DebugBar\DataCollector\MessagesCollector;
	use DebugBar\DataCollector\PhpInfoCollector;

	/**
	 * Debug bar subclass which adds all included collectors
	 */
	class UserDebugBar extends AbstractDebugBar
	{
		public function __construct (Container $app)
		{
			$this->applyDebuggerStorage($app);
			$this->addCollector(new PhpInfoCollector());
			$this->addCollector(new MessagesCollector());
		}

	}
}
