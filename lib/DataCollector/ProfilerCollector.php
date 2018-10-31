<?php
namespace ZealByte\Debugger\DataCollector
{
	use DebugBar\DataCollector\DataCollector;
	use DebugBar\DataCollector\Renderable;
	use Twig_Profiler_Profile;
	use ZealByte\Debugger\Twig\Profiler\Dumper\CollectorDumper;

	class ProfilerCollector extends DataCollector implements Renderable
	{
		private $twigProfile;

		public function __construct (Twig_Profiler_Profile $twigProfile)
		{
			$this->twigProfile = $twigProfile;
		}

		public function collect ()
		{
			$dumper = new CollectorDumper($this->twigProfile);

			return [
				'html' => $dumper->dump(),
				'tpls_rendered' => $dumper->numProfiles(),
			];
		}

		public function getName ()
		{
			return 'profiler';
		}

		public function getTitle ()
		{
			return 'Templates';
		}

		public function getWidgets ()
		{
			$name = $this->getName();
			$title = $this->getTitle();

			return [
				$name => [
					'icon' => 'inbox',
					'title' => $title,
					'widget' => 'ProfilerWidget',
					'map' => "$name.html",
					'default' => '[]',
				],
				"$name:badge" => [
					'map' => "$name.tpls_rendered",
					'default' => '0',
				],
			];
		}

	}
}
