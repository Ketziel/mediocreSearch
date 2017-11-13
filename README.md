# mediocreSearch

A somewhat powerful MODX search snippet.

## What does this plugin actually do?

mediocreSearch is allows you to search, filter and then output resources based on search cirteria. The fields available for search on each resource is specified, the order of which gives each feild a weight (allowing matches in some fields to be given priority over matches in others). The search also prioritises search term matches of single words, over those found inside of other words, which in theory should make searches less frustraiting - "_I searched for 'A Big Breasted Dame', not 'A Big Breasted Fundamentalist' god damn it!_".

## Using this thing

mediocrerSearch is built on two snippets - one of which is optional. The main snippet which runs the search, and a second snippet used to generate input filters. Chunks can be specified to define the structure of the outputted html.

While searching, mediocreSearch will assign each resource a rank, increasing it for each match found. By defining a weight for each search field, you can set how much each match will increase the rank by depending on the field it was found in.

## Options to help you do the do

Each snippet accepts and requires different paremters to function.

### mediocreSearch Snippet

| Name | Type | Default  Value | Description |
|-------|------|-----------------|------------|
| parent | string | '1' | Defines a parent resource for the search to search the children of. |
| fields | string | 'pagetitle,content' | Specifies the fields for the seach terms to match to. Check the **Fields** section below for more info. |
| filters | JSON string | '{}' | JSON array of hard coded filters for the search to apply to every search regardless of front end search form settings. |
| sortby | JSON string | '{"pagetitle":"ASC","menuindex":"DESC"}' | JSON array to order results by, should resources have the same rank after searching. Before a search or filter is supplied, all resources will be assigned rank 1 and will therefore be ordered by this field. |
| resultTpl | chunk | '' | Defines a template for each search result to follow. Look below at **Output Content** for more details. |
| includeTVs | string | '1' | Indicates if TemplateVar values should be included in the properties available to each resource template. |
| includeTVList | string | '' | An optional comma-delimited list of TemplateVar names to include explicitly if includeTVs is 1. _Using this is recommended to improve performance._ |
| resultsPerPage | integer | 0 | Defines a number of results to show per page. If set to 0, then _all_ results will be output. _Using this is recommended to improve performance._ |
| paginationCount | integer | 4 | Specifies a number of pages to show at either side of the current one, within the pagination. |
| paginationWrapperClass | string | 'pagination' | A class (or classes) to apply to the outer container of the pagination. |
| paginationPageClass | string | 'page' | A class (or classes) to apply to each pagination page. |
| paginationCurrentClass | string | 'current' | A class (or classes) to apply to the current pagination page. |
| paginationNext | string | '>' | Text or mark-up to be contained within the _next_ button within the pagination. |
| paginationPrev | string | '<' | Text or mark-up to be contained within the _prev_ button within the pagination. |


#### Fields

The order in which the search fields are placed in this list will affect the priority for calculating the resources rank. Fields listed at the start of this list will award more points to the resources rank, than those which follow it. By listing the searchable fields in the order they should be prioritized you should be able to acheive the results you want.

Resource options are specified as expected - by listing their key (_pagetitle, longtitle, description, content etc_). Template variables must be defined by starting the key with _TV._ in order for mediocreSearch to read the value correctly.

``TV.myTemplateVariable``

Migx values can also be searched, however the key within each Migx row to search must be specified. This is denoted by using _>_ between the template variable name for the Migx, and the key withn each row to search.

``TV.myMigxVariable>myMigxValueTitle``

Nested Migx variables are also searchable. To do this, just continue to seperate the variable keys with _>_.

``TV.myMigxVariable>myNestedMigx>myMigxValueTitle``

``TV.myMigxVariable>myNestedMigx>myDoubleNestedMigx>myMigxValueTitle``

### mediocreFilter

In order to generate filters for users to apply on the front end, the mediocreFilter should be used. The parameters are as follows:

| Name | Type | Default  Value  | Description |
| -------|------|-----------------|-------------|
| type | string | 'checkbox' | Defines the type of input to be generated. |
| classes | string | '' | Specifies the classes to attach to the input. |
| id | string | '' | Assigns an id to the input. |
| min/max | string | '' | Sets min/max attribute of input. |
| label | string | '' | Text for the inputs generated Label. |
| labelBefore | bool | false | Generates a label before the input element if set to true. |
| labelAfter | bool | false | Generates a label after the input element if set to true. |
| condition | JSON string | '' | String specifying the condition the filter should apply. |

## Output

When the mediocreSearch snippet is ran, a number of placeholders are generated. These placeholders can be used to build up the layout of the search page. The following is a list of those placeholders.

| Placeholder | Description |
|-------------|-------------|
| mediocreResults | Contains the processed output of all search results. |
| mediocreQuery | Contains the current search query - allows you to retain the search inputs vbalue after a page refresh. |
| mediocrePagination | Contains the generated pagination. |

### Results

Chunks are used to create the HTML structure of each returned search result. Any resource option or template variable can be used here. 

In addition, the following variables are also parsed to the chunk for use.

| Variable | Type | Description |
|----------|------|-------------|
| pagerank | integer | The numeric value calculated be the number of matches the resource received druing search |

A simple output chunk would be as follows:

```html
<div class="result">
  <a href="[[~[[+id]]]]"><h3>[[+longtitle:default=`[[+pagetitle]]`]] (rank [[+pagerank]])</h3></a>
  <p>[[+description]]</p>
</div>
```

### Search Form

It is important to call the **mediocreSearch** snippet _before_ a search form is defined.

The search form must, at minimum, consist on an text input with the name/id "search" and a submit button. If more inputs are required, use the above snippet call **mediocreFilter** to generate them. 

```html
<form id="mediocre-form" onSubmit="">
  <input name="search" id="search" value="[[+mediocreQuery]]">
  <fieldset>
      <legend>Filters</legend>
      [[[!mediocreFilter? &condition=`template:==` &value=`7` &label=`Is Template 7` &labelAfter=`true`]]
      [[[!mediocreFilter? &condition=`TV.myTV:==` &value=`mine` &label=`Is Mine` &labelAfter=`true`]]
  </fieldset>
  <button type="submit">Search</button>
</form>
```
