# Akeeba Release Maker, an overview

## What is Akeeba Release Maker?

Akeeba Release Maker is a tool to partially automate your software release process using an Akeeba Release System (ARS) installation on your Joomla site.

It takes care of the following steps of the release process:

* Upload package files to remote storage. This can be a web server accessible through FTP, FTPS, or SFTP; or an Amazon S3 bucket. This is where your packages will be made available for download to your users.
* Create or update an ARS release. It can optionally set the release notes to the contents of an HTML file you provide. Moreover, it can parse a change log file and include a formatted copy of the changes in the ARS release's Release Notes area.
* Create or update ARS items. One item is created per file. Each file can have a different Joomla viewing access level which allows you to limit who has access to which download item on your site.
* Publish the release and its items so that your users can download your software.
* Optionally copy the contents of your up-to-date ARS update streams to static hosting. This is _very strongly_ recommended.

## Why not release manually?

Making a non-trivial software release is a multi-step process. You'd need to write release notes (including formatting your changelog), create a release, paste the release notes in, and set the release access level. For each released file you'd need to create an item which means setting its title, uploading and choosing a file or pasting a download URL, set its environments, set its description, set its access level. Then you need to remember to publish all items and the release. In most cases you'd also want to access the ARS update stream URL and copy its contents to static content.

This can easily take hours and there's plenty of room for mistakes. With Akeeba Release Maker this process only takes a few seconds, it is fully automated, and there's a very small margin for mistakes (which can be easily caught). Moreover, it can be further automated as part of your release build scripts. We already do that in [Akeeba BuildFiles](https://github.com/akeeba/buildfiles) where our Phing common file already handles Release Maker automation.

## Why not just use ARS update stream URLs?

ARS offers a handy feature in the form of update stream URLs. Every time you access the update stream URL it will go through the database contents, find the published releases and create the minimum XML update stream file required by Joomla to find updates for all of your supported Joomla and PHP versions. While handy, this feature also takes a substantial amount of time which is on top of the resource already used by Joomla to load itself and call the ARS component.

The update stream URL would need to be accessed by every single site using your software, either when Joomla is looking for updates automatically (every 1 to 24 hours, by default every six hours) or when the user manually looks for updates. Each time this happens your site needs to load Joomla and have ARS reconstruct the update stream.

When you have several thousands of users — like we do at Akeeba Ltd — you can easily have a sustained load of several requests per second with a peak in the dozens of requests per second to the update stream URL. This can very easily consume all your server resources and bring your site down. This happened to us back in 2011 when we ‘only’ had a _few thousand_ clients. It took all of five minutes for the server to be essentially DDoS'ed when sites started probing for updates.

Copying the update streams _after a release is made_ to static hosting helps alleviate this problem. Even when using your own web server, the static update stream file will be kept in memory due to the sheer number of requests and will be delivered very fast to your clients, using the bare minimum of resources. When you reach the point of several dozens of thousands of clients this, however, is not enough. You will start reaching the limits of your hosting infrastructure. It's a good idea to use a CDN. You can either put your site behind a CDN such as CloudFlare or upload your update streams to a CDN you have for this purpose, e.g. Amazon CloudFront. We use a mix of both.

Either way, it's a good idea NOT to put your ARS update stream URLs in your extensions' XML manifests. Use static hosting for the update streams. Release Maker helps you automate their deployment.