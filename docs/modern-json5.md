## File format

[Example file](modern-json5.md)

Akeeba Release Maker 2.x configuration files can be written in the JSON or [JSON5](https://json5.org) file format. We recommend using JSON5 because it is more human friendly and even allows for comments.

The file is a JSON object with 6 top level keys:

* [The `release` section](release.md). Contains general information about your software release in Akeeba Release System.
* [The `api` section](api.md) Describes how Release Maker will connect to Akeeba Release System on your site.
* [The `steps` section](steps.md) Optional. It tells Release Maker which steps to run and in which order when making a release. You don't need to override this unless you are debugging Release Maker.
* [The `connections` section](connections.md) It contains one or more objects which describe how Release Maker will connect to a remote file storage server, such as an (S)FTP server or Amazon S3, to upload your software package and / or update files.
* [The `updates` section](updates.md) Optional. It tells Release Maker which ARS update streams you want to retrieve and upload to a remote server. This allows you to mirror your up-to-date software update streams to static file hosting on your site or a CDN.
* [The `files` section](files.md) It tells Release Maker where and which files to upload to which server and make them available for download through Akeeba Release Maker. 

Also see the [Akeeba Release Maker Overview](overview.md) documentation page.
