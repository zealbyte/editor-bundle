<?php

/*
 * This file is part of Twig.
 *
 * (c) 2015 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
namespace ZealByte\Debugger\Twig\Profiler\Dumper
{
	use Twig_Profiler_Profile;

	class CollectorDumper
	{
    private static $colors = array(
        'block' => '#dfd',
        'macro' => '#ddf',
        'template' => '#ffd',
        'big' => '#d44',
    );

		private $profile;
		private $root;
		private $hasCount = false;
		private $profileCount = 0;

		public function __construct (Twig_Profiler_Profile $profile)
		{
			$this->profile = $profile;
		}

		public function dump ()
		{
			return '<pre>'.$this->dumpProfile($this->profile).'</pre>';
		}

		public function numProfiles ()
		{
			if (!$this->hasCount) {
				$this->countProfiles($this->profile);
			}

			return $this->profileCount;
		}

		protected function countProfiles (Twig_Profiler_Profile $profile)
		{
			foreach ($profile as $i => $p) {
				if ($p->isTemplate()) {
					$this->profileCount++;
				}

				$this->countProfiles($p);
			}

			$this->hasCount = true;
		}

    protected function formatTemplate (Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ <span style="background-color: %s">%s</span>', $prefix, self::$colors['template'], $profile->getTemplate());
    }

    protected function formatNonTemplate (Twig_Profiler_Profile $profile, $prefix)
    {
        return sprintf('%s└ %s::%s(<span style="background-color: %s">%s</span>)', $prefix, $profile->getTemplate(), $profile->getType(), isset(self::$colors[$profile->getType()]) ? self::$colors[$profile->getType()] : 'auto', $profile->getName());
    }

    protected function formatTime (Twig_Profiler_Profile $profile, $percent)
    {
        return sprintf('<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::$colors['big'] : 'auto', $profile->getDuration() * 1000, $percent);
    }

		private function dumpProfile (Twig_Profiler_Profile $profile, $prefix = '', $sibling = false)
		{
			if ($profile->isRoot()) {
				$this->root = $profile->getDuration();
				$start = $profile->getName();
			} else {
				if ($profile->isTemplate()) {
					$start = $this->formatTemplate($profile, $prefix);
				} else {
					$start = $this->formatNonTemplate($profile, $prefix);
				}
				$prefix .= $sibling ? '│ ' : '  ';
			}

			$percent = $this->root ? $profile->getDuration() / $this->root * 100 : 0;

			$str = sprintf("%s %s\n", $start, $this->formatTime($profile, $percent));

			$nCount = count($profile->getProfiles());
			foreach ($profile as $i => $p) {
				// Count the profile if it is a template
				if ($p->isTemplate()) {
					$this->profileCount++;
				}

				$str .= $this->dumpProfile($p, $prefix, $i + 1 !== $nCount);
			}

			$this->hasCount = true;

			return $str;
		}
	}
}
