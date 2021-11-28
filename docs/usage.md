## Introduction

With this MODX Revolution extra you can generate a xPDO where clause on base of
request parameters. This where clause can i.e. be used as pdoRessources or
getResources where property. That way you can filter MODX resources with request
parameters.

## Snippets

### FilterGetResourcesWhere

The FilterGetResourcesWhere snippet creates a where clause that can i.e. be used
as pdoRessources or getResources where property. It has to be called uncached to
work on the request values.

The Snippet uses the following properties:

Property | Description | Default
-------- | ----------- | -------
fields | JSON encoded array of filter => resourcefield combinations. | -
where | JSON encoded xPDO where clause to filter the resources. | -
emptyRedirect | ID of a resource, the user is redirected to, when the generated where clause is empty. | -
toPlaceholder | If set, the snippet result will be assigned to this placeholder instead of outputting it directly. | -
varName | Name of the superglobal variable that is searched for the filter values. | REQUEST

The fields property uses the following syntax in:
{"&lt;request_key&gt;":"&lt;resource_fields&gt;::&lt;operator&gt;::&lt;junction&gt;"}

`request_key` contains the request key from which the value is retrieved.
`resource_fields` contains a comma separated list of resource fields which are
searched by the value. `operator` contains the xPDO query operator like `=`,
`>`, `LIKE` etc. If the operator is `LIKE`, the searched value is surrounded
with `%`, to create a wildcard search in the resource fields. `junction` can be
set to define the junction between two search query parts.

The sanitized values of each request key is set as placeholder in `<key>_value`.

Using the `where` property, you can combine the created where clause with an
additional where clause created i.e. with `TaggerGetResourcesWhere`.