# The `release` section

This section contains general information about your software release in Akeeba Release System.

It is a dictionary (key-value array). It has the following keys.

## `version`

**Required**: Yes.

**Type**: String.

The version number you are releasing, e.g. '1.2.3' or '1.2.3.b3'.

The version number must follow the PHP versioning format. The following suffixes are understood and modify the stability field of the ARS release:

* `.a<NUMBER>` Alpha version
* `.b<NUMBER>` Beta version
* `.rc<NUMBER>` Release Candidate version

All other version numbers are considered to be stable.

## `date`

**Required**: No.

**Type**: String.

The date and time of the release. 

It can have one of the following formats understood by PHP's `DateTime` constructor:
* [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) e.g. `2021-01-18T09:10:20Z` understood as January 18th, 2021 at 9:10:20 a.m. GMT ("Zulu" time).
* [ISO-8601 date](https://en.wikipedia.org/wiki/ISO_8601) (YYYY-mm-dd) e.g. `2021-01-18` understood as January 18th, 2021.
* The string `now`. This is converted to the current date and time.

Dates need to be expressed in the GMT timezone, regardless of your site's or user's timezone preferences.

## `category`

**Required**: Yes.

**Type**: Integer.

The ARS category ID your release belongs in.

Go to ARS on your site, Categories and click on the category you want to make releases in. Check the URL. The number in the id parameter is the one you need to put here.

## `access`

**Required**: No.

**Type**: Integer.

Joomla's Access Level for the entire category. If not set it defaults to 1 (the default Public access level in Joomla).

## `release_notes`

**Required**: No.

**Type**: String.

Absolute filesystem path to a file containing HTML (without the HTML, HEAD and BODY tags). The HTML will be used as the release notes of the ARS release.

If it's not specified the release notes will be empty, notwithstanding the `changelog` option below.

## `changelog`

**Required**: No.

**Type**: String.

Absolute filesystem path to a file containing the changelog. If present, it will be parsed and the changelog of the latest release (the **first** one on the file) will be appended to the release notes which will be used for the ARS release.

The changelog has the following format:
```text
<?php die(); ?>
Software Name 1.2.3
================================================================================

* Security fix
! Critical fix
+ New feature
- Removed feature
^ Noteworthy change
~ Miscellaneous / minor change
$ Language file change
# Bug fix

Software Name 1.2.2
================================================================================

(more lines in the CHANGELOG file...)
```

The first line, the PHP die statement, is optional.

Each release's changelog starts with a header which optionally contains the software name and necessarily contains the version number.

The header is followed by exactly one line called the divider which contains three or more consecutive separator characters. The valid separator characters are `-` (hyphen), `–` (en-dash), `—` (em-dash), `=` (equals sign), `~` (tilde), or `_` (underscore). Only one type of separator characters is allowed in the divider.

The divider is followed by zero or more empty lines.

Following the empty lines you have one or more lines with change notes, separated by newlines. The first character of each changelog line determines its context and is used when formatting the changelog. The characters understood by Release Maker are:
* `*` (star): Security fix. Use when you've fixed a security issue. You should document this in your release notes as well. If it's not possible to do that without tipping the hand of attackers create a page on your site with the minimal set of information possible and a note that the page will be updated in a reasonable amount of time (e.g. after 15 to 30 days). The linked page should also credit the security researcher who reported the issue, if applicable.
* `!` (exclamation point): Important change. Use when you fix an issue which caused an extremely severe issue affecting most of your clients. You should document this in your release notes as well.
* `+` (plus sign) : New feature. Use when you add a new feature to your software. You should document this in your release notes as well.
* `-` (hyphen): Remove feature. Use when you removed a feature previously present. You should document this in your release notes as well.
* `^` (caret) : Noteworthy change. Use when you make a change which creates a backwards incompatible situation for your users, or if it requires the user to take action after the upgrade to the new version. You should document this in your release notes as well. 
* `~` (tilde) : Miscellaneous or minor change. Any small change in behaviour which is neither a bug fix nor a new feature. For example: changing the styling of administrative-only UI elements.
* `$` (dollar sign): Language file change. This is _not_ meant to document whether language strings are added or removed. It's there to note the addition, removal or major change of translations (language packages / language files).
* `#` (pound / hash sign): Bug fix. We recommend that you prefix the description of the bug you fixed with an indication of the bug impact (low, medium, high). We start our bugfix changelog lines with `# [HIGH]`, `# [MEDIUM]` or `# [LOW]` to communicate this information.

Lines not starting with any of these characters are _ignored_ and do not appear in the formatted changelog.

You cannot have line breaks in a changelog line. CHANGELOGs are meant to offer a short summary (headlines) of your changes. If you want to convey long information use the release notes. 

You cannot separate changelog lines with more than one line breaks, i.e you cannot have empty lines between your changelog lines. The first empty line after a changelog line is understood to denote the end of the version's changelog.

If your changelog contains notes for multiple versions the latest version needs to appear at the **top** of the file. Moreover, you need to leave at least one blank line between the changelog lines of one version and the header of its immediately previous version.