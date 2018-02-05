<?php

namespace Pingdom;

/**
 * Client object for executing commands on a web service.
 */
class Client
{
	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $token;

    /**
     * @var string
     *
     * See: https://www.pingdom.com/resources/api/2.1#multi-user+authentication
     */
    private $accountemail;

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $token
     * @param string accountemail
     *
	 * @return Client
	 */
	public function __construct($username, $password, $token, $accountemail="")
	{
		$this->username     = $username;
		$this->password     = $password;
		$this->token        = $token;
		$this->accountemail = $accountemail;

		return $this;
	}

	/**
	 * Returns the username.
	 *
	 * @return string
	 */
	protected function getUsername()
	{
	  return $this->username;
	}

	/**
	 * Returns the password.
	 *
	 * @return string
	 */
	protected function getPassword()
	{
	  return $this->password;
	}

	/**
	 * Returns the token.
	 *
	 * @return string
	 */
	protected function getToken()
	{
	  return $this->token;
	}

    /**
     * Returns the account-email.
     *
     * @return string
     */
    protected function getAccountemail()
    {
        return $this->accountemail;
    }

    /**
     * Generates and returns the appropriate headers.
     *
     * @return array
     */
    protected function generateHeaders()
    {
        if ($this->accountemail == "")
        {
            return array('App-Key' => $this->token);
        }
        else
        {
            return array(
                'App-Key' => $this->token,
                'User-Agent' => 'curl/7.54.0',
                'Account-Email' => $this->accountemail,
                'Accept' => '*/*',
            );
        }
    }

	/**
	 * Returns a list overview of all checks
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getChecks()
	{
		$client = new \Guzzle\Service\Client('https://api.pingdom.com/api/2.1');

		$headers = $this->generateHeaders();

		/** @var $request \Guzzle\Http\Message\Request */
		$request = $client->get('checks', $headers, array('debug' => true) );
		$request->setAuth($this->username, $this->password);
        try {
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            echo 'Message: ' . $e->getMessage();
            echo 'URL: ' . $e->getRequest()->getUrl() . "\n";
            echo 'Request: ' . $e->getRequest() . "\n";
            echo 'Status Code: ' . $e->getResponse()->getStatusCode() . "\n";
            echo 'Response: ' . $e->getResponse() . "\n";
        }

		$response = json_decode($response->getBody(), true);

		return $response['checks'];
	}

	/**
	 * Returns a list of all Pingdom probe servers
	 *
	 * @return Probe\Server[]
	 */
	public function getProbes()
	{
		$client = new \Guzzle\Service\Client('https://api.pingdom.com/api/2.1');

		/** @var $request \Guzzle\Http\Message\Request */
		$request = $client->get('probes', $this->generateHeaders());
		$request->setAuth($this->username, $this->password);
		$response = $request->send();
		$response = json_decode($response->getBody(), true);
		$probes   = array();

		foreach ($response['probes'] as $attributes) {
			$probes[] = new Probe\Server($attributes);
		}

		return $probes;
	}

	/**
	 * Return a list of raw test results for a specified check
	 *
	 * @param int        $checkId
	 * @param int        $limit
	 * @param array|null $probes
	 * @return array
	 */
	public function getResults($checkId, $limit = 100, array $probes = null)
	{
		$client = new \Guzzle\Service\Client('https://api.pingdom.com/api/2.1');

		/** @var $request \Guzzle\Http\Message\Request */
		$request = $client->get('results/' . $checkId, $this->generateHeaders());
		$request->setAuth($this->username, $this->password);
		$request->getQuery()->set('limit', $limit);

		if (is_array($probes)) {
			$request->getQuery()->set('probes', implode(',', $probes));
		}

		$response = $request->send();
		$response = json_decode($response->getBody(), true);

		return $response['results'];
	}

	/**
	 * Get Intervals of Average Response Time and Uptime During a Given Interval
	 *
	 * @param int $checkId
	 * @param string $resolution
	 * @return array
	 */
	public function getPerformanceSummary($checkId, $resolution = 'hour')
	{
		$client = new \Guzzle\Service\Client('https://api.pingdom.com/api/2.1');

		/** @var $request \Guzzle\Http\Message\Request */
		$request = $client->get('summary.performance/' . $checkId, $this->generateHeaders());
		$request->setAuth($this->username, $this->password);
		$request->getQuery()->set('resolution', $resolution);
		$request->getQuery()->set('includeuptime', 'true');

		$response = $request->send();
		$response = json_decode($response->getBody(), true);

		return $response['summary'][$resolution . 's'];
	}
}
