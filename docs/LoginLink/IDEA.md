# Idea

Sends users a secure login link ... aka Magic Link

-   Fix the 404 (on what platforms?)
-   Finish used_at, not recognized by now, invalidate token
-   Let it look nice by using SimpleLayout
-   LoginLinkController::sendLinkInternal($user); should do the job, but provide an api
-   Instead of an 404 provide error handling for token links, but mute it to not reveal if an address is correct or not
-   Valid should be a combo from valid email, not expired and not used, maybe in the model
-   Then invalid requests can be stored (or logged?)
