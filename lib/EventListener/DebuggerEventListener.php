<?php
namespace ZealByte\Debugger\EventListener
{
	use Pimple\Container;
	use Symfony\Component\EventDispatcher\EventSubscriberInterface;
	use Symfony\Component\HttpKernel\KernelEvents;
	use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
	use Symfony\Component\HttpKernel\Event\GetResponseEvent;
	use Symfony\Component\Asset\Package;
	use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
	use ZealByte\Debugger\OpenHandler;
	use ZealByte\Message\MessageEvents;
	use ZealByte\Message\Event\ActionMessageEvent;
	use ZealByte\Message\Event\AuditMessageEvent;
	use ZealByte\Message\Event\ErrorMessageEvent;
	use ZealByte\Message\Event\ExceptionMessageEvent;
	use ZealByte\Message\Event\InfoMessageEvent;
	use ZealByte\Message\Event\NoticeMessageEvent;
	use ZealByte\Message\Event\SuccessMessageEvent;
	use ZealByte\Message\Event\WarningMessageEvent;
	use ZealByte\Util;

	class DebuggerEventListener implements EventSubscriberInterface
	{
		private $url_generator;

		private $file_route_name;

		private $resource_route_name;

		protected $app;

		public static function getSubscribedEvents()
		{
			return [
				KernelEvents::REQUEST => [
					['onKernelRequest', -1000]
				],
				KernelEvents::RESPONSE => [
					['onKernelResponse', -1000]
				],
				MessageEvents::ACTION  => [
					'onMessageAction'
				],
				MessageEvents::AUDIT  => [
					'onMessageAudit'
				],
				MessageEvents::ERROR  => [
					'onMessageError'
				],
				MessageEvents::EXCEPTION => [
					'onMessageException'
				],
				MessageEvents::INFO => [
					'onMessageInfo'
				],
				MessageEvents::NOTICE => [
					'onMessageNotice'
				],
				MessageEvents::SUCCESS => [
					'onMessageSuccess'
				],
				MessageEvents::WARNING => [
					'onMessageWarning'
				],
			];
		}

		public function __construct (Container $app)
		{
			$this->app = $app;
		}

		public function setFileRouteName (string $fileRouteName) : self
		{
			$this->file_route_name = $fileRouteName;

			return $this;
		}

		public function setResourceRouteName (string $resourceRouteName) : self
		{
			$this->resource_route_name = $resourceRouteName;

			return $this;
		}

		public function setUrlGenerator ($url_generator) : self
		{
			$this->url_generator = $url_generator;

			return $this;
		}

		public function onMessageAction (ActionMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'action');
			//$this->app['debugger']['messages']->addMessage($event->getRequest(), 'success');
		}

		public function onMessageAudit (AuditMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'audit');
		}

		public function onMessageError (ErrorMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'error');
		}

		public function onMessageException (ExceptionMessageEvent $event)
		{
			$message = $event->getException()->getCode().' :: '
				.$event->getMessage()->getSummary();

			$this->app['monolog']->error($event->getMessage()->getFullMessage());
			$this->app['debugger']['messages']->addMessage($message, 'error');
			$this->app['debugger']['exceptions']->addException($event->getException());
		}

		public function onMessageInfo (InfoMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'info');
		}

		public function onMessageNotice (NoticeMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'notice');
		}

		public function onMessageSuccess (SuccessMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'success');
		}

		public function onMessageWarning (WarningMessageEvent $event)
		{
			$this->app['debugger']['messages']->addMessage($event->getMessage()->getFullMessage(), 'warning');
		}

		/**
		 *
		 * @param GetResponseEvent $event
		 */
		public function onKernelRequest (GetResponseEvent $event)
		{
			$file_path = $this->url_generator->generate($this->file_route_name);
			$renderer = $this->app['debugger']->getJavascriptRenderer();

			$renderer->setOpenHandlerUrl($file_path);
		}

		/**
		 *
		 * @param FilterResponseEvent $event
		 */
		public function onKernelResponse (FilterResponseEvent $event)
		{
			$assets = [];
			$response = $event->getResponse();

			if (!$this->app['debugger.render'])
				return;

			if (!$event->isMasterRequest())
				return;

			if (!Util\RequestUtil::isPageRequest($event->getRequest(), $response))
				return;

			$renderer = $this->app['debugger']->getJavascriptRenderer();
			$renderer->setIncludeVendors('css');
			$renderer->setEnableJqueryNoConflict(true);

			$resourcePath = $renderer->getBasePath();
			list($css, $js) = $renderer->getAssets();

			foreach($css as $resource) {
				$resource = trim(str_replace($resourcePath, '', $resource), '/');
				$href = $this->url_generator->generate($this->resource_route_name, ['resource' => $resource]);
				$assets[] = "<link rel=\"stylesheet\" href=\"$href\">";
			}

			foreach($js as $resource) {
				$resource = trim(str_replace($resourcePath, '', $resource), '/');
				$src = $this->url_generator->generate($this->resource_route_name, ['resource' => $resource]);
				$assets[] = "<script src=\"$src\" type=\"text/javascript\"></script>";
			}

			$resource = 'js/zealbyte.debugger.js';
			$src = $this->url_generator->generate('assets', ['path' => $resource]);
			$assets[] = "<script src=\"$src\" type=\"text/javascript\"></script>";

			$content = $response->getContent();

			if (false !== strpos($content, '</body>'))
				$content = str_replace('</body>', implode("\n", $assets) . "\n" . $renderer->render() . '</body>', $content);

			$response->setContent($content);

			return;
		}

	}
}
