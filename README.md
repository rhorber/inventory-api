# inventory-api
Little helper application to manage (cellar) inventory. (API)

## API Documentation

The API is documented as OpenAPI Specification documents. Each major API version is saved separately.
They are stored under `doc/V{major}/api.yml`. I.e. [doc/V1/api.yml](./doc/V1/api.yml).

The generated documentation is a zero-dependency file and stored under `doc/V{major}/api.html`.
I.e. [doc/V1/api.html](./doc/V1/api.html). It is created with [ReDoc](https://github.com/Redocly/redoc).

To update an HTML file use the following command:

```bash
$ npx redoc-cli bundle api.yml -o api.html
```
