# inventory-api
Little helper application to manage (cellar) inventory. (API)

## Implementations

This is the `sql` branch. The database access is implemented with relational SQL.  

There is also a NoSQL (MongoDB) implementation on the [`main` branch](https://github.com/rhorber/inventory-api/tree/main).


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

### Database structure

There are SQL scripts to scaffold a database. These do also serve to document the database structure.
There are scripts for the MySQL/MariaDB and PostgreSQL dialects. Each major API version is saved separately.

They are stored under `doc/V{major}/database.{dialect}.sql`.
E.g. [doc/V1/database.postgresql.sql](./doc/V1/database.postgresql.sql).


## Credits

The API has a `gtin` endpoint to search by GTIN (Global Trade Item Number).
If the requested GTIN is not associated with an existing article,
the Open Food Facts API is queried. See [openfoodfacts.org](https://world.openfoodfacts.org/)
