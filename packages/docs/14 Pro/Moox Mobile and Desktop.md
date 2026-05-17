https://mooxmobile.test

- https://bifrost.nativephp.com/
- https://nativephp.com/docs/mobile/1/getting-started/introduction
- https://developer.apple.com/account

https://mooxdesktop.test

- https://nativephp.com/docs/desktop/1/getting-started/introduction



Concept:

- Platforms - DB table and Selector to manage multiple platforms

- Contexts - Platform delivers the available contexts like (Moox Family, Moox Estate ... that cares for needed meta data)
- Files are in a lokal upload queue and can be uploaded in the background then, automatically resume them
- Files are then attached to an entity or managed as a whole (360 stitches etc.) by Moox Media Pro

- https://github.com/tus - resumable uploads specially for Desktop, for Mobile used in Backend because of Webview-sleep problem
- https://uppy.io/ - Uploader for Desktop (and Web maybe later), for Mobile own native Uploader and maybe [cropper.js](https://fengyuanchen.github.io/cropperjs/), because Webview-prob
- API
  - https://mooxdev.test/api/media/contexts - GET contexts
  - https://mooxdev.test/api/media/upload/init - POST prepare upload session, get a ticket
  - https://mooxdev.test/api/media/upload/preflight - POST dry run the upload, see what it does
  - https://mooxdev.test/api/media/upload/files - PUT resumable upload (tus or chunks)
  - https://mooxdev.test/api/media/upload/finalize - POST finish upload, invoke jobs to optimize or create media entries
  - https://mooxdev.test/api/media/upload/abort - POST stop complete upload, remove fragments 
  - https://mooxdev.test/api/media/upload/status - GET status for an upload by ticket-id
- Workflow (mobile)
  - Platform selector
  - Context selector
  - Global settings or meta data
  - Select files
  - Show files, auto-sorted, re-sortable with fast-edit fields
  - Upload

