# Idea

Original idea was for bookmarks, but I think it is a good idea for wishlist package, as these are bookmarked items, too.

I want to add a new Laravel and Filament package to my collection of packages on https://github.com/mooxphp/moox

Moox Bookmarks allow users to set a bookmark on any item (or Press Post, CPT etc.), so that it is saved in the bookmarks table with his user id.

Users have a view that shows all bookmarks including last-changed (and sorted by last changed), so he never misses any update on the items or posts bookmarked.

While on most tables there is a Laravel default field for that, for Press (that uses the WordPress tables under the hood), we need to access a WP Post including Meta.

First my model:

-   Bookmark
    -   Title (Title of the Bookmarked doc)
    -   Info (optional text)
    -   Item (the item that is saved)
    -   Item type (model)
    -   User (the user who saves)
    -   User type (user model)
    -   Timestamps

I am not sure, if I miss something. Please check and make suggestions, if any idea.
Collections - I am not sure if I want or need that, but let's discuss ...
Item updated - that field should be from the Item, I guess. Maybe add it to the model.

-   https://chatgpt.com/c/67102e32-e31c-800c-9b53-a7acd54c4e21
-   https://chatgpt.com/c/670f56c0-db0c-800c-a27b-a9d745002a17 (WP)
