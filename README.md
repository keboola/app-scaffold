# Application Scaffold

[![Build Status](https://travis-ci.com/keboola/app-scaffold.svg?branch=master)](https://travis-ci.com/keboola/app-scaffold)

> Scaffold application can setup project using list of oprerations.

# Usage

Scaffolds are saved in `scaffolds` directory, each scaffold has own directory. Directory name is also scaffold id.

## Scaffold structure

- Scaffold must contain `manifest.json` file and operations folders.
- Optionally parameters from runner can be validated with `ScaffoldDefinition.php`.
- Each scaffold operation id must be camelCase optionally with sufix after underscore.
- CreateConfigurationRows operations must match id with parent CreateConfiguration operation.

Example scaffold structure:
```
├── manifest.json
├── operations
│   ├── CreateConfiguration
│   │   ├── geneeaNlpAnalysisV2_0f1a.json
│   │   ├── kdsTeamExReviewtrackers_2a98.json
│   │   ├── keboolaWrDbSnowflake_e2e7.json
│   │   ├── transformation_4554.json
│   │   └── transformation_95f7.json
│   ├── CreateConfigurationRows
│   │   ├── transformation_4554.json
│   │   └── transformation_95f7.json
│   └── CreateOrchestration
│       └── orchestration_a53f.json
└── ScaffoldDefinition.php
```

## Scaffold creation

There are two ways how to create scaffold, manually and using import command (don't do it manually).

Import command will import orchestration and tasks configurations. Template of `manifest.json` and `ScaffoldDefinition.php` will be also created.
```
docker-compose run --rm dev composer console scaffold:import:orchestration <KBC_URL> <SAPI_TOKEN> <ORCHESTRATION_ID> <SCAFFOLD_ID>
```

### Decorators

Each task from orchestration is processed by decorators.

#### TransformationConfigurationRowsDecorator

Each configuration row imput mapping is decorated with `source_search` and original source is kept for check with key name `__SCAFFOLD_CHECK__.original_source`.
Note that transformation are not ordering automatically by `source_search` and `metadata` it's important to keep original `source` when it's from other configuration row in same transformation [transformation-router#76](https://github.com/keboola/transformation-router/issues/76).

Each configuration row output mapping is decorated with `metadata` array, destination table has rewriten name with pattern `out.c-SCAFFOLD_ID.tableName`. Original destination is kept under `__SCAFFOLD_CHECK__.original_destination` key name.

#### ClearEncryptedParametersDecorator

Clears all encrypted values in parameters. Please read https://github.com/keboola/app-scaffold/issues/22 all parameters used as inputs must be removed.

#### Component specific

Decorators can be specific for one or more components.
Naming must contain Component name, if decorator supports more than one component name should describe function.

**Component specific decorators:**

- **ExSalesforceConfigurationRowsDecorator**: appends after [processors](https://developers.keboola.com/extend/component/processors/). Configuration rows has `configuration.parameters.objects[].name` path.

### Post import steps

- All valus with `__SCAFFOLD_CHECK__` must be configured by scaffold author and appropriete changes has to be made to make scaffold work.
- `CreateConfiguration` operation are not decorated automatically so processors or input/output mapping has to be created/edited manually.
- If any parameters are going to be passed from runner `manifest.json` file `inputs` must be configured and can be validated by ScaffoldDefinition.php
- Don't forget to test scaffold in sample project if all mappings works, this is not possible to validate.

## Parameters

Optionally parameters from runner can be validated with `ScaffoldDefinition.php`

CreateConfiguration operation path `payload.configuration.parameters` can be overide with parameters injected by runner.

```
{
    "configData": {
        "parameters": {
            "id": "<scaffold id>",
            "inputs": [
                {
                    "id": "<operation id>",
                    "values": {
                    	"parameters": {
	                        <parameters object>
                    	}
                    }
                },
            ]
        }
    }
}
```

## Development

Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/app-scaffold
cd app-scaffold
cp .env.template .env
#don't forget to setup env variables for yout need
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```

# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/)
