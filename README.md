# Google OpenLineage Writer

> Writes jobs data into a Google Data Catalog via OpenLineage API integration

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/google-openlineage-writer
cd google-openlineage-writer
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
