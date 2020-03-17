# Application Scaffold

[![Build Status](https://travis-ci.com/keboola/app-scaffold.svg?branch=master)](https://travis-ci.com/keboola/app-scaffold)

Scaffold application can setup project using list of operations.

Scaffolds are saved in `scaffolds` directory, each scaffold has own directory. Directory name is also scaffold id.

## Scaffold structure

- Scaffold must contain `manifest.json` file and operations folders.
- Optionally, parameters from runner can be validated with `ScaffoldDefinition.php`.
- The json files in `operations` folder correspond to exported configurations.
- Each scaffold operation id must be CamelCase.
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

# Usage

The main steps in creating a new scaffold are:

- [import an orchestration](#importing-orchestration)
- [check all the configurations](#check-configurations)
- [configure authorization](#authorization)
- optionally - set-up [validation](#parameters) in `ScaffoldDefinition.php`
- Use command `docker-compose run --rm dev composer console scaffold:validate` to validate created scaffold
- [test the configuration](#test-configuration) on live project

## Importing Orchestration
Create new scaffold using an import command. The import command requires an orchestration ID and a token capable of 
reading all configurations. Import command will import the orchestration and its tasks configurations. A template 
of `manifest.json` and `ScaffoldDefinition.php` will be also created.

To use the import command, you need to clone the repository first:

```
git clone https://github.com/keboola/app-scaffold
cd app-scaffold
```

Import command:
```
docker-compose run --rm dev composer console scaffold:import:orchestration <KBC_URL> <SAPI_TOKEN> <ORCHESTRATION_ID> <SCAFFOLD_ID>
```

SCAFFOLD_ID - use `CamelCase` syntax with alpha characters only. This is part of namespace of `ScaffoldDefinition` class.

## Check Configurations
Check all the configurations for `__SCAFFOLD_CHECK__` occurrences:
- These are encrypted values, be sure to also check other values in the configurations which are variable (username, url, ...). Remove all of them (remove the nodes from the configuration, not just the values) and replace them with configuration schema:
    - In manifest.json, find the corresponding node in `inputs` and put a JSON schema directly in the `schema` node. 
    - Root properties map to the configuration `parameters` node. 
    - Add `title` for each schema control. 
    - List all controls as `required` (try to avoid optional controls to keep the schema very simple).
    - Use `format: password` for passwords and `format: checkbox` for boolean values.
- Check mapping of components:
    - If the component uses default bucket (most extractors), then the output mapping is unstated, in that case, add the [processors](#processors) to do that.
    - On known components the above is added for you, but you still have to check what the result tags are (and modify them if you don't like them)
    - In transformations input mapping, there will be three nodes `source_search`, `__SCAFFOLD_CHECK__.source` and `__SCAFFOLD_CHECK__.original_source`:
        - `__SCAFFOLD_CHECK__.original_source` is the name of the original table, remove this once you resolved the mapping.
        - If the input mapping references a table which is created **by the same** transformation bucket, use the `source` field and remove `source_search`
        - If the input mapping references a table which is created **outside** the transformation bucket (e.g. in extractor), use the `source_search` field and remove the `source` node.
    - If the input mapping contains numbers, chances are that the table was created using default bucket and you need to manually pickup the correct tag for the table.
    - In transformations output mapping, there will be nodes `destination` and `__SCAFFOLD_CHECK__original_destination`. Check that the new `destination` makes sense and delete the `__SCAFFOLD_CHECK__original_destination` when you don't need it.

## Authorization
For Snowflake/Redshfit writer which is supposed to use provisioned credentials, add `"authorization": "provisionedSnowflake"` or `"authorization": "provisionedRedshift"` to the root of the configuration (same level as `payload`) and remove everything inside the `db` node.
For components requiring OAuth, add `"authorization": "oauth"` to the root of the configuration (same level as `payload`).

## Processors
To handle components which have output mapping unstated in the configuration (i.e. components that use default bucket), processors need to be added.
The following shows a configuration for a single table:

```
"processors": {
    "after": [
        {
            "definition": {
                "component": "keboola.processor-create-manifest"
            },
            "parameters": {
                "delimiter": ",",
                "enclosure": "\"",
                "incremental": false,
                "primary_key": [],
                "columns_from": "header"
            }
        },
        {
            "definition": {
                "component": "keboola.processor-add-metadata"
            },
            "parameters": {
                "tables": [
                    {
                        "table": "reviews.csv",
                        "metadata": [
                            {
                                "key": "bdm.scaffold.table.tag",
                                "value": "someValue"
                            }
                        ]
                    }
                ]
            }
        }
    ]
}
```

The only thing which needs to be changed is the `someValue` for `bdm.scaffold.table.tag` tag. This value should be in the format:
`SCAFFOLDID.internal.SOMENAME` where `SOMENAME` is unique within the scaffold. If the component uses configuration rows, then this 
has to be repeated for each row. If the component doesn't use configuration rows, this is added only once to the configuration and
the `keboola.processor-add-metadata` processor is repeated for each table.
The `processors` node is at the same level as `parameters` node in the configuration.

## Test configuration
Don't forget to test scaffold in sample project if all mappings works, this is not possible to validate.
Prepare an input config file for [running the component](https://developers.keboola.com/extend/component/tutorial/debugging/#running-locally).
The structure of the configuration is as follows:

```
{
    "action": "useScaffold",
    "parameters": {
        "id": "SCAFFOLD_ID",
        "inputs": [
            {
                "id": "CONFIGURATION_ID",
                "values": {
                    "parameters": {...
                    }
                }
            }
        ]
    }
}
```

`SCAFFOLD_ID` is id of the scaffold (directory name). `CONFIGURATION_ID` is id of the configuration within scaffold (json operation file name).
The contents of the `parameters` node are the values received by the corresponding JSON schema. If the configuration has no schema (there is no configuration), it still must be listed with `values:null`.

Place the configuration in a `config.json` file inside a data directory and run the component:
```
docker-compose run -e KBC_URL=https://connection.keboola.com -e KBC_TOKEN=xxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx dev
```

## Internals

Each task from orchestration is processed by decorators.

### TransformationConfigurationRowsDecorator

 - Each configuration row input mapping is decorated with `source_search`
 - original source is kept for check with key name `__SCAFFOLD_CHECK__.original_source`.
 - source with rewritten table name in pattern `out.c-SCAFFOLD_ID.bucketNameTableName` is kept under key `__SCAFFOLD_CHECK__.source`. If input mapping point's to different configuration row in same transformation `source_search` can't be used [transformation-router#76](https://github.com/keboola/transformation-router/issues/76).
 - Each configuration row output mapping is decorated with `metadata` array, `destination` table has rewriten name with pattern `out.c-SCAFFOLD_ID.bucketNameTableName`. Original destination is kept under `__SCAFFOLD_CHECK__.original_destination` key name.

Be careful with sources from other components using default bucket since their bucket name has also configurationId and can't be matched automatically.

### ClearEncryptedParametersDecorator

Clears all encrypted values in parameters. Please read https://github.com/keboola/app-scaffold/issues/22 all parameters used as inputs must be removed.

### StorageInputTablesDecorator

Decorates component path `configuration.storage.input.tables[]` with `source_search` and original source is kept for check with key name `__SCAFFOLD_CHECK__.original_source`.
If source match pattern `out.c-project.tableName` it's rewritten to `out.c-SCAFFOLD_ID.tableName`.

### Component specific

Decorators can be specific for one or more components.
Naming must contain Component name, if decorator supports more than one component name should describe function.

**Component specific decorators:**

- **ExSalesforceConfigurationRowsDecorator**: appends after [processors](https://developers.keboola.com/extend/component/processors/). Configuration rows has `configuration.parameters.objects[].name` path. Metadata tag looks like this: `CrmMrrSalesforce.internal.inHtnsExSalesforce######Order` Component uses default bucket and `######` must be replaced with configurationId or changed to something specific.

### Disabling decorators

Any decorator can be disabled in `Keboola\ScaffoldApp\Importer\OperationImportFactory::DECORATORS` constant by removing or commenting out specific decorator class.

### Parameters

Parameters are validated against schema defined in manifest file. If `ScaffoldDefinition.php` file is present validation against json schema is skipped.

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

### Scaffold Dependencies
The manifest file can optionally list `outputs` which is a list of table tags produced by that scaffold (when its orchestration is ran). Then another scaffold may list a list of table tags
in its `requirements` section of its manifest file. The dependency checking works this way:

- The manifest to be used (A) get's all scaffolds used in the project.
- The outputs of the used scaffolds (B) are merged together.
- If B outputs matches A requirements then the scaffold A can be used.

I.e. the logic is that the scaffold can be used when all it's requirements are fullfilled by one or more scaffolds. However the presence of the scaffolds is important, not the presence of actual tables.

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
