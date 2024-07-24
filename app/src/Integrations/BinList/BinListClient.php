<?php declare(strict_types=1);

namespace App\Integrations\BinList;

use Exception;
use stdClass;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class BinListClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiUrl,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getInfoByBin(string $bin): stdClass
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiUrl . $bin
            );
            $binResults = $response->getContent();
            $data = json_decode($binResults);

            if ($data === null || !isset($data->country->alpha2)) {
                throw new Exception('Invalid API response.');
            }

            return $data;
        } catch (ClientException $e) {
            throw new Exception('Client error: ' . $e->getMessage());
        } catch (ServerException $e) {
            throw new Exception('Server error: ' . $e->getMessage());
        } catch (TransportException $e) {
            throw new Exception('Transport error: ' . $e->getMessage());
        }
    }
}
