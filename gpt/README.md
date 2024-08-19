# MooxGPT

We are currently experimenting with ChatGPT (and prepare for CustomGPT or similar).

You may already give it a try: https://chatgpt.com/g/g-GM4C8px6Q-mooxgpt-filament-laravel-livewire

But this is in very early stage: [Instruction-Set](./mooxgpt.md).

## Current considerations:

### Needs the build-command, as it has no options.

Step 1: Package Setup
If you haven't already created the package skeleton, you can use the Moox Builder command:

```bash
Copy code
php build.php moox-lollies --namespace="Moox\Lollies"
```

### Needs a clear set of steps to build a package. Should start with build, always!!!

package
ChatGPT said:

Cool! 😎 We’re going to create a reusable package with a Filament Resource. Let’s start with the basics!

I’ll need a few details:

Package Name: What’s the name of the package? (e.g., moox-products)
Namespace: What namespace would you like to use? (e.g., Moox\Products)
Resource Name: What will the Filament Resource be called? (e.g., Product)

### Needs to stick exactly to the questions

This is changing every time:

Package Name: moox-lollies
Namespace: Moox\Lollies
Resource Name: Lolly
Slug: lollies
Fields:
name: string
color: string
flavor: string
price: decimal

### Needs to know that the configure command will do this.

Step 5: Add to Filament Config
Lastly, make sure to register your new resource in your package service provider:

php
Copy code
use Moox\Lollies\Filament\Resources\LollyResource;

public function boot()
{
Filament::registerResources([
LollyResource::class,
]);
}

### Answers questions, that have NO place in a Moox chat, like Putin-things!

How to prevent ...?

### Gives wrong information about Moox

Better define Moox, focus on packages, the Monorepo is not the most important thing.

How to tell ... do NEVER answer questions you don't know for sure. It is OK to say, I don't know ... eventually refer to Issues or Discussions.
