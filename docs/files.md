# The `files` section

This section tells Release Maker where and which files to upload to which server and make them available for download through Akeeba Release Maker.

The section is an array. Its values are dictionaries (key-value arrays), each one having the following keys.

## `title`

**Required**: Yes

**Type**: String.

A title printed when Release Maker is processing the files.

## `connection`

**Required**: Yes

**Type**: String.

How should I upload the files? Use one of the keys in the 'connections' section.

## `directory`

**Required**: No

**Type**: String.

Override the directory of the connection.

## `source`

**Required**: Yes

**Type**: String.

Filesystem match pattern for the file to upload. Must include the absolute filesystem path.

All files matched by the pattern will be uploaded using the settings in this configuration.

Watch out! If you set up a pattern which overlaps with that of another file source you'll end up uploading the same file multiple times and overwrite its ARS Item.

## `access`

**Required**: No

**Type**: String.

Numeric ID of the Joomla View Access Level for the files being uploaded. Default: the same as the release's `access`.