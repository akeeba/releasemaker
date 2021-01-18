# Akeeba Release Maker

Release software and make it available to your Joomla-based site's Akeeba Release System installation.

We recommend reading the [Overview](docs/overview.md) to get an idea of what it does and why.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received [a copy of the GNU General Public License](LICENSE.txt) along with this program.  If not, see <http://www.gnu.org/licenses/>.

## Before you begin

Run `composer install` to install the external dependencies.

## How to use

`php /path/to/this/repository/releasemaker.php /your/path/to/release.json5`

Optional parameters:

* `--debug` Enable debug mode (full error reporting)

A non-zero exit code indicates a failure. Unhandled exceptions always set the exit code to 255.

## Configuration file formats

Akeeba Release Maker 2.x supports three different configuration formats:

* [JSON5 configuration format](docs/modern-json5.md) ([example](docs/sample_config.json5)).
* [YAML configuration format](docs/modern-yaml.md) ([example](docs/legacy.json)).
* [Legacy JSON configuration format](docs/legacy.md) ([example](docs/sample_config.yaml)). Deprecated. It is provided for backwards compatibility. Support for it will be removed in the future.

We very strongly recommend using the JSON5 or YAML formats. They are more feature rich than the legacy format, allowing for very customised software release processes.

