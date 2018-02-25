# php-sky-remote
Controll Sky HD/Q boxes over TCP/IP in PHP
# Usage

```php
use PhpSkyRemote\PhpSkyRemote;

$remote = new PhpSkyRemote('192.168.0.X'); // Replace with sky box IP

// Send single command
$remote->press('home');

// Send single command using constants
$remote->press(PhpSkyRemote::COMMAND_HOME);

// Send multiple commands using a string...
$remote->press('home up select');

// ... or an array
$remote->press(['home', 'up', 'select']);

// ... or an array of constants
$remote->press([
  PhpSkyRemote::COMMAND_HOME,
  PhpSkyRemote::COMMAND_UP,
  PhpSkyRemote::COMMAND_SELECT,
]);
```

# Credits
This project was completed by converting https://github.com/dalhundal/sky-remote into PHP. Many thanks to Dal Hundal for writing it. Without his work this would not have been easy...
