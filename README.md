# Telemetry
A helper for anonymous tracking of Craft CMS plugin usage

## Usage

Add `ether/telemetry` to your composer.json.

```shell script
composer require ether/telemetry
```

Replace the `extends Plugin` in your primary plugin file with `extends TelemetryPlugin`.

```php
use ether\telemetry\TelemetryPlugin;

class MyPlugin extends TelemetryPlugin {
    # ...
}
```
