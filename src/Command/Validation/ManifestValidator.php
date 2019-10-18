<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command\Validation;

use Keboola\Component\JsonHelper;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

final class ManifestValidator
{
    /**
     * @var SplFileInfo
     */
    private $scaffoldDir;

    /**
     * @var array
     */
    private $manifest;

    public function __construct(SplFileInfo $scaffoldDir)
    {
        $this->scaffoldDir = $scaffoldDir;
    }

    public function validate(): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $this->manifest = JsonHelper::readFile(sprintf('%s/manifest.json', $this->scaffoldDir->getPathname()));
        return $validator->validate($this->manifest, $this->getConstraints());
    }

    private function getConstraints(): Assert\Collection
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
                'inputs' => [
                    new Assert\All([
                        new Assert\Collection([
                            'allowExtraFields' => true,
                            'fields' => self::getInputConstraints(),
                        ]),
                    ]),
                    new Assert\Callback(function (
                        array $object,
                        ExecutionContextInterface $context,
                        $payload
                    ): void {
                        $this->validateScaffoldInputsOperationListing($context);
                    }),
                ],
            ],
        ]);
    }

    private function getInputConstraints(): array
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

    private function getInputSchemaConstraints(): array
    {
        return [
            'type' => new Assert\NotBlank(),
            'required' => new Assert\Type('array'),
            'properties' => new Assert\Type('array'),
        ];
    }

    private function validateScaffoldInputsOperationListing(
        ExecutionContextInterface $context
    ): void {
        $missingOperations = $this->getMissingOperationsInInputs(OperationsConfig::CREATE_CONFIGURATION);
        $missingOperations = array_merge(
            $missingOperations,
            $this->getMissingOperationsInInputs(OperationsConfig::CREATE_ORCHESTREATION)
        );

        if (empty($missingOperations)) {
            return;
        }

        $context->buildViolation(sprintf(
            'Scaffold inputs missing this operations: %s',
            implode(', ', $missingOperations)
        ))->addViolation();
    }

    private function getMissingOperationsInInputs(string $operation): array
    {
        $finder = new Finder();
        $finder->in(sprintf('%s/operations/%s/', $this->scaffoldDir, $operation))
            ->files()->depth(0);

        $missingOperations = [];
        foreach ($finder as $operationFile) {
            /** @var array $input */
            foreach ($this->manifest['inputs'] as $input) {
                if ($input['id'] === $operationFile->getBasename('.json')) {
                    continue 2;
                }
            }
            $missingOperations[] = $operationFile->getBasename('.json');
        }

        return $missingOperations;
    }
}
