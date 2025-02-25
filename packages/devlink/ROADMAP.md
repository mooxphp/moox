# Roadmap

This is the roadmap for the devlink package.

## Current tasks

We are working on the following tasks from top to bottom. Please don't forget to update the roadmap when a task is completed.

-   [ ] Status command needs to be improved, now has 2 statuses:
    -   [ ] New `Updated` status:
        -   [ ] devlink.php = composer.json => (Rocket) Up-to-date
        -   [ ] devlink.php =/= composer.json => (Construction) Outdated
    -   [ ] Old `Link` status:
        -   [ ] Linked if composer.json-devlink exists and composer.json-deploy does not exist
        -   [ ] Unlinked if composer.json-deploy exists and composer.json-devlink does not exist
        -   [ ] Unknown if composer.json-devlink and composer.json-deploy do not exist
        -   [ ] Error if composer.json-devlink and composer.json-deploy exist
-   [ ] Private packages are not yet handled, they need to use the private repo URL
-   [ ] deploy: local packages must stay
-   [ ] deploy: new feature: last version for all packages instead of \*
