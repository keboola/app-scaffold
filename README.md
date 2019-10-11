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
