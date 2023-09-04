# Install-Tool
The Install Tool automates the process of creating a new OS installation. It creates an OCO computer object and an unattended Windows installation answer file (XML). After that, it creates jobs for installing basic software.

This is only a demonstration and can be extended for more company-specific installation steps. It is only localized in German.

## Installation
1. Move this extension directory into your OCO server's `extensions` directory **or** clone this repo into a separate directory on your server and create a symlink to the extension directory inside the OCO server's `extensions` directory.

2. Create a configuration value `install-tool` on the OCO config page with the following JSON content and enter all necessary credentials/values:
```
{
	"oco": {
		"package-group-id-base-windows": 21,
		"package-group-id-base-linux": 22,
		"preseed-path-windows": "/srv/smb/images/preseed"
	}
}
```
- `package-group-id-base-windows` is the ID of the default package group for new Windows installations
- `package-group-id-base-linux` is the ID of the default package group for new Linux installations
- `preseed-path-windows` is the file path for unattended Windows installation answer files (XML), shared via SMB

3. "Install-Tool" is now visible at the end of the left sidebar in the web interface for all users with the `InstallTool` permission in the OCO system user role.

4. Create a Windows unattended installation template file `TEMPLATE.xml` in the defined `preseed-path-windows`. The placeholder `$$HOSTNAME$$` will be replaced when creating a new installation. It may look like the example in the `preseed` folder.
