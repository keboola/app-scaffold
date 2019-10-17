# Application Scaffold

[![Build Status](https://travis-ci.com/keboola/app-scaffold.svg?branch=master)](https://travis-ci.com/keboola/app-scaffold)

> Scaffold application can setup project using list of operations.

# Usage

Scaffolds are saved in `scaffolds` directory, each scaffold has own directory. Directory name is also scaffold id.

## Scaffold structure

- Scaffold must contain `manifest.json` file and operations folders.
- Optionally parameters from runner can be validated with `ScaffoldDefinition.php`.
- Each scaffold operation id must be camelCase optionally with suffix after underscore.
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
SCAFFOLD_ID - is part of namespace of `ScaffoldDefinition` class, keep this in mind and use `CamelCase` syntax with alpha characters only.

### Decorators

Each task from orchestration is processed by decorators.

#### TransformationConfigurationRowsDecorator

 - Each configuration row input mapping is decorated with `source_search`
 - original source is kept for check with key name `__SCAFFOLD_CHECK__.original_source`.
 - source with rewritten table name in pattern `out.c-SCAFFOLD_ID.bucketNameTableName` is kept under key `__SCAFFOLD_CHECK__.source`. If input mapping point's to different configuration row in same transformation `source_search` can't be used [transformation-router#76](https://github.com/keboola/transformation-router/issues/76).
 - Each configuration row output mapping is decorated with `metadata` array, `destination` table has rewriten name with pattern `out.c-SCAFFOLD_ID.bucketNameTableName`. Original destination is kept under `__SCAFFOLD_CHECK__.original_destination` key name.

Be careful with sources from other components using default bucket since their bucket name has also configurationId and can't be matched automatically.

#### ClearEncryptedParametersDecorator

Clears all encrypted values in parameters. Please read https://github.com/keboola/app-scaffold/issues/22 all parameters used as inputs must be removed.

#### StorageInputTablesDecorator

Decorates component path `configuration.storage.input.tables[]` with `source_search` and original source is kept for check with key name `__SCAFFOLD_CHECK__.original_source`.
If source match pattern `out.c-project.tableName` it's rewritten to `out.c-SCAFFOLD_ID.tableName`.

#### Component specific

Decorators can be specific for one or more components.
Naming must contain Component name, if decorator supports more than one component name should describe function.

**Component specific decorators:**

- **ExSalesforceConfigurationRowsDecorator**: appends after [processors](https://developers.keboola.com/extend/component/processors/). Configuration rows has `configuration.parameters.objects[].name` path. Metadata tag looks like this: `CrmMrrSalesforce.internal.inHtnsExSalesforce######Order` Component uses default bucket and `######` must be replaced with configurationId or changed to something specific.

#### Disabling decorators

Any decorator can be disabled in `Keboola\ScaffoldApp\Importer\OperationImportFactory::DECORATORS` constant by removing or commenting out specific decorator class.

### Post import steps

- All values with `__SCAFFOLD_CHECK__` must be configured by scaffold author and appropriate changes has to be made to make scaffold work.
- `CreateConfiguration` operation are not decorated automatically so processors or input/output mapping has to be created/edited manually.
- If any parameters are going to be passed from runner `manifest.json` file `inputs` must be configured and can be validated by ScaffoldDefinition.php
- Don't forget to test scaffold in sample project if all mappings works, this is not possible to validate.
- If you want to use Snowflake or Redshift provisioned database, add `"authorization": "provisionedSnowflake"` or `"authorization": "provisionedRedshift"`
to the root of a Snowflake or Redshift writer operation configuration.
- If the component uses OAuth authorization, add `"authorization": "oauth"` to the root of the operation configuration. This will mark the created configuration
that it needs to be authorized manually by the end-user.
- Use command `docker-compose run --rm dev composer console scaffold:validate` to validate created scaffold

## Parameters

Optionally parameters from runner can be validated with `ScaffoldDefinition.php`

CreateConfiguration operation path `payload.configuration.parameters` can be override with parameters injected by runner.

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
