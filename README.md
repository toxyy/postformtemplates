# PhpBB Extension - toxyy Post Form Templates v0.0.2

[Topic on phpBB.com](https://www.phpbb.com/community/viewtopic.php?t=2645506)

## Requirements

**phpBB 3.3.10-RC1+ PHP 7+
** - I have not tested with earlier versions, technically the latest event I use was added in 3.1.9, so it might work on even earlier versions. Try it out!

(core bug) ticket 17188 - [PHPBB3-17188](https://tracker.phpbb.com/browse/PHPBB3-17188)

To fix this yourself, open `/forumroot/phpbb/request/type_cast_helper.php`

Find
`!preg_match('/^./u', $result)`

Replace with
`!preg_match('//u', $result)`

Alternatively, you can also replace with:
`!preg_match('/.*/u', $result)`

Readme will be updated when the official fix is pushed to core phpBB.

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

This is just a minor preview. I could add many more pictures but this is already a bit much. It's a very big extension!

In a post:
![alt text](https://toxyy.github.io/postformtemplates/pft1.png)

What it looks like when you add, and then preview:
![alt text](https://toxyy.github.io/postformtemplates/pft2.png)

Form settings in the ACP:
![alt text](https://toxyy.github.io/postformtemplates/pft3.png)

Entry settings in the ACP:
![alt text](https://toxyy.github.io/postformtemplates/pft4.png)

Main image management page in ACP (I just used smiles as a quick example, use whatever images you want!)
![alt text](https://toxyy.github.io/postformtemplates/pft5.png)

Main permissions page (as you're used to):
![alt text](https://toxyy.github.io/postformtemplates/pft6.png)

Setting permissions page (also as you're used to):
![alt text](https://toxyy.github.io/postformtemplates/pft7.png)

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
