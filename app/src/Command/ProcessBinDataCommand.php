<?php declare(strict_types=1);

namespace App\Command;

use App\Integrations\ExchangeRate\ExchangeRateManager;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Integrations\BinList\BinListManager;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:process-bin-data',
    description: 'Processes BIN data from a file.',
)]
class ProcessBinDataCommand extends Command
{
    public function __construct(
        private readonly BinListManager $binListManager,
        private readonly ExchangeRateManager $exchangeRateManager,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'The path to the input file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        if (!file_exists($filename) || !is_readable($filename)) {
            $output->writeln('<error>File not found or not readable.</error>');
            return Command::FAILURE;
        }

        foreach (explode("\n", file_get_contents($filename)) as $row) {
            if (empty($row)) {
                continue;
            }
            $value = json_decode($row, true);

            try {
                $isEu = $this->binListManager->checkIfBinIsEu($value['bin']);
                $finalAmount = $this->exchangeRateManager->convertAmount(
                    amount: floatval($value['amount']),
                    currency: $value['currency'],
                    isEu: $isEu
                );

                $output->writeln((string) (ceil($finalAmount * 100) / 100));
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            } catch (TransportExceptionInterface $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}

