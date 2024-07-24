<?php declare(strict_types=1);

namespace App\Integrations\BinList;

use Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class BinListManager
{
    public function __construct(public BinListClient $binListClient)
    {
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function checkIfBinIsEu(string $bin): bool
    {
        $data = $this->binListClient->getInfoByBin($bin);

        return $this->isEu($data->country->alpha2);
    }

    /**
     * @throws Exception
     */
    private function isEu(string $country): bool
    {
        $jsonFilePath = 'eu_countries.json';
        if (!file_exists($jsonFilePath)) {
            throw new Exception('File not found:' . $jsonFilePath);
        }
        $euCountries = json_decode(file_get_contents('eu_countries.json'), true);

        return in_array($country, $euCountries, true);
    }
}
