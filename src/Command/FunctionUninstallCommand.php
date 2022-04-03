<?php

declare(strict_types=1);

namespace Aeliot\Bundle\DoctrineEncryptedField\Command;

use Aeliot\Bundle\DoctrineEncryptedField\Service\FunctionManager;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;

final class FunctionUninstallCommand extends InstallationCommand
{
    public function __construct(
        array $encryptedConnections,
        FunctionManager $functionManager,
        ConnectionRegistry $registry
    ) {
        parent::__construct(
            'doctrine-encrypted-field:function:uninstall',
            $encryptedConnections,
            $functionManager,
            $registry
        );
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Uninstall functions');
    }

    protected function prepare(Connection $connection, string $functionName): void
    {
        if ($this->functionManager->hasFunction($connection, $functionName)) {
            $this->functionManager->removeFunction($connection, $functionName);
        }
    }
}
