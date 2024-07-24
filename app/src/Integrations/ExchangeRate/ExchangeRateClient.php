<?php declare(strict_types=1);

namespace App\Integrations\ExchangeRate;

use Exception;
use stdClass;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ExchangeRateClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiUrl,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws Exception
     */
    public function getExchangeInfo(): stdClass
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl);
            $data = json_decode($response->getContent());
        } catch (ClientExceptionInterface $e) {
            throw new Exception('Client error: ' . $e->getMessage());
        } catch (ServerExceptionInterface $e) {
            throw new Exception('Server error: ' . $e->getMessage());
        } catch (TransportExceptionInterface $e) {
            throw new Exception('Transport error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error fetching exchange rate data: ' . $e->getMessage());
        }


        if ($data === null) {
            throw new Exception('Error fetching or parsing exchange rate data.');
        }

        return $data;
    }
}
