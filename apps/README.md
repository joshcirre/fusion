This directory holds test apps for the Fusion library. Each app is a full-on, actual Laravel application. Each has its
own `composer.json` and `package.json`. The purpose of these apps is to test the Fusion library in the most realistic
way possible.

The `composer.json` of the Vue app can serve as a reference for any new apps you'd like to create.

Because of a few composer limitations, we have some workarounds to make sure we can install the package.

Before packages are installed or updated, we run `zip.sh`.

```json
{
    "scripts": {
        "pre-install-cmd": [
            "../zip.sh"
        ],
        "pre-update-cmd": [
            "../zip.sh"
        ]
    }
}
```

The `zip.sh` script takes the `composer.json` from the main Laravel application, adds "version: dev-main" to it, and
creates a zip file named `fusionphp-fusion-dev-main.zip`.

With that `.zip` file in place, Composer will look there to install `fusionphp/fusion` thanks to this section in the
`composer.json`:

```json
{
    "repositories": [
        {
            "type": "artifact",
            "url": "../"
        }
    ]
}
```

After the package is "installed" (which we put in quotes, because it's just a `composer.json`), we run a script to
symlink the real package.

In the app's `composer.json`, we have these scripts:

```json
{
    "scripts": {
        "post-autoload-dump": [
            "../symlink.sh vue"
        ],
        "post-update-cmd": [
            "../symlink.sh vue"
        ],
        "post-install-cmd": [
            "../symlink.sh vue"
        ]
    }
}
```

Where `vue` is the name of app's directory. The `symlink.sh` will symlink each individual file/folder from the main
Laravel package into the `vendor/fusionphp/fusion` directory of the sample application. It respects the `.gitattributes`
of the main package, only
symlinking the files that would be present if required from Packagist.

Now, in the test application's `vendor/fusionphp/fusion` you'll have the plugin fully symlinked. We go to all this
trouble
because composer prevents you from installing a package inside itself, so we have to have workarounds. We could use
Workbench, but we need multiple applications, and this setup gives me more confidence that it will work in real apps.