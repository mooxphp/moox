# TallUI API Manager

The TallUI API Manager provides an admin panel module to manage API Base URLs and credentials to access different APIs. 

Usage:

To use an API ...

```
$api->get('rest-api-1');

// you get
$api->name = "Rest-API Connection";

$api->connect('rest-api-1')->endpoint('/posts/')->response...
```

Presets:

- ReST ... returns JSON...
  - Name: ReST-API Connection
  - Slug: rest-api-1
  - Icon: Google Api
  - Base-URL: https://api.tallui.io
  - Auth: Basic Auth ... https://blog.restcase.com/4-most-used-rest-api-authentication-methods/
    - User: Username
    - Password: Secret
    - Token: Token
    - Token Value: 
  - Returns: Auto Discover, JSON, XML, 
  - Asset: load js/js-defer/js-footer/css
  - Comment: This is a custom ReST-API Connection
- GraphQL
- Google ...
- Facebook ...
- Twitter ...

Explore APIs on https://rapidapi.com/, https://apilist.fun/, https://developers.google.com/apis-explorer, https://apilayer.com/, 
