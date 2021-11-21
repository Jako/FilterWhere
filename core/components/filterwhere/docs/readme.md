# FilterWhere

Filter resources with request parameters

- Author: Thomas Jakobi <office@treehillstudio.com>
- License: GNU GPLv2

## Features

With this MODX Revolution package you can generate a xPDO where clause on base
of request parameters. This where clause can i.e. be used as pdoRessources or
getResources where property.

## Installation

MODX Package Management

## Documentation

@TODO

In short: 

```
[[!FilterGetResourcesWhere?
&fields=`{"search":"pagetitle,content,description::LIKE"}`
]]
```

The snippet call will create a where clause to filter getResources/pdoResources
results with the value of a search request key. The value of the search request
key is set as placeholder in `<key>_value`

## GitHub Repository

https://github.com/Jako/FilterWhere
