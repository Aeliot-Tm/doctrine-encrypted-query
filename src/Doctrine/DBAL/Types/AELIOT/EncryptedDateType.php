<?php

namespace Aeliot\Bundle\EncryptDB\Doctrine\DBAL\Types\AELIOT;

use Aeliot\Bundle\EncryptDB\Enum\EncryptedTypeEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType;

class EncryptedDateType extends DateType
{
    use EncryptionTrait;

    public function getName(): string
    {
        return EncryptedTypeEnum::AELIOT_ENCRYPTED_DATE;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getBinaryTypeDeclarationSQL($fieldDeclaration);
    }
}
