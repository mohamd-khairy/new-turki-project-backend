<?php

namespace App\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpServices
{
    protected $client;

    protected $headers = [];

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Send GET request to endpoint
     *
     * @param $url
     * @param $parameters
     * @param array $headers
     *
     * @return mixed|string
     * @throws GuzzleException
     */
    public function get($url, $parameters, $headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this->request('get', $url, $parameters);
    }

    public function delete($url, $parameters, $headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this->request('delete', $url, $parameters);
    }

    /**
     * Send POST request to endpoint
     *
     * @param $endpoint
     * @param $postData
     * @param array $headers
     *
     * @param string $postType
     * @return mixed|string
     * @throws GuzzleException
     */
    public function post($endpoint, $postData, $headers = [], $postType = 'form_params')
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this->request('post', $endpoint, $postData, $postType);
    }

    /**
     * Send Request to Url
     *
     * @param $method
     * @param $endpoint
     * @param $data
     *
     * @param string $postType
     * @return mixed|string
     * @throws GuzzleException
     */
    public function request($method, $endpoint, $data, $postType = 'form_params')
    {
        $dataParamNames = ['get' => 'query', 'post' => $postType, 'delete' => 'query'];
        $options[$dataParamNames[strtolower($method)]] = $data;
        $options['headers'] = $this->headers;

        $response = $this->client->request(strtoupper($method), $endpoint, $options);

        $body = (string)$response->getBody();
        $body = json_decode($body, true);

        return $body;
    }

    /**
     * Set Basic Auth header
     *
     * @param $username
     * @param $password
     *
     * @return $this
     */
    public function basicAuth($username, $password)
    {
        $header['Authorization'] = "basic " . base64_encode($username . ":" . $password);
        $this->headers = array_merge($this->headers, $header);

        return $this;
    }

    public function apiKeyAuth($key)
    {
        $header['Authorization'] = $key;
        $this->headers = array_merge($this->headers, $header);

        return $this;
    }
}
