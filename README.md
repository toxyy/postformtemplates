# PhpBB Extension - toxyy Post Form Templates v0.0.1

[Topic on phpBB.com]()

## Requirements

phpBB 3.3.5-RC1+ PHP 7+

## Features

Make, manage, and use forms in posts within phpBB.

Features multiple entry types (text entry, radio, checkbox, dropdown, and notes),
as well as header images with image cycle selection and settings.

Subentries can also be created inside the entry settings page for a more dynamic template.

Entries support full BBCode, so you can style your questions on the frontend,
and have user answers styled to your liking as well. Initial or ending newlines
are also supported, if you want to better format how they are added to posts.

Extensive ACP features, including image uploader, and full custom permission system.

Templates and template categories can be created for better organizing for admins and users.

Max depth of three templates, so you can have a Category -> Category -> Form Template, or anything inbetween,
though form templates cannot be at depth 0, there must always be an initial category.

Extremely customizeable, with multiple ways to copy a template or category:
template settings (with or without copying parent), permissions, templates, entries, display forums, and image settings.

## Screenshot

## Quick Install

You can install this on the latest release of phpBB 3.2 by following the steps below:

* Create `toxyy/postformtemplates` in the `ext` directory.
* Download and unpack the repository into `ext/toxyy/postformtemplates`
* Enable `Post Form Templates` in the ACP at `Customise -> Manage extensions`.

## Uninstall

* Disable `Post Form Templates` in the ACP at `Customise -> Extension Management -> Extensions`.
* To permanently uninstall, click `Delete Data`. Optionally delete the `/ext/toxyy/postformtemplates` directory.

## Support

* Report bugs and other issues to the [Issue Tracker](https://github.com/toxyy/postformtemplates/issues).

## License

[GPL-2.0](license.txt)
