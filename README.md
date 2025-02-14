<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/logo-dark.png">
      <source media="(prefers-color-scheme: light)" srcset="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/logo-light.png">
      <img alt="Fusion for Laravel" src="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/logo-light.png" style="max-width: 80%; height: auto;">
    </picture>
</p>

<h3 align="center">Unite your modern frontend with your Laravel backend.</h3>

---

Fusion is the best way to combine your Laravel backend with your JavaScript frontend. We currently only support Vue.js,
but React + Svelte are on the roadmap.

> [!CAUTION]
> 🚨🚨🚨
>
> Fusion is in a _very_ early development preview. Please do not use it in production yet! There are still lots of
> bugs (probably.)
>
> 🚨🚨🚨

## Concepts

It is important to note up front what Fusion **does not** do:

- Fusion does not transpile your PHP to WASM.
- Fusion does not turn your JavaScript into PHP/Blade.
- Fusion does not use the "PHP for templating."
- Fusion does not *automatically* sync frontend/backend state.

What Fusion **does** do:

- Fusion uses Vite to extract the PHP blocks from your JavaScript files and writes the PHP to disk.
- Fusion uses Vite to inject some information into your JavaScript file as it is being transpiled.
- Fusion runs your PHP on the backend and your JavaScript on the frontend.
- Fusion turns your PHP block into a sort of controller.
- Fusion uses the standard Laravel request/response lifecycle, router, auth, middleware, etc.
- Fusion allows *you* to sync frontend/backend state.

Conceptually, you can think of the `<php>` block in your file as your controller, with a little bit of auto-wiring
applied to inject state and call methods. That's about it! (To learn more, you may read
the [how Fusion works](#how-fusion-works) section.)

Using Fusion, you can write a single file like this:

```vue

<php>
  // Define a prop in PHP
  $name = prop(Auth::user()->name);
</php>

<template>
  <!-- Use it in Vue! -->
  Hello {{ name }}!
</template>
```

This exposes the `$name` variable to your Vue template as `name`. It will be passed down to the frontend upon first
load. You do
not need to define any props on the Vue side, we take care of that for you.

There are two styles of PHP you can write. You can write "[procedural](#procedural-php)"
or "[class-based](#class-based-php)" PHP.

Procedural is much closer to a functional paradigm, and may feel more comfortable to folks coming from other languages
like JavaScript. Writing the previous example using a class-based approach is very similar:

```vue

<php>
  new class {
  public string $name;

  public function mount()
  {
  $this->name = Auth::user()->name;
  }
  }
</php>

<template>
  Hello {{ name }}!
</template>
```

You define a class and all public properties become state.

Neither approach is right, neither is wrong. Neither approach is stupid, neither is smart. It's merely a matter of
preference! More details on each approach can be found throughout.

## Installation

**Fusion expects to be installed in a Laravel application that uses Inertia.** In the future we might not have that
stipulation, but for now it needs to be an Inertia application.

If you want to play with a Fusion app without creating your own, you can clone
the [FusionCasts](https://github.com/fusion-php/fusioncasts) repo.

To install Fusion into your application, you must first require it from Packagist:

```bash
composer require fusionphp/fusion
```

(Yes, it's `fusionphp` without a dash. The GitHub org is `fusion-php` but the packagist namespace is `fusionphp`.)

Then you can run the installation command.

```bash
php artisan fusion:install
```

The installation command will do the following:

- Publish the `config/fusion.php` file
- Make sure the correct storage directory exists
- Add the Vue package to your `package.json`
- Add a `fusion:install` script and modify the `postinstall` script to call it
- Add the Vue plugin to your `resources/js/app.js`
- Add the Vite plugin to your `vite.config.js`
- Add a `post-update-cmd` to your `composer.json`
- Migrate Fusion's internal SQLite database

Fusion creates `[original].backup` files for each file it modifies, in case it messes your file up in any way. You're
free to delete those once you're comfortable with the modifications.

## Getting Started

You may now run Vite by running `npm run dev`. Fusion will run as a part of the Vite toolchain. (A Vite plugin was added
by the `fusion:install` Artisan command.)

Thanks to the Vite plugin, every `<php>` block gets extracted from the `.vue` single file components (SFCs.) More
in-depth information on the entire process can be seen at the bottom of the page.

## Routing

You have two options when it comes to routing. You may choose to do file-based routing, or route your components
individually. You are free to mix and mingle the styles.

### File-based routing

File-based routing is convenient when you want your URL structure to mirror your file structure. To get started with
file-based routing, you can call `Fusion::pages()` in your `web.php` file.

```php
// web.php
use Fusion\Fusion;

Fusion::pages();
```

The `pages` method accepts two arguments: a URI `$root` and a `$directory`. By default, the URI root is `/` and the
directory is whatever is defined in your config under `paths.pages`, which is `resources/js/Pages` by default.

By calling `Fusion::pages()` with no arguments, you're saying "route everything in my `fusion.paths.pages` directory,
using `/`
as the starting point." This includes pages that have no `<php>` block.

By passing arguments, you have more control over the routes. Maybe you only want to auto-route marketing pages, but you
want them routed to the root domain.

```php
// web.php
use Fusion\Fusion;

// All pages in resources/js/Pages/Marketing end up at the root.
Fusion::pages(root: '/', directory: 'Marketing');
```

This will route an example component `Marketing/Hello.vue` to the URI `/hello`. You're free to use `pages` as many times
as you want:

```php
// web.php
use Fusion\Fusion;

// All marketing pages end up at the root.
Fusion::pages(root: '/', directory: 'Marketing');

// All files in `Cases` end up at `/case-studies`
Fusion::pages(root: '/case-studies', directory: 'Cases');
```

#### Route binding

Laravel has powerful [route model binding](https://laravel.com/docs/11.x/routing#route-model-binding), which allows you
to automatically inject models into your controllers instead of having to look them up manually. Fusion exposes that
same route model binding.

To indicate that a route has a segment that is a parameter, you may use square brackets `[]` in the filename.

For example, a file name `Podcasts/[Podcast].vue` would receive a `$podcast` parameter. When a user visits `podcasts/1`,
you would receive a string `"1"` as your parameter.

How you receive this parameter depends on the style of PHP you're writing. To receive it in procedural PHP, you may use
the `fromRoute` method on the `prop` function.

```php
$podcast = prop()->fromRoute();
```

In this example, having not passed any arguments, we will assume that the route's parameter is named `podcast` because
your variable was named `$podcast`. Should you need to customize it, you can pass a value:

```php
// Variable is named $pod, but the route parameter is
// `podcast` to match the filename of [Podcast].vue.
$pod = prop()->fromRoute('podcast');
```

Using class-based PHP, you may receive the route parameter in your `mount` method.

```php
new class {
    public function mount($podcast)
    {
        // Do something with $podcast! Usually that
        // means setting it to a public property.
    }
}
```

When you're using the procedural method of route binding, you actually do not have to include a `mount` function. If you
have a public or protected property that is named the same as a route parameter, we will go ahead and auto set it if
there is no `mount` function available.

```php
new class {
    // Will be set to the route parameter.
    public $podcast;
}
```

#### Route model binding

So far, all we've done is _route_ binding, not _route model_ binding. Sometimes route binding is all you want. Most
times, you want route model binding. To route model bind, you'll need to tell Fusion what class to look for.

Using procedural PHP:

```php
$podcast = prop()->fromRoute(class: \App\Models\Podcast::class);
```

And class based:

```php
new class {
    // Merely hint the type as a UrlRoutable. 
    public function mount(\App\Models\Podcast $podcast)
    {
        // Podcast is now an Eloquent Model.
    }
}
```

If the model is not found, a 404 error will be thrown.

Again, you can just use a public property instead of using the mount function and we will respect the type hint on that
property.

```php
new class {
    public \App\Models\Podcast $podcast;
}
```

To customize the routing even further, you may pass more arguments to the procedural `fromRoute` method.

```php
$podcast = prop()->fromRoute(
    class: \App\Models\Podcast::class,
    // Route by a custom key instead of `id`
    using: 'slug',
    // Include soft-deleted models. 
    withTrashed: true
);
```

These follow Laravel's standards of
including [soft-deleted models](https://laravel.com/docs/11.x/routing#implicit-soft-deleted-models)
and [customizing the route key](https://laravel.com/docs/11.x/routing#customizing-the-default-key-name).

Customization using the class-based `mount` method is not available yet, but we'll add attributes to control the route
key and soft-deletes very soon. Sorry about that!

#### Wild-card routes

If you have a route that might have many wildcard segments, you may indicate that with a preceding `...` in the
parameter name, like this: `Podcasts/[...wild].vue`.

In your PHP, we will pass `$wild` as an array, split by slashes (`/`).

This can be useful for SEO purposes, amongst other things. Given a file of `Podcasts/[...wild].vue` and the URI
`podcasts/the-best/show-in-the-world/a8f74b`, a prop of `$wild` will be equal to the array
`['the-best', 'show-in-the-world', 'a8f74b']`.

```php
$wild = prop()->fromRoute();
// $wild === ['the-best', 'show-in-the-world', 'a8f74b']

// Get the unique ID from the parts.
$id = last($wild);
```

```php
new class {
    public function mount(array $wild) 
    {
        $id = last($wild);   
    }
}
```

#### Fine-grained control over the routed files

Should you need it, you may exercise greater control over file-based routing by passing a closure as the second
argument. The Closure must return an instance of `\Symfony\Component\Finder`.

```php
use Fusion\Fusion;
use Symfony\Component\Finder\Finder;

Fusion::pages('/', fn() => (new Finder)
    // Start in any directory you please.
    ->in(config('fusion.paths.pages'))
    // Some file patterns you don't want routed.
    ->notName('*.template.vue')
    // Maybe we route these separately? Who knows.
    ->exclude([
        'Cases',
        'Marketing'
    ])
);
```

### Manual routing

If you prefer to route your pages one-by-one, you may use the singular `page` method:

```php
// web.php
use Fusion\Fusion;

Fusion::page(uri: '/hello-world', component: 'Custom/HelloWorld');
```

You are free to combine `page()` along with `pages()`. We would suggest putting your `page` calls first, so that any
routes with parameters or wildcards don't take precedence.

## Writing PHP

Your PHP code must be contained inside of a single `<php></php>` block, anywhere inside of your Vue SFC.

It seems to make sense to place it as the first block in your file, since it's the first thing that will be executed
when a request comes in. That's merely preference though, you're free to do as you please.

Your PHP code will be run within the context of a Laravel request. You have full access to
the [container](https://laravel.com/docs/11.x/container), [facades](https://laravel.com/docs/11.x/facades), [helpers](https://laravel.com/docs/11.x/helpers),
and everything else Laravel has to offer.

Regardless of the style of PHP you write, it must be valid PHP. If it isn't, Vite will show you an overlay with your
errors.

### Best practices

There aren't a lot of best practices for writing PHP in Vue templates or JavaScript files, because that's
never really been done before!

My recommendation is that you treat your PHP block as a "thin controller." I would recommend that you defer much of the
business logic out to actions, service objects, or other classes in your
application, and use the PHP block in your Vue template as a sort of routing layer into the rest of your application.
Then, the PHP block in your Vue template only serves as an entry point from HTTP into the rest of your application.

### Procedural PHP

Procedural PHP is very a straightforward, top-to-bottom style of writing your PHP.

Fusion provides you a few important functions, which we will cover in detail elsewhere. For now, know that they are:

- `prop`
- `expose`
- `mount`

And that you may import them from the Fusion namespace:

```php
use function \Fusion\prop;

$podcast = prop();
```

You do not *have* to import them, as they are available in your Vue files by default. They are worth importing though,
if only for the sake of autocomplete.

### Class-based PHP

Class-based PHP will feel much more familiar to traditional PHP developers. To use class based PHP, you must define an
anonymous class:

```php
new class 
{
    //
}
```

You may extend `Fusion\FusionPage` if you wish.

```php
new class extends \Fusion\FusionPage
{
    //
}
```

You may `return` the class, if that feels more logical to you:

```php
return new class
{
    //
}
```

### Code highlighting in PHP blocks

If you're using PhpStorm, you may configure a "Language Injection" to alert the editor that the language inside the
`<php>` block is PHP.

In your preferences, look for Editor > Language Injections. In the top left you'll see a plus sign (+).

<img alt="PhpStorm 01" src="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/readme/php-storm-01.png" style="max-width: 50%; height: auto;">

From that menu, choose "XML Tag Injection".

<img alt="PhpStorm 02" src="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/readme/php-storm-02.png" style="max-width: 30%; height: auto;">

Then enter the following details.

<img alt="PhpStorm 03" src="https://raw.githubusercontent.com/fusion-php/fusion/refs/heads/main/art/readme/php-storm-03.png" style="max-width: 50%; height: auto;">

This should make your editing experience much nicer. We'll work with the JetBrains team to make this step unnecessary.

As of now, we have no instructions on VSCode. Please stay tuned, we'll figure it out as quickly as we can.

## State

One of Fusion's primary responsibilities is to send your state to the frontend. State is sent at runtime, not compiled
into your JavaScript bundle. The only thing that gets written into your bundle are the names of your properties and
exposed actions.

### State with Procedural PHP

When using procedural PHP, you can expose a variable to the frontend by using the `Fusion\prop` function.

```php
$name = prop();
```

> [!TIP]
> This is very non-traditional PHP, and only possible because we're transpiling your PHP code before it gets written to
> disk. All transpiled PHP is written into your storage directory and you're free to inspect it!

By assigning a variable of `$name` to the function `prop`, you've alerted Fusion that `$name` is something that should
be shared with the frontend.

#### Defaults

You are free to pass in a default value as either a scalar or a Closure.

```php
$name = prop('Aaron');
// Or
$name = prop(fn() => 'Aaron');
```

The _default_ is "Aaron", but if a different value is sent from the frontend, the `$name` variable will be assigned to
that value. If the frontend sends `name: "Steve"`, then in your PHP `$name = "Steve"` instead of `"Aaron"`.

We'll cover syncing state further down.

#### Tracking

Something that may feel familiar to a user of a JavaScript framework is that we'll keep track of that variable
throughout your code. This will feel strange to most PHP developers:

```php
$name = prop('Aaron');

$name = strtoupper($name);

// "AARON" gets sent to the frontend.
```

We're only able to do this because we are transpiling your code. At the end of the code that you write, we call a Fusion
method named `syncProps` passing in `get_defined_vars()`.
This gives us the names and values of all of the variables that are in scope. If you've declared a variable as a prop,
we will take the last value and use that as the state for the frontend.

#### Readonly state

To declare a piece of state "readonly", meaning that you never want to receive it back from the frontend, you can append
`->readonly()` to the `prop` function.

```php
$name = prop(Auth::user()->name)->readonly();
```

When `name` gets sent to the frontend, it can still be modified *on the frontend,* if you allow that. But the frontend
will never send it to the backend, the backend will *always* recalculate it.

You can think of this type of state as computed props.

```php
$podcasts = prop(fn() => Podcast::all())->readonly();
```

#### Syncing values to the querystring

It may be convenient to sync state to the querystring to create stable URLs. You may do so by appending
`->syncQueryString`.

```php
$search = prop()->syncQueryString();
```

By default `$search` is null, but if there is a `?search=` in the URL, that value will be used. If you want the
querystring name to be different than the variable name, you may use the `as: ` argument.

```php
$search = prop()->syncQueryString(as: 's');
```

The querystring will now use `?s=` to track this prop.

### State with Class-based PHP

Using class-based PHP, any properties that are `public` will be sent to the frontend. (WIth one caveat, mentioned
below.)

Declaring a class like this:

```php
new class 
{
    public string $name = "Aaron";
}
```

will send `name` to your frontend to be consumed.

If you need to hide a `public` property, you may annotate it with `#[Fusion\Attributes\ServerOnly]`. You should rarely
need this. If you find yourself needing this often, please open an issue and explain why! We might be able to make it
less cumbersome.

#### Readonly state

You may annotate a public property with `#[Fusion\Attributes\IsReadOnly]` to mark a property as readonly, i.e., it will
never be set from a frontend request.

Alternatively, you may set the value via the `mount` method to accomplish the same outcome.

Consider the previous class:

```php
new class 
{
    public string $name = "Aaron";
}
```

The default for `$name` is `Aaron`, but if a value comes in from the frontend where `name: "Steve"` then the `$name`
variable will be set to `Steve`.

However, if you annotate it with the attribute, it will stay "Aaron".

```php
new class 
{
    #[\Fusion\Attributes\IsReadOnly]
    public string $name = "Aaron";
}
```

Beyond annotating with the `#[IsReadOnly]` attribute, you can just brute-force the value in `mount`:

```php
new class 
{
    public string $name;
    
    public function mount() 
    {
        // Doesn't matter what the frontend sends,
        // we'll just overwrite it.
        $this->name = 'Aaron';
    }
}
```

Now, regardless of what was sent from the frontend, we set the value to `"Aaron"`. You could accomplish a "default" by
using the null coalescing operator:

```php
new class 
{
    public ?string $name = null;
    
    public function mount() 
    {
        // Set it to Aaron, only if it's not set.
        $this->name ??= 'Aaron';
    }
}
```

This allows you to potentially sync the `name` state from the frontend, but otherwise assign a default.

#### Syncing QueryString

If you'd like to sync a property to the querystring, you may use the `#[SyncQueryString]` attribute.

```php
new class 
{
    #[\Fusion\Attributes\SyncQueryString]
    public string $search = '';
}
```

You're free to pass an `as:` argument to control the name of the querystring:

```php
new class 
{
    #[\Fusion\Attributes\SyncQueryString(as: 's')]
    public string $search = '';
}
```

The variable passed to your frontend will be named `search`, but in the querystring it will appear as `s`.

## Actions

On the backend, along with state, you can declare "actions." Actions are a way for the JavaScript frontend to reach over
the network and call functions on your backend. In the class-based approach, any method that you define as a `public`
method will be exposed to the frontend.

```php
new class 
{
    public function hello() 
    {
        // Do something
    }
}
```

We'll talk further down about calling these methods from your JavaScript, but now on the frontend you have a `hello`
function that will route to this method on the backend.

If, for whatever reason, you don't want a public method to be reachable from the frontend, you can add a `ServerOnly`
attribute to it in class-based PHP.

```php
new class 
{
    #[\Fusion\Attributes\ServerOnly]
    public function hello() 
    {
        // This method is not addressable from the frontend.
    }
}
```

In procedural PHP, you may pass the function as a named argument to the `expose` method and that will make it visible to
the frontend.

```php
expose(hello: function() {
    // This is available on the frontend as `hello`.
})
```

## Working with JavaScript

You don't _have_ to do anything to receive your state on the frontend, but there is a lot you _can_ do. Because we have
a
plugin in the Vite toolchain, we're able to add some Vue code to inject the state and actions for you automatically,
provided you don't do it yourself.

The automatic process is this simple:

```vue

<php>
  new class {
  public string $name = 'Aaron';
  }
</php>

<template>
  Hello {{ name }}
</template>
```

That's it! You do not have to define props, a `script` tag of any sort, or anything else.

> [!NOTE]
> We are still working on making your editor aware of these injected pieces of data. More on that soon.

If you'd like to define your own script tags, you have as many options as Vue supports.

### A `<script>` tag, without Fusion

You are welcome to define any of the script tags that Vue supports, without referencing Fusion at all. You may use
`<script>`, `<script setup>`, or `<script>` with `setup()`. Fusion will inject itself at build time into all of these
formats.

For example, a `script setup` tag:

```vue

<php>
  new class {
  public string $name = 'Aaron';
  }
</php>

<script setup>
  // Define extra state here? Do something else?
</script>

<template>
  Hello {{ name }}
</template>
```

Or a traditional script tag, with or without a `setup` method:

```vue

<php>
  new class {
  public string $name = 'Aaron';
  }
</php>

<template>
  Hello {{ name }}
</template>

<script>
  export default {
    setup() {
      // extra setup?
    },
    data() {
      // extra state?
    }
  }
</script>
```

All of your Fusion state will still be present in your template.

### A `<script setup>` with manual Fusion imports

Alternatively, you may import some or all of the Fusion state yourself by using the `useFusion` function. Every Vue page
component gets a corresponding `.js` file from which you can import `useFusion`.

```vue

<php>
  new class {
  public string $name = 'Aaron';

  public string $email = 'aaron@example.com';
  }
</php>

<script setup>
  // Assuming this Component is at Pages/Hello.vue, you can 
  // import the shim with the help of the $fusion alias.
  import {useFusion} from "$fusion/Pages/Hello.js";

  // Import `name` only.
  const {name} = useFusion(['name']);
</script>

<template>
  Hello {{ name }}, your email is {{ email }}
</template>
```

In this example, the developer has decided that they want to import the `name` state, and perhaps do something extra
with it on the frontend. Because Fusion is aware that the `email` key exists and was not handled by the developer, we'll
go ahead and inject just that piece of data.

When you import a piece of state or an action, Fusion gives control of that piece of data over to you.

You can import all of it in one go by not passing anything to the `useFusion` function.

```js
<script setup>
  import {useFusion} from "$fusion/Pages/Hello.js";

  // Import everything Fusion has to offer.
  const data = useFusion();
</script>
```

If you do this, you'll need to be sure you're exposing your data to the template, if you so desire.

### A `<script>` tag and `setup()` function

If you prefer the options API, you may import the Fusion state in the `setup` function.

```js
import {useFusion} from "$fusion/Pages/Hello.js";

export default {
  setup() {
    const {name} = useFusion(['name']);

    name.value = 'Steve';

    return {name}
  }
}
```

In this example, we're importing only the `name` property, leaving any other state to get injected into the template by
Fusion. In this example, we (for whatever reason) decided to hardcode the name to `Steve` on the frontend. Notice that
at the end we must return the data we want to be exposed.

Remember: if you import it, Fusion hands control to you.

## Calling actions

Every public method that you define on the backend becomes a function that you can call on the frontend. For example, if
you define a `favorite` method on the backend, in your JavaScript you now have a `favorite` function.

In procedural PHP:

```php
expose(favorite: function() {
    // This is available on the frontend.
})
```

And class-based:

```php
new class {
    public function favorite()
    {
        // This is available.
    }
}
```

You could now call this in your JavaScript by just calling the `favorite` function.

```vue

<template>
  <button @click='favorite'>Favorite</button>
</template>
```

Of course you could import this function and use it in any way that you want.

```vue

<script setup>
  import {useFusion} from "$fusion/Pages/Hello.js";

  const {favorite} = useFusion(['favorite']);
</script>
```

Every Fusion function is actually a proxy object that contains status about the function's state. For example, you may
use `favorite.processing` to see if the request is currently in-flight.

```vue

<template>
  <button @click='favorite'>Favorite</button>
  <div v-if='favorite.processing'>
    Loading...
  </div>
</template>
```

The following properties are available on the function:

```js
const status = reactive({
  processing: false,

  failed: false,
  recentlyFailed: false,

  succeeded: false,
  recentlySucceeded: false,

  finished: false,
  recentlyFinished: false,

  error: null,
  errors: [],
});
```

> [!TIP]
> You can see more about how this works by looking at the packages/vue/ActionFactory.js file.

You'll notice that the status object is a reactive object. This means that you are free to put these properties in your
template and they will work just like other Vue state.

If you want to get the entire status object, you may call `favorite.getStatus()`. This may prove useful if you are
importing the function into one of your script tags and want to use the status as a standalone object.

Here is what the individual properties on the status object represent:

- `processing`: The request is currently in flight
- `failed`: The request failed.
- `recentlyFailed`: This is the same logic as `failed`, but after 3.5 seconds it turns itself back to false. Useful for
  flash messages.
- `succeeded`: The request was successful.
- `recentlySucceeded`: This is the same logic as `succeeded`, but after 3.5 seconds it turns itself back to false.
  Useful for flash messages.
- `finished`: The request finished, regardless of success or failure.
- `recentlyFinished`: This is the same logic as `finished`, but after 3.5 seconds it turns itself back to false. Useful
  for flash messages.
- `error`: If Laravel returns a 422 response, the error bag is present in this property.
- `errors`: If Laravel returns a 422 response, the error message is present in this property.

> [!IMPORTANT]  
> Do you have ideas for other state that would be helpful? Open an issue and let me know!

## Syncing state

Fusion *does not* automatically sync state back to the server. So if you add a `v-model` to a piece of state that came
from
the server, we do not make any requests as the value changes.

Fusion *does* sync state whenever you call an action.

Anytime you call an action that has been provided by Fusion, we will gather up the current values of all the
state that Fusion has defined, and send that to the backend. All of your state will be instantiated on the
backend before your function is run. Then in your function, you're able to reference the state as you normally would in
PHP.

When a request is made to Fusion, here is the order of operations:

- Fusion finds the right PHP class, based on the component.
- Fusion initializes the page's state, if the frontend has sent anything.
- Fusion initializes state from the querystring, if there is any.
- Fusion mounts the page, running the `mount` function if present, or auto-binding properties from the route if
  applicable.
- Fusion runs either the page handler, or the method you called from the frontend.
- Fusion returns the response.

When the frontend receives the response, here is the order of operations:

- Fusion pulls the state out of the response.
- Fusion updates the state in your component.
- The response is modified such that only the response from the action remains.
- If the response is a 422, the `errors` and `error` properties are updated.
- Other reactive state on the function is updated.

## Magic Fusion actions

In addition to defining your own actions by exposing public methods on the backend, there is also the concept of a
"Fusion provided function."

Currently, Fusion provides one function and it is called `sync`. It is available as a part of the `fusion`
object that you may import from `useFusion` or you may just use in your template. All of the Fusion provided 
functions have the same internal state behavior as your user defined functions.

```vue
<template>
  <button @click='fusion.sync'>Sync!</button>
  <div v-if='fusion.sync.processing'>
    Loading...
  </div>
</template>
```

To import it, you may use the `useFusion` function.
```vue
<script setup>
  import {useFusion} from "$fusion/Pages/Hello.js";

  // Import `name` only.
  const {fusion} = useFusion(['fusion']);
</script>
```

All that `sync` does is gather up the state from the
frontend, send it to the backend, mounts the page, and then sends the state back out to the
frontend to be applied.

You can imagine this might be useful if you had a simple search component that looks like this:

```vue

<php>
  use function \Fusion\prop;
  use \App\Models\Podcast;

  $search = prop('');
  $podcasts = prop(function() use ($search) {
  if ($search) {
  return Podcast::search($search)->get();
  }
  return Podcast::all();
  })->readonly();
</php>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <input v-model='search' placeholder="Search podcasts" />
    <button @click='fusion.sync'>
      <LoadingIcon v-if="fusion.sync.inProgress" />
      <span v-else>Search</span>
    </button>

    <Podcast v-for="podcast in podcasts" v-bind="podcast" />
  </div>
</template>

<script>
  import Podcast from '@/Components/Podcast.vue'
  import LoadingIcon from '@/Components/LoadingIcon.vue'

  export default {
    components: {
      Podcast,
      LoadingIcon,
    }
  }
</script>
```

In this example, we are sending a piece of state down with the name `search`. On the backend, we're using that `search`
state to find podcasts that the user is looking for. When the page first loads, search will be a blank string, and so
we'll send out all of the podcasts. If the user types something into the text box on the front, the _local state_ of
`search` will be updated, but it does not automatically sync to the backend.

When the user presses the button, we call `fusion.sync`, which is that magic function provided by Fusion. We'll send
all the state to the backend. At this point, `search` is now a value, so we use that value to compute the value of
`podcasts`, and we send `podcasts` and search back out to the frontend to be updated in the template.

## How Fusion works

It may be helpful to understand the way that the Fusion lifecycle works, because it may demystify some of the apparent
magic.

When you run `npm run dev` or `npm run build`, your Vite process starts. Fusion adds a Vite plugin to the stack that
intercepts your single file component before the Vue plugin begins working on them. In our Vite plugin, we look for the
PHP block, and if we find it, we extract it and hand it over to an Artisan command called `fusion:conform`.

Your PHP is then run through a series of parsers to make sure that it conforms to the Fusion standard. We do all of this
at build time and then write the file to the disk so that we don't have to do this expensive reflection at runtime.
After your PHP has been conformed, we run a second command called `fusion:shim`.

This writes a thin JavaScript file to the disk with some information about the names of your state properties and the
names of your actions. Inside of this shim file there is a `useFusion` function. Every single view component gets a
corresponding `useFusion` function. You do not have to import or even ever interact with the `useFusion` function, as we
will inject it for you into your Vue component. If you want to have more control over importing your state and your
methods, you are free to use the `useFusion` function yourself and handle that state in your `script` or `script setup`
tags. You can find more details about that in the JavaScript section of this documentation.

Once the PHP class has been written to the disk, Fusion operates primarily in the standard Laravel request/response
lifecycle. There is a single `FusionController` that will take the inbound requests and look up the correct class to
route them to. The request is routed to that class that was extracted from your Vue template. If it is a page level
request, i.e. you are loading the page for the first time, then an Inertia response is returned with the name of the
component that should be shown on the page. If it is an action request, the response is just returned for the frontend
to handle.

## Contributing

Please help contribute to Fusion! We need a lot of help, especially on the React side, and perhaps JavaScript/TypeScript
generally. To start contributing, you can clone this repository. We have everything you need in this single repo.

### Structure

The structure of this repo is as follows:

- `/`: at the root of the repo is the Laravel package. You will find most of the source in `src` and some tests in
  `tests`. We need more tests!
- `/packages/*`: in the `packages` directory, you'll find framework-specific JavaScript packages. These are shipped
  _with_ the Laravel package, and are _not_ published to NPM. This makes it easy to keep everything in sync.
- `/apps/*`: in the `apps` directory you'll find full-on Laravel applications, with their own `composer.json`s and
  `package.json`s. There is a readme in the `apps` directory that explains more about how those apps are set up.
- `.github/workflows`: in the `workflows` directory you'll find the CI workflows that test the PHP library, the JS
  packages, and the sample apps.

### Laravel tests

You may run the Laravel tests with the following command:

```shell
./vendor/bin/phpunit
```

### Vue package tests

You may run the Vue package tests with the following commands:

```shell
cd packages/vue
npm run test
```

### Vue app tests

You may run all of the Playwright (browser) tests with the following commands:

```shell
cd apps/vue
npm run test
```

If you want to run the tests while you are working, you may run `npm run test:watch`. This will start `npm run dev` and
also open Playwright in "UI mode" where you can interact with your tests.

#### The `<script test>` block

In the `apps/vue` project, you can write a single file component that contains a `<script test>` block and that will be
extracted to a playwright test.

```vue

<php>
  new class {
  public string $name = 'Aaron';
  }
</php>

<template>
  Hello {{ name }}
</template>

<script test>
  import {test} from '@pw/playwright.extension.js'

  /**
   * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
   */
  test('sees data', async ({fusion}) => {
    await fusion.visit();
    await fusion.see('Aaron');
  });
</script>
```

We have provided a few Playwright helpers in the form of the `FusionPage.js`. Feel free to add any helpers to that
class.
It is also a proxy to the Playwright `page` class.

`fusion.visit()` will visit the component that you are currently working on in the browser. You do not have to use
`fusion.visit()`, especially if you're working with route model binding components that have different URLs.

`fusion.see` is a shorthand to assert that this text is in the body element.


## Credits

- All Fusion artwork was designed by [Will King](https://x.com/wking__)
- Fusion was named by [Josh Cirre](https://x.com/joshcirre)! It had a much worse working name, which is so bad I won't share it.