<?php

namespace kostikpenzin\impaya;

/**
 * ImpayaMerchant
 */
class ImpayaMerchant
{
    private $api_url;
    private $terminalKey;
    private $secretKey;
    private $dev;
    private $paymentId;
    private $status;
    private $error;
    private $response;
    private $paymentUrl;


    /**
     * __construct
     *
     * @param  string $terminalKey
     * @param  string $secretKey
     * @param  bool $dev
     * @return void
     */
    public function __construct(string $terminalKey, string $secretKey, bool $dev=false)
    {
        $this->api_url = ($dev==false) ? 'https://api.impaya.ru/' : 'https://api-stage.impaya.ru/';
        $this->dev = $dev;
        $this->terminalKey = $terminalKey;
        $this->secretKey = $secretKey;
    }

    /**
     * __get
     *
     * @param  mixed $name
     * @return void
     */
    public function __get($name)
    {
        switch ($name) {
            case 'response':
                return htmlentities($this->response);
            default:
                if ($this->response) {
                    if ($json = json_decode($this->response, true)) {
                        foreach ($json as $key => $value) {
                            if (strtolower($name) == strtolower($key)) {
                                return $json[$key];
                            }
                        }
                    }
                }

                return false;
        }
    }

    /**
     * session
     *
     * @param  mixed $args
     * @return void
     */
    public function session($args)
    {
        return $this->buildQuery('session', $args);
    }


    /**
     * Builds a query string and call sendRequest method.
     * Could be used to custom API call method.
     *
     * @param string $path API method name
     * @param mixed $args query params
     *
     * @return mixed
     * @throws HttpException
     */
    public function buildQuery($path, $args)
    {
        $url = $this->api_url;
        if (is_array($args)) {
            if (!array_key_exists('key', $args)) {
                $args['key'] = $this->terminalKey;
            }
            if (!array_key_exists('terminal_password', $args)) {
                $args['credential']['terminal_password'] = $this->secretKey;
            }
        }
        $url = $this->_combineUrl($url, $path);


        return $this->_sendRequest($url, $args);
    }

    /**
     * Combines parts of URL. Simply gets all parameters and puts '/' between
     *
     * @return string
     */
    private function _combineUrl()
    {
        $args = func_get_args();
        $url = '';
        foreach ($args as $arg) {
            if (is_string($arg)) {
                if ($arg[strlen($arg) - 1] !== '/') $arg .= '/';
                $url .= $arg;
            } else {
                continue;
            }
        }

        return $url;
    }

    /**
     * Main method. Call API with params
     *
     * @param $api_url
     * @param $args
     * @return bool|string
     * @throws HttpException
     */
    private function _sendRequest($api_url, $args)
    {
        $this->error = '';
        if (is_array($args)) {
            $args = json_encode($args);
        }

        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $api_url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            ));

            $out = curl_exec($curl);
            $this->response = $out;
            $json = json_decode($out);

            /*
            if ($json) {
                if (@$json->ErrorCode !== "0") {
                    $this->error = @$json->Details;
                } else {
                    $this->paymentUrl = @$json->PaymentURL;
                    $this->paymentId = @$json->PaymentId;
                    $this->status = @$json->Status;
                }
            }
            */

            curl_close($curl);

            return $out;
        } else {
            throw new \Exception('Can not create connection to ' . $api_url . ' with args ' . $args, 404);
        }
    }
}
