# The `steps` section

This section is an array of string values.

Each value is the name of a class implementing the `Akeeba\ReleaseMaker\Contracts\StepInterface` interface. If the class is in the `Akeeba\ReleaseMaker\Step` namespace you can omit the namespace. Otherwise you must specify the FQN (Fully Qualified Name) of the class. The class must be loadable through the class autoloader provided by Composer. 

You normally don't need to change the steps and can omit this section in its entirety. We currently only use it for debugging.

There are a few reasons why you might want to specify the `steps` section.

**Make a release without uploading files**. If your files are already uploaded through external means, e.g. your existing build process, you might want to skip the files upload step. In this case you need to only use the steps `prepare`, `release`, `items`, `publish`, and `updates`. Do note that you STILL need to specify a (fake) connection for your `files` section's entries to be valid. This connection will not be used. You can use completely bogus values in it.

**Upload updates without making a release**. If you want to refresh your updates without making a new release you can only use the `updates` step. This can be useful if you made a manual update to your release, e.g. changed the environments of one or more items. You still need to include a bogus connection and file entry for your configuration file to be valid.

**Customised release**. It's possible that the currently defined steps do not fully cover your use case. You can create your own custom step classes and autoloader. Call `releasemaker.php` from your own wrapper PHP file which loads your classes autoloader and specify your custom steps in your configuration file. Do note that you cannot easily override the configuration parser and the cofniguration validation unless you rewrite large parts of Release Maker.