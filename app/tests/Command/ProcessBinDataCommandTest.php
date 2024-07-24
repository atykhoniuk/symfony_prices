<?php declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ProcessBinDataCommand;
use App\Integrations\BinList\BinListClient;
use App\Integrations\BinList\BinListManager;
use App\Integrations\ExchangeRate\ExchangeRateClient;
use App\Integrations\ExchangeRate\ExchangeRateManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessBinDataCommandTest extends KernelTestCase
{
    private BinListManager $binListManager;
    private ExchangeRateManager $exchangeRateManager;
    private CommandTester $commandTester;
    private BinListClient $binListClientMock;

    private ExchangeRateClient $exchangeRateClientMock;
    protected function setUp(): void
    {
        parent::setUp();

        $this->binListClientMock = $this->createMock(BinListClient::class);
        $this->exchangeRateClientMock = $this->createMock(ExchangeRateClient::class);

        $this->binListManager = new BinListManager($this->binListClientMock);
        $this->exchangeRateManager = new ExchangeRateManager($this->exchangeRateClientMock);

        $command = new ProcessBinDataCommand(
            $this->binListManager,
            $this->exchangeRateManager
        );

        $this->commandTester = new CommandTester($command);
    }

    public function testFileNotFound(): void
    {
        $this->commandTester->execute(['filename' => 'non_existing_file.json']);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('File not found or not readable.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testProcessValidFile(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($filename, json_encode([
                'bin' => '45717360',
                'amount' => 100.00,
                'currency' => 'USD'
            ]) . PHP_EOL);

        $this->binListClientMock
            ->expects($this->once())
            ->method('getInfoByBin')
            ->willReturn((object) [
                'country'  => (object) [
                    'alpha2' => 'FR',
                ],
            ]);

        $this->exchangeRateClientMock
            ->expects($this->once())
            ->method('getExchangeInfo')
            ->willReturn((object) [
                'rates'  => (object) [
                    'USD' => 0.99,
                ],
            ]);

        $this->commandTester->execute(['filename' => $filename]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('1.02', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());

        unlink($filename);
    }

    public function testHandleExceptionInvalidRate(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($filename, json_encode([
                'bin' => '45717360',
                'amount' => 100.00,
                'currency' => 'USD'
            ]) . PHP_EOL);

        $this->binListClientMock
            ->expects($this->once())
            ->method('getInfoByBin')
            ->willReturn((object) [
                'country'  => (object) [
                    'alpha2' => 'FR',
                ],
            ]);

        $this->exchangeRateClientMock
            ->expects($this->once())
            ->method('getExchangeInfo')
            ->willReturn((object) [
                'rates'  => (object) [
                    'USD' => 0.00,
                ],
            ]);

        $this->commandTester->execute(['filename' => $filename]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Invalid exchange rate.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());

        unlink($filename);
    }

    public function testHandleExceptionErrorParsingExchange(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($filename, json_encode([
                'bin' => '45717360',
                'amount' => 100.00,
                'currency' => 'USD'
            ]) . PHP_EOL);

        $this->binListClientMock
            ->expects($this->once())
            ->method('getInfoByBin')
            ->willReturn((object) [
                'country'  => (object) [
                    'alpha2' => 'FR',
                ],
            ]);

        $this->exchangeRateClientMock
            ->expects($this->once())
            ->method('getExchangeInfo')
            ->willReturn((object) [
                'rates'  => (object) [
                ],
            ]);

        $this->commandTester->execute(['filename' => $filename]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Error parsing exchange rate data.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());

        unlink($filename);
    }
}
