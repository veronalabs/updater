# Updater
A custom update checker for WordPress plugins and themes based on yahnis-elsts/plugin-update-checker and own private license server updater.

### Installation
Install by Composer
```
composer require veronalabs/updater
```

Then, initial the `UpdaterChecker` by the required parameters
```php
\VeronaLabs\Updater\UpdaterChecker::getInstance(array(
    'plugin_slug'  => 'plugin-slug',
    'website_url'  => 'https://plugin-url.com',
    'license_key'  => 'license-key',
    'plugin_path'  => 'plugin-path',
    'setting_page' => admin_url('admin.php?page=plugin-setting-page')
));
```