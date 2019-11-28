<?php

declare(strict_types=1);

namespace Keboola\ScaffoldApp\Command\Validation;

use Keboola\Component\JsonHelper;
use Keboola\Component\UserException;
use Keboola\ScaffoldApp\Operation\OperationsConfig;
use Swaggest\JsonSchema\Schema;
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
                'outputs' => new Assert\Required([
                    new Assert\Callback(function (
                        array $object,
                        ExecutionContextInterface $context,
                        $payload
                    ): void {
                        $this->validateScaffoldOutputs($object, $context);
                    }),
                ]),
                'requirements' =>  new Assert\Required([
                    new Assert\Callback(function (
                        array $object,
                        ExecutionContextInterface $context,
                        $payload
                    ): void {
                        $this->validateScaffoldRequirements($object, $context);
                    }),
                ]),
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
                        $this->validateJsonSchema($context, $object);
                    }),
                ],
            ],
        ]);
    }

    private function validateScaffoldOutputs(array $object, ExecutionContextInterface $context): void
    {
        $diff = $this->searchForMissingDefinitionDependencies($object);
        if (count($diff) > 0) {
            $context->buildViolation(sprintf(
                'Outputs \'%s\' are not provided by scaffold operations.',
                implode(', ', $diff)
            ))->addViolation();
        }
    }

    private function validateScaffoldRequirements(array $object, ExecutionContextInterface $context): void
    {
        $missingDefinition = $this->searchForMissingDefinitionDependencies($object);
        if (count($missingDefinition) > 0) {
            $context->buildViolation(sprintf(
                'Requirements \'%s\' are not provided by scaffold operations.',
                implode(', ', $missingDefinition)
            ))->addViolation();
        }
    }

    private function searchForMissingDefinitionDependencies(array $items): array
    {
        $found = [];
        foreach ($this->getOperationConfigRowsFiles() as $operationFile) {
            foreach ($items as $item) {
                preg_match('/(' . $item . ')/', $operationFile->getContents(), $outputArray);
                foreach ($outputArray as $outputItem) {
                    if (in_array($outputItem, $items)) {
                        $found[$outputItem] = $outputItem;
                    }
                }
            }
        }

        return array_diff($items, $found);
    }

    private function getOperationConfigRowsFiles(): Finder
    {
        $finder = new Finder();
        $finder->in(sprintf('%s/operations/', $this->scaffoldDir))
            ->files()->depth(1);

        return $finder;
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
            $this->getMissingOperationsInInputs(OperationsConfig::CREATE_ORCHESTRATION)
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

    private function validateJsonSchema(
        ExecutionContextInterface $context,
        array $inputs
    ): void {
        foreach ($inputs as $index => $input) {
            if (empty($input['schema'])) {
                continue;
            }
            try {
                $schema = (string) json_encode($input['schema']);
                Schema::import(json_decode($schema, false));
            } catch (\Throwable $e) {
                $context->buildViolation(sprintf(
                    'Json schema for component "%s" is not valid: "%s".',
                    $input['id'],
                    $e->getMessage()
                ))->atPath(sprintf('[%s][schema]', $index))->addViolation();
            }
        }
    }
}
