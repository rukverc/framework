<?php

namespace Dappur\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Dappur Framework Controller
 * Auto generated by the Dappur CLI at: 2017-06-08 7:28 pm
 */
class Deploy extends Controller
{

	public function deploy(Request $request, Response $response)
	{

		if (!$this->settings['deployment']['enabled']) {
			$this->flash->addMessage('danger', 'Deployment is not enabled in the config.');
			$this->logger->addError("Deployment", array("message" => "Deployment is not enabled in the config."));
            return $response->withRedirect($this->router->pathFor('home'));
		}

		if ($this->settings['deployment']['repo_url'] == "") {
			$this->flash->addMessage('danger', 'Please specify a repo url in your deployment config.');
			$this->logger->addError("Deployment", array("message" => "Please specify a repo url in your deployment config."));
            return $response->withRedirect($this->router->pathFor('home'));
		}

		$deploy = new \Dappur\Dappurware\Deployment($this->settings['deployment']['repo_url'],$_SERVER['DOCUMENT_ROOT'],$_SERVER['HOME'],$this->settings['deployment']['repo_branch']);

		echo $deploy->execute();
		echo $deploy->updateDappur();

	}

}
