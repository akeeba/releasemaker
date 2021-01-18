# The `updates` section

This section is optional. It tells Release Maker which ARS update streams you want to retrieve and upload to a remote server. This allows you to mirror your up-to-date software update streams to static file hosting on your site or a CDN.

The section is an array. The elements of the array are dictionaries (key-value arrays).

Each element of the array has the following keys.

## `title`

**Required**: Yes

**Type**: String.

A title printed when Release Maker is processing the update.

## `connection`

**Required**: Yes

**Type**: String.

How should I upload the updates? Use one of the keys in the `connections` section.

## `directory`

**Required**: No

**Type**: String.

Override the directory of the connection.

## `stream`

**Required**: Yes

**Type**: Integer.

ARS update stream ID to read from.

## `base_name`

**Required**: Yes

**Type**: String.

Base name of the update stream file to upload. Warning: do not include the extension of the file!

## `formats`

**Required**: Yes

**Type**: Array of string.

Which update stream formats to upload. One or more of `ini`, `inibare` or `xml`.

ARS supports two update formats, INI and XML. The INI format (deprecated) gives update information for exactly one release, the latest published one. The XML format is Joomla's XML extension update which includes the latest published versions which satisfy every combination of Joomla and PHP you have declared in your environments. It has additional information which convey the PHP compatibility which are not canonical to Joomla's XML update scheme (Joomla just ignores them).

The `ini` and `inibare` formats refer to ARS' INI format updates. The difference is the file extension. With `inibare` there is no file extension, just the `base_name`. With `ini` the file will have a `.ini` extension.

The `xml` format refers to ARS' XML update format. The uploaded file will have a `.xml` extension.