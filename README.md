# inventory-api
Little helper application to manage (cellar) inventory. (API)

## Implementations

This is the `main` branch. The database access is implemented with NoSQL (MongoDB).

There is also a relational SQL implementation on the [`sql` branch](https://github.com/rhorber/inventory-api/tree/sql).


## Documentation

### API

The API is documented as OpenAPI Specification documents. Each major API version is saved separately.
They are stored under `doc/V{major}/api.yml`. E.g. [doc/V1/api.yml](./doc/V1/api.yml).

The generated documentation is a zero-dependency file and stored under `doc/V{major}/api.html`.
E.g. [doc/V1/api.html](./doc/V1/api.html). It is created with [ReDoc](https://github.com/Redocly/redoc).

To update an HTML file use the following command:

```bash
$ npx @redocly/cli build-docs api.yml -o api.html
```

### Database

For the database there is a `mongosh.txt` file. It contains the `mongosh` commands to set up a database.
Only the `createIndex` commands would be necessary, but the `createCollection` commands are also included
to document the expected collections.

Currently, only `V3` contains one because `V3` it is the only version implemented with MongoDB at the moment.


## Credits

The API has a `gtin` endpoint to search by GTIN (Global Trade Item Number).
If the requested GTIN is not associated with an existing article,
the Open Food Facts API is queried. See [openfoodfacts.org](https://world.openfoodfacts.org/)
