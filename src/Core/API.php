<?php

namespace Iidev\MetaConversionAPI\Core;

use XLite\Core\Config;
use XLite\InjectLoggerTrait;
use Exception;

class API
{
    use InjectLoggerTrait;

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $pixelId = Config::getInstance()->Iidev->MetaConversionAPI->pixel_id;
        $accessToken = Config::getInstance()->Iidev->MetaConversionAPI->access_token;

        return "https://graph.facebook.com/v23.0/{$pixelId}/events?access_token={$accessToken}";
    }

    public function isDebugMode() {
        return (bool) Config::getInstance()->Iidev->MetaConversionAPI->is_debug;
    }

    protected function isSuccessfulCode($code)
    {
        return in_array((int) $code, [200, 201, 202, 204], true);
    }

    public function event($data)
    {
        if($this->isDebugMode()) {
            $data['test_event_code'] = Config::getInstance()->Iidev->MetaConversionAPI->test_event_code;
        }

        $result = $this->doRequest($data);

        return $this->isSuccessfulCode($result->code);
    }

    /**
     * @param array $data
     *
     * @return \PEAR2\HTTP\Request\Response
     * @throws \Exception
     */
    protected function doRequest($data = [])
    {
        $data = json_encode($data);

        $url = $this->getUrl();

        $request = new \XLite\Core\HTTP\Request($url);

        $request->verb = "POST";

        $request->setHeader('Accept', 'application/json');
        $request->setHeader('Content-Type', 'application/json');

        $request->body = $data;

        $response = $request->sendRequest();

        if($this->isDebugMode()) {
            $this->getLogger('Meta Conversion API')->error('Debug data', [
                'url' => $url,
                'body' => $data,
                'response_code' => $response->code,
                'response_body' => $response->body
            ]);
        }

        if ($response->code === 409) {
            return $response;
        }

        if (!$response || !$this->isSuccessfulCode($response->code)) {
            $this->getLogger('Meta Conversion API')->error(__FUNCTION__ . 'Response error', [
                $response->body,
                $response->code
            ]);
        }

        return $response;
    }
}
