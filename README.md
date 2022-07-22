# gravity-forms-bootstrap-5-classes
A class to append Bootstrap 5 form and grid classes to Gravity Forms HTML

This class will automatically disable Gravity Forms' built-in styles. It requires the PHP DOM extension, which is usually enabled by default.

By default, most fields will be stacked vertically on mobile (date and time fields are not).

### Usage

Drop this file somewhere into your theme's `functions.php`. Then require and initialize it:

```
require_once get_template_directory() . '/path/to/class/class-gravityformsbootstrap.php';

new GravityFormsBootstrap();
```

### Notes

This intentionally does not provide vertical spacing between fields, or padding on the form itself, since this will vary much more often among different use cases.
