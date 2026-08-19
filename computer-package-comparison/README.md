# Computer Package Comparison

This extension compares the centrally managed OCO packages installed on two computers. It is intended for preparing replacement computers without manually checking the package lists of the old and new device.

The comparison intentionally does not use recognised software. Software installed manually by users is therefore outside the scope of this extension.

## Features

- adds a **Compare packages** button to the **Packages and Jobs** tab of computer objects
- selects a comparison computer by its unique hostname
- distinguishes missing packages, different versions, identical packages and packages installed only on the current computer
- searches and filters the comparison result
- offers all deployable versions of a missing package family, with the most recently created OCO package selected by default
- opens OCO's regular deployment assistant for the selected packages and target computer
- honours the existing read and deploy permissions

## Compatibility

- OCO Server 1.2.2 through 1.2.x
- German, English and French translations

## Installation

Copy the `computer-package-comparison` directory into the `extensions` directory of the OCO Server installation. The extension is loaded automatically and is listed under **Settings > Configuration > Extensions**.

No database migration or modification of OCO core files is required.

## Usage

1. Open the new or replacement computer in OCO.
2. Select **Packages and Jobs**.
3. Click **Compare packages**.
4. Select the old computer.
5. Review the missing or differing packages.
6. Select the desired package versions and open the deployment assistant.

The extension only prepares the deployment assistant. Packages are not deployed until the deployment is confirmed there.

## Tests

The comparison logic has PHPUnit tests in the `tests` directory.

```bash
phpunit --configuration tests/phpunit.xml
```
