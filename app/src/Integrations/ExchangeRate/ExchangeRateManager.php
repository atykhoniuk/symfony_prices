<?php declare(strict_types=1);

namespace App\Integrations\ExchangeRate;

use Exception;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

readonly class ExchangeRateManager
{
    public function __construct(private ExchangeRateClient $exchangeRateClient)
    {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws Exception
     */
    public function convertAmount(float $amount, string $currency, bool $isEu): float
    {
        if ($currency !== 'EUR') {
            $rate = $this->getExchangeRate($currency);
            if ($rate === 0.0) {
                throw new Exception('Invalid exchange rate.');
            }
            $amount = $amount / $rate;
        }

        $feeRate = $isEu ? 0.01 : 0.02;

        return $amount * $feeRate;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws Exception
     */
    private function getExchangeRate(string $currency): float
    {
        $exchangeInfo = $this->exchangeRateClient->getExchangeInfo();

        if (!isset($exchangeInfo->rates->$currency)) {
            throw new Exception('Error parsing exchange rate data.');
        }

        return $exchangeInfo->rates->$currency;
    }
}
