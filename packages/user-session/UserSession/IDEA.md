# Idea

-   [x] sessions resource
-   [ ] sessions in user profile
-   [ ] sessions as user relationsmanager

-   Allows to list, view and manage sessions in Filament
-   Compatible to Moox User, Moox Press or any Laravel-compatible user model
-   Can handle multiple user models at a time
-   Is perfect to use together with Moox User Device

Next:

-   More Power to the model?
-   Make RelationManagers if class and config exists
-   Edit does not work, does not make sense either
-   Drop would be better

Test

```php
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
```

config

-   filter by IP - Name (z. B. für PRTG)
-   filter by IP - Whitelisted, not Whitlisted
-   filter by user, non-user
-   filter by frontend, backend

Known Services:

I create a laravel package / filament plugin that allows to manage sessions, it is called Moox Sessions.

For Sessions to be useful as a tool to monitor user sessions and distinct them from visitor sessions and kind of bots, I want to implement a known ips or known services feature.

My idea:
Get a list of known services and the URL, where they make their IPs publicly available, like

-   https://ohdear.app/used-ips
-   https://uptimerobot.com/inc/files/ips/IPv4andIPv6.txt

I would then create a service,
