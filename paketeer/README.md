# Paketeer - Automatic OCO Package/Update Creation Tool
This OCO extensions allows you to create packages by automatically downloading the necessary files from the vendor website of supported software. This simplifies the package creation.

Since Microsoft deprecated the WSUS server, you can use this extension to easily create and deploy Windows updates packages through OCO - no need to pay for Intune to have the Windows patch management under your control!

## Installation
1. Move this extension directory into your OCO server's `extensions` directory **or** clone this repo into a separate directory on your server and create a symlink to the extension directory inside the OCO server's `extensions` directory.

2. "Paketeer" is now visible at the end of the left sidebar in the web interface.

## Usage
Simply select the software for which you want to create an OCO package. Then, select the version, a target package family and click "Create".
