# esq_sectionsort

**Custom drag-and-drop section and category sorting in Textpattern** (v4.8.0+).

The plugin adds a user-definable sort order value for outputting sections and categories in a custom order.


## Installation and usage

- Install and activate the plugin
- Visit _Presentation › Sections_ or _Content › Categories_.
- A sort grabber will be present to the left of the section or categories. Drag and drop to change the order.

### Outputting sections / categories in a user-definable order

In your page templates and forms, use `sectionsort` or `categorysort` as your sort criteria when outputting section or category lists:

- **txp:section_list:** `sort="sectionsort asc"` or `sort="sectionsort desc"`
- **txp:category_list:** `sort="categorysort asc"` or `sort="categorysort desc"`

### Deleting

To prevent accidental deletion of sort order information, deleting the plugin does not remove the data from the database. To remove all sort order information from the database, visit the plugin options before deleting the plugin.


## Known issues

- Categories can only be sorted within their current parent. To make a category a child of another parent, edit it and change the parent it is assigned to.
- Sorting is not possible over pagination boundaries. Set the number of sections / page to the maximum possible.
- The sort grabbers may disappear after saving or changing pagination. Simply reload the pane and they will reappear.


## Changelog

- 2.0.5 – 2022-04-27. Prevent "Passing null to parameter ($string)" notice in PHP8. General neatening, messagepane improvements + plugin help.
- 2.0.2b – 2016-05-07. Code amendments by Uli to adjust to changed admin layout.
- 2.0.2 – 2014-12-04. Original version by Radneck with bug reports and fixes from rsilletti, maverick, jagorny and anteante. See [forum thread](https://forum.textpattern.com/viewtopic.php?id=34637)