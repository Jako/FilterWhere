## Introduction

With this MODX Revolution extra you can generate a xPDO where clause on base of
request parameters. This where clause can i.e. be used as pdoRessources or
getResources where property. That way you can filter and sort MODX resources
with request parameters.

## Snippets

### FilterGetResourcesWhere

The FilterGetResourcesWhere snippet creates a where clause that can i.e. be used
as pdoRessources or getResources where property. It has to be called uncached to
work on the request values.

The Snippet uses the following properties:

| Property      | Description                                                                                              | Default |
|---------------|----------------------------------------------------------------------------------------------------------|---------|
| emptyRedirect | ID of a resource, the user is redirected to, when the generated where clause is empty.                   | -       |
| fields        | JSON-encoded array of ‘filter => resourcefield’ combinations.                                            | -       |
| options       | JSON-encoded array of filter operator options.                                                           | -       |
| toPlaceholder | If set, the snippet result will be assigned to this placeholder instead of outputting it directly.       | -       |
| type          | Type of the xPDO clause to filter the resources. Can be set to ‘where’ or ‘having’. Defaults to ‘where’. | where   |
| varName       | Name of the superglobal variable that is searched for the filter values.                                 | REQUEST |
| where         | JSON-encoded xPDO where clause to filter the resources.                                                  | -       |

The fields property uses the following syntax:

```json
{"<request_key>":"<resource_fields>::<operator>::<junction>"}
```

`request_key` contains the request key from which the value is retrieved.
`resource_fields` contains a comma separated list of resource fields which are
searched by the value. `operator` is optional and contains the xPDO query
operator like `=`, `>`, `LIKE` etc. It defaults to `=`. If the operator is
`LIKE`, the searched value is surrounded with `%`, to create a wildcard search
in the resource fields. `junction` is optional and can be set to define the
junction between two search query parts. It defaults to empty, which means `AND`
in an xPDO where clause.

The sanitized values of each request key is set as placeholder in `<key>_value`.

Using the `where` property, you can combine the created where clause with an
additional where clause created i.e. with `TaggerGetResourcesWhere`.

#### Operators

FilterWhere can use the default xPDO operators like 

`=`, `>`, `<`, `>=`, `<=`, `!=`, `LIKE` and `IN`

If the operator `LIKE` is used, the query value is surrounded with `%`. If the
operator `IN` is used, you maybe have to surround each requested value with
quotes, if request values are not an array.

There are some additional operators available with FilterWhere:

- `RANGE`: The requested value is separated at a `-` sign. The first part is used 
  as the start of the range and the last part as the end of the range.

- `DATE`: The requested value will be parsed as [valid date/time
  string](https://www.php.net/manual/en/datetime.formats.php). The resulting
  value will be used as the start of a date range. The value plus one day will
  be used as the end of a date range. The start and end date can be formatted
  with the `dateformat` option or set to `unixtime`.

- `DATERANGE`: The requested value will be separated by a string referenced in
  the `daterangeseparator` option. The two parts are parsed as [valid date/time
  string](https://www.php.net/manual/en/datetime.formats.php). The resulting
  first two values will be used as the start and the end of a date range. The
  start and end date can be formatted with the `dateformat` option or set to
  `unixtime`.

- `GEOCODE`: The requested value will be geocoded with Google Maps Geocoding
  (the API Key has to be set in the system settings). The distance from the
  resulting value to the location given by two values in the resource (lat/lng
  separated by `||`) is calculated. This distance has to be smaller than the
  requested `distance` value.

##### Operator Options

FilterGetResourcesWhere uses the following operator options in a JSON encoded
value in the `options` property:

| Option             | Description                                                                                          | Default       |
|--------------------|------------------------------------------------------------------------------------------------------|---------------|
| daterangeseparator | The string, the requested value is separated with when the `DATERANGE` operator is used.             | `-`           |
| dateformat         | The values in start and end date can be formatted with the `dateformat` option or set to `unixtime`. | `Y-m-d H:i:s` |

#### Examples

##### In Array

Create a form on a page and prepend it with a FilterGetResourcesWhere call:

```html
[[!FilterGetResourcesWhere?
&fields=`{ "resource":"alias::IN" }`
&toPlaceholder=`resourceswhere`
]]
<form method="get" action="[[~[[*id]]]]">
    <div>
        <input type="checkbox" name="resource[]" value="foo" [[!+resource_value:FormItIsChecked=`foo`]]>
        <label>Foo</label>
    </div>
    <div>
        <input type="checkbox" name="resource[]" value="bar" [[!+resource_value:FormItIsChecked=`bar`]]>
        <label>Bar</label>
    </div>
</form>
```

This form will filter a getResources snippet call showing only resources with
the `alias` 'foo' and/or 'bar', if one checkbox is enabled.

##### Range

Create a form on a page and prepend it with a FilterGetResourcesWhere call:

```html
[[!FilterGetResourcesWhere?
&fields=`{ "count":"count::RANGE" }`
&toPlaceholder=`resourceswhere`
]]
<form method="get" action="[[~[[*id]]]]">
    <div>
        <input type="checkbox" name="count" value="0-2" [[!+count_value:eq=`0-2`:then=`checked`:else=``]]>
        <label>Count 0-2</label>
    </div>
    <div>
        <input type="checkbox" name="count" value="2-4" [[!+count_value:eq=`2-4`:then=`checked`:else=``]]>
        <label>Count 2-4</label>
    </div>
    <div>
        <input type="checkbox" name="count" value="4-" [[!+count_value:eq=`4-`:then=`checked`:else=``]]>
        <label>Count 4+</label>
    </div>    						
</form>
```

This form will filter a getResources snippet call showing resources if the TV
value `count` is inside the range, if one checkbox is enabled.

##### Daterange

Create a form on a page and prepend it with a FilterGetResourcesWhere call:

```html
[[!FilterGetResourcesWhere?
&fields=`{ "daterange":"publishedon::DATERANGE" }`
&options=`{ "daterangeseparator":" - ", "dateformat":"unixtime" }`
&toPlaceholder=`resourceswhere`
]]
<form method="get" action="[[~[[*id]]]]">
    <div>
        <input type="text" name="daterange" value="[[!+daterange_value]]">
        <label>Daterange</label>
    </div>
</form>
```

This form will filter a getResources snippet call showing resources in the
daterange set in the daterange input (Example value: `2023-12-01 - 2023-12-30`).

If the second part of the daterange is not set, the range has no end.

##### Geocode

Create a form on a page and prepend it with a FilterGetResourcesWhere call:

```html
[[!FilterGetResourcesWhere?
&fields=`{ "geolocation":"lat||lng::GEOCODE" }`
&toPlaceholder=`resourceswhere`
]]
<form method="get" action="[[~[[*id]]]]">
    <div>
        <input type="text" name="geolocation" value="[[!+geolocation_value]]">
        <select name="distance">
            <option selected="true" disabled [[!+distance_value:FormItIsSelected=``]]>Distance</option>
            <option value="5" [[!+distance_value:FormItIsSelected=`5`]]>5 km</option>
            <option value="10" [[!+distance_value:FormItIsSelected=`10`]]>10 km</option>
            <option value="25" [[!+distance_value:FormItIsSelected=`25`]]>25 km</option>
            <option value="50" [[!+distance_value:FormItIsSelected=`50`]]>50 km</option>
        </select>
    </div>    						
</form>
```

This form will filter a getResources snippet call showing resources with a max
distance around the geocoded value, if the geocoded value is has a result and
the distance is set. Otherwise, the result is empty.

All forms use the following getResources snippet call.

```html
[[!getResources?
...
&where=`[[!+resourceswhere]]`
]]
```

### FilterGetResourcesSortby

The FilterGetResourcesSortby snippet creates a sortby clause that can i.e. be used
as pdoRessources or getResources sortby property. It has to be called uncached to
work on the request values.

The Snippet uses the following properties:

| Property      | Description                                                                                                             | Default |
|---------------|-------------------------------------------------------------------------------------------------------------------------|---------|
| dirkey        | The name of a request key that is used to set the sort direction.                                                       | -       |
| sortby        | JSON-encoded xPDO sortby clause to sort the resources. This sorting is used before the sorting of the request is added. | -       |
| sortkey       | The name of a request key that is used to set the sort field.                                                           | -       |
| toPlaceholder | If set, the snippet result will be assigned to this placeholder instead of outputting it directly.                      | -       |
| varName       | Name of the superglobal variable that is searched for the filter values.                                                | REQUEST |

#### Examples

##### Sorting

Create a form on a page and prepend it with a FilterGetResourcesSortby call:

```html
[[!FilterGetResourcesSortby?
&toPlaceholder=`resourcessortby`
]]
<form method="get" action="[[~[[*id]]]]">
    <div>
        <select name="sortby">
            <option value="pagetitle" selected="true" [[!+sortby_value:FormItIsSelected=`pagetitle`]]>Pagetitle</option>
            <option value="createdon" [[!+sortby_value:FormItIsSelected=`createdon`]]>Created on</option>
        </select>
        <select name="sortdir">
            <option value="ASC" selected="true" [[!+sortdir_value:FormItIsSelected=`ASC`]]>Ascending</option>
            <option value="DESC" [[!+sortdir_value:FormItIsSelected=`DESC`]]>Descending</option>
        </select>
        <input type="hidden" name="sortdir" value="ASC">
    </div>    						
</form>
```

This form will filter a getResources snippet call showing resources sort by the
pagetitle or createdon field in ascending or descanding order.

This form use the following getResources snippet call.

```html
[[!getResources?
...
&sortby=`[[!+resourcessortby]]`
]]
```

## System Settings

FilterWhere uses the following system settings in the namespace `filterwhere`:

| Key                             | Name                                | Description                                                                                                                              | Default |
|---------------------------------|-------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------|---------|
| filterwhere.debug               | Debug                               | Log debug information in the MODX error log.                                                                                             | No      |
| filterwhere.google_maps_api_key | Google Maps Geocoding API Key       | [Request](https://developers.google.com/maps/documentation/javascript/get-api-key) a Google Maps API Key with enabled Geocoding API.     | -       |
| filterwhere.google_maps_region  | Google Maps API Region Code Biasing | [Preferred region](https://developers.google.com/maps/documentation/javascript/geocoding#GeocodingRegionCodes) for the geocoding result. | -       |
