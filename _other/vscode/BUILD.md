# Build

This is only an extension package. Use it with VS Code. There is no need to build anything else ;-)

## Update package on VSC Marketplace (yep, that's more an internal note)

```bash
npm install -g vsce

cd tallui-vscode
$ vsce package
# myExtension.vsix generated
$ vsce publish
# <publisherID>.myExtension published to VS Code Marketplace
```

See https://code.visualstudio.com/api/working-with-extensions/publishing-extension for more information.

Instead of vsce publish, where most of the time the pat expired, you can use the upload on https://marketplace.visualstudio.com/manage/publishers/adrolli
