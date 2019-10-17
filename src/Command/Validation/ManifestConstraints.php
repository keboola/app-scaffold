<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command\Validation;

use Symfony\Component\Validator\Constraints as Assert;

final class ManifestConstraints
{
    public static function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'allowExtraFields' => true,
            'fields' => [
                'author' => new Assert\NotBlank(),
                'name' => [
                    new Assert\Length(['min' => 5]),
                    new Assert\NotBlank(),
                ],
                'description' => [
                    new Assert\Length(['min' => 20]),
                    new Assert\NotBlank(),
                ],
                'inputs' => new Assert\All([
                    new Assert\Collection([
                        'allowExtraFields' => true,
                        'fields' => self::getInputConstraints(),
                    ]),
                ]),
            ],
        ]);
    }

    private static function getInputConstraints(): array
    {
        return [
            'id' => new Assert\NotBlank(),
            'componentId' => new Assert\NotBlank(),
            'name' => new Assert\NotBlank(),
            'description' => new Assert\Optional(new Assert\NotBlank()),
            'required' => new Assert\Type('boolean'),
            'schema' => new Assert\Optional(
                new Assert\Collection([
                    'allowExtraFields' => true,
                    'fields' => self::getInputSchemaConstraints(),
                ])
            ),
        ];
    }

    private static function getInputSchemaConstraints(): array
    {
        return [
            'type' => new Assert\NotBlank(),
            'required' => new Assert\Type('array'),
            'properties' => new Assert\Type('array'),
        ];
    }
}
