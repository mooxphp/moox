# Deployment

Deployment is made 

- by **Envoyer** to All-Inkl (Shared Hosting)
- by **Laravel Vapor** to AWS (Lambda) - manually, not running
- by **Laravel Forge** to Digital Ocean - manually, not running

Automatic deployment is triggered by **push on** ```main``` or ```dev```, then we deploy:

-  TallUI Web from ```main``` on https://tallui.io
-  TallUI Web from ```dev``` on https://next.tallui.io
- TallUI Full from ```main``` on https://demo.tallui.io
- TallUI Satis from ```main``` on https://satis.tallui.io

These instances are not always running, the can be deployed for testing purpose:

-  TallUI Full from ```main``` on https://vapor.tallui.io
-  TallUI Full from ```main``` on https://forge.tallui.io
-  TallUI Full from ```main``` on https://shared.tallui.io