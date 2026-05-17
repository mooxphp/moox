# Moox User and more



-   [ ] User Profile (replaces Breeze)
-   [ ] User Models
    -   [ ] User
    -   [ ] Customer
-   [ ] Default Permission Groups for Users
    -   [ ] Super Admin
    -   [ ] Admin
    -   [ ] Editor
    -   [ ] Author
    -   [ ] Translator
    -   [ ] Viewer
    -   [ ] Guest
-   [ ] User Activity
-   [ ] User Login History
-   [ ] User Login Log
-   [ ] User Login Log

See also:

-   [bezhanSalleh/filament-shield](https://github.com/bezhanSalleh/filament-shield)
-   [jeffgreco13/filament-breezy](https://github.com/jeffgreco13/filament-breezy)



# User Device

See https://github.com/rappasoft/laravel-authentication-log

-   Stores or updates the Known Device for Users when a user logs in
-   Stores the device into the session, if the session-table exists
-   Users can be Moox Users, Moox Press or any other model
-   Uses [GeoLite-DB](Uses https://github.com/P3TERX/GeoLite.mmdb) to geolocate users by their IP-address
-   Multiple models are supported by config
-   Allows to show and edit User Devices in Filament

Next:

-   Drop a device and ->delete ->cascade to all sessions
-   Sends the user a notification about the login on an unknown device
-   Sends the user a request (Magic Link) to authenticate the new device
-   Can set the expiry of a session to a extremely high value (like years) on known devices
-   Can give the users opportunity to log in with a simple PIN on known devices
-   Can be audited - e. g. send a mail depending on a specific thing ... admin logs in with new device from outside germany
-   Cleans up orphaned devices
-   Notification, Slack etc?



# User Session

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





# Login Link





Sends users a secure login link ... aka Magic Link

-   Fix the 404 (on what platforms?)
-   Finish used_at, not recognized by now, invalidate token
-   Let it look nice by using SimpleLayout
-   LoginLinkController::sendLinkInternal($user); should do the job, but provide an api
-   Instead of an 404 provide error handling for token links, but mute it to not reveal if an address is correct or not
-   Valid should be a combo from valid email, not expired and not used, maybe in the model
-   Then invalid requests can be stored (or logged?)