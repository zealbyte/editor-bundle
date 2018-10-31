<?php
namespace ZealByte\Provider
{
	use Pimple\Container;
	use Pimple\ServiceProviderInterface;
	use Silex\Application;
	use Silex\ControllerCollection;
	use Silex\Api\BootableProviderInterface;
	use Silex\Api\ControllerProviderInterface;
	use Silex\Api\EventListenerProviderInterface;
	use Symfony\Component\EventDispatcher\EventDispatcherInterface;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use ZealByte\Debugger\Controller;
	use ZealByte\Debugger\AdminDebugBar;
	use ZealByte\Debugger\UserDebugBar;
	use ZealByte\Debugger\EventListener\DebuggerEventListener;

	/**
	 * DebugBar Provider
	 *
	 * Class DebugBarProvider
	 * @package Dongww\SilexBase\Provider
	 */
	class DebuggerServiceProvider implements  ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface, ControllerProviderInterface
	{
		public function register (Container $app)
		{
			$app['debugger.storage'] = null;
			$app['debugger.render'] = false;
			$app['debugger.path'] = '/debugger';

			$app['debugger'] = function (Container $app) {
				return new AdminDebugBar($app);
			};

			$app['debugger.controllers.debugger'] = function (Container $app) {
				return new Controller\DebuggerController();
			};
		}

		public function boot (Application $app)
		{
			$app['debugger']->applyTraceableCollectors($app);
			$app['debugger']->applyZealByteExtensions($app);

			$app->mount($app['debugger.path'], $this);
		}

		/**
		 * @inheritdoc
		 */
		public function connect (Application $app)
		{
			$factory = $app['controllers_factory'];

			$this->connectDebugResourceAction($factory);
			$this->connectDebugOpenHandlerAction($factory);
			$this->connectPhpInfoAction($factory);

			return $factory;
		}

		public function subscribe (Container $app, EventDispatcherInterface $dispatcher)
		{
			$subscriber = (new DebuggerEventListener($app))
				->setResourceRouteName('zealbyte.debugbar.resource')
				->setFileRouteName('zealbyte.debugbar.open')
				->setUrlGenerator($app['url_generator']);

			$dispatcher->addSubscriber($subscriber);
		}

		private function connectDebugOpenHandlerAction (ControllerCollection $factory) : void
		{
			$factory->get('/', 'debugger.controllers.debugger:openAction')
				->bind('zealbyte.debugbar.open');
		}

		private function connectDebugResourceAction (ControllerCollection $factory) : void
		{
			$factory->get('/resource/{resource}', 'debugger.controllers.debugger:resourceAction')
				->bind('zealbyte.debugbar.resource')
				->assert('resource', '.+');
		}

		private function connectPhpInfoAction (ControllerCollection $factory) : void
		{
			$factory->get('/info', 'debugger.controllers.debugger:phpInfoAction')
				->bind('zealbyte.debugbar.info');
		}
	}
}
