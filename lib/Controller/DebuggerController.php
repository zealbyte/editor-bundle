<?php
namespace ZealByte\Debugger\Controller
{
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpFoundation\JsonResponse;
	use Symfony\Component\HttpFoundation\RedirectResponse;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use DebugBar\DebugBar;
	use DebugBar\DebugBarException;
	use ZealByte\Util;

	class DebuggerController
	{
		public function resourceAction (Application $app, Request $request, string $resource = null)
		{
			if ($resource) {
				$path = $app['debugger']->getJavascriptRenderer()->getBasePath() . '/' . $resource;
				$pathinfo = pathinfo($path);
				$mime = Util\MediaType::findMimeByExtension($pathinfo['extension'], ['web']);

				if ($mime)
					return $app->sendFile($path, 200, ['Content-Type' => $mime]);
			}

			throw new NotFoundHttpException();
		}

		public function phpInfoAction (Application $app, Request $request)
		{
			ob_start();
			phpinfo();
			$content = ob_get_contents();
			ob_end_clean();

			return new Response($content, 200);
		}

		public function openAction (Application $app, Request $request) : Response
		{
			$op = $request->query->get('op');

			switch (Util\Canonical::param((string) $op)) {
				case 'get':
					return $this->getAction($app, $request);
				case 'clear':
					return $this->clearAction($app, $request);
				default:
					return $this->findAction($app, $request);
			}

			throw new NotFoundHttpException();
		}

		/**
		 * Find operation
		 */
		public function findAction (Application $app, Request $request) : Response
		{
			if (!isset($app['debugger']) || !$app['debugger']->isDataPersisted())
				throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");

			$max = 20;
			$offset = 0;
			$filters = [];

			if ($request->get('max'))
				$max = $request->get('max');

			if ($request->get('offset'))
				$offset = $request->get('offset');

			foreach (['utime', 'datetime', 'ip', 'uri', 'method'] as $key)
				if ($request->get($key))
					$filters[$key] = $request->get($key);

			return new JsonResponse($app['debugger']->getStorage()->find($filters, $max, $offset), Response::HTTP_OK);
		}

		/**
		 * Get operation
		 */
		public function getAction (Application $app, Request $request) : Response
		{
			if (!isset($app['debugger']) || !$app['debugger']->isDataPersisted())
				throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");

			if (!$request->get('id'))
				throw new DebugBarException("Missing 'id' parameter in 'get' operation");

			$id = $request->get('id');

			return new JsonResponse($app['debugger']->getStorage()->get($id), Response::HTTP_OK);
		}

		/**
		 * Clear operation
		 */
		public function clearAction (Application $app, Request $request) : Response
		{
			if (!isset($app['debugger']) || !$app['debugger']->isDataPersisted())
				throw new DebugBarException("DebugBar must have a storage backend to use OpenHandler");

			$success = $app['debugger']->getStorage()->clear();

			return new JsonResponse(['success' => $success], Response::HTTP_OK);
		}
	}
}
