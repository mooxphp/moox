# Documentation

## Video

Use GIFs from the `art/video` folder. This is an example of a CLI video:

![Moox](../../art/video/test-cli-video.gif?raw=true)

It is 75% sized of the original video and compressed with [Squoosh](https://squoosh.app/) or [FreeConvert](https://www.freeconvert.com/gif-compressor), so 15 seconds of video are under 1MB.

```applescript
tell application "iTerm"
	activate
	tell the first window
		set bounds to {100, 100, 1200, 900}
		tell current session
			write text "cd ~/Herd/moox"
			write text "clear"
		end tell
	end tell
end tell
```

💡 This ensures that iTerm always launches at the exact size you want.

```applescript
tell application "Safari"
	if (count of windows) = 0 then
		make new document
	end if
	set bounds of front window to {100, 100, 1400, 900}
    set URL of front document to "https://www.moox.org"
end tell
```

> [!TIP]
> This ensures that Safari always launches at the exact size you want.
> Same possible for "Google Chrome", but we use Safari.

# We discuss docs here

-   https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax
-   Doing Screenshots
-   Adding Data and Images (Seeding) - use addMedia in the Factory
    -   https://thispersondoesnotexist.com/
    -   https://picsum.photos/
    -   https://unsplash.com/

## Moox Models

```plaintext
        +-------------------------------------+
        | 🧑 User                             |
        +-------------------------------------+
        | id: int                             |
        | name: str                           |
        | email: str                          |
        +-------------------------------------+
        | posts(): HasMany                    |
        +-------------------------------------+

        +-------------------------------------+
        | 📝 Item                             |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🏷️ Taxonomy                         |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🧩 Modules                          |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 📜 Log                              |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🌐 Api Data                         |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🗄️ Others                           |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🔄 Pivot                            |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+
```
