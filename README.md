# Scaffolds

[![Build Status](https://travis-ci.com/keboola/app-scaffold.svg?branch=master)](https://travis-ci.com/keboola/app-scaffold)

> Fill in description

# Usage

Scaffolds are saved in `scaffolds` directory, each scaffold has own directory named after scaffold and must contain `scaffold.json` file.
Optionally parameters from runner can be validated with `ScaffoldDefinition.php`

There are 3 operations available `create.configuration` , `create.configrows`, `create.orchestration`

Operation path `payload.configuration.parameters` can be overide with parameters injected by runner.

```
{
    "parameters": {
        "ReviewsReviewTrackers": {
            "writer01": {  // refer to component config id
                "parameters":{...}
            }
        }
}
```

example config:
```
{
    "opeartions": [
            {
                "operation": "create.configuration", // required
                "id": "customId", // required
                "KBCComponentId": "component name in storage",  // required
                "payload": {
                    "name": "Component name",
                    "configuration": {
                        ...
                    }
                }
            },
            {
                "operation": "create.configrows",
                "refConfigId": "customId", // refer to component config id
                "rows": [...]
            },
            {
                "operation": "create.orchestration",
                "payload": {
                    "name": "Reviews",
                    "tasks": [
                        {
                            "refConfigId": "customId", // refer to component config id
                        },
                        ...
                    ]
            }
    ]
}
```

## Development

Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/app-scaffold
cd app-scaffold
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```

# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/)
