# Moox Rendering Architecture



## Todo

1) See [Fucking complex component](#Fucking-complex-component ) for last questions
2) Implement Frontend class in Entity
3) Implement needed Components
4) Implement Frontend class in Theme
5) Implement Renderer



## **Concept**

Each entity (Page, Product, Post, etc.) defines **how** it can be rendered through a **Frontend class** that returns a **JSON layout**.

This JSON is **uniformly structured** and can be overridden by **four layers** of priority.



------



## **The Four Layers (top priority wins)**



1. **Database (DB)** *(highest priority)*

   

   - Saved by the Block Editor.
   - Editors can make changes – only fields without locked: true can be modified.
   - Stores complete or partial JSON.
   - Use case: per-instance content/layout customization.

   

2. **App Overrides** +++ (component, theme, entity)

   

   - Project- or tenant-specific overrides.
   - Exists outside Theme and Core, so updates are safe.
   - Can modify fields, layouts, and props.
   - Example: Client A wants an additional “Delivery Time” field shown for products.

   

3. **Theme** +++ (theme hierarchy)

   

   - Defines look & feel: Tailwind classes, colors, typography, layout structure.
   - Can define **slots**: named slots or a catchall slot for unknown/additional blocks.
   - Can rearrange content, change grid layouts, etc.

   

4. **Entity (Core)** *(lowest priority)

   

   - Provides the domain default structure: which components, which data fields, in what base structure.
   - Knows all business logic and complex field relationships.
   - Contains no design, only structure & meaning.



------

## Simple Entity

```json
{
  "component": "Heading",
  "tag": "h1",
  "data": "{{ product.title }}",
  "design": "text-3xl font-bold text-gray-900",
  "layout": "col-span-6 text-center",
  "meta": {
    "locked": { "data": false, "design": true, "layout": false },
    "placeholder": "Product title",
    "fallback": "No Product title"
  }
}
```



## Multiple Slots

```json
{
  "component": "Card",
  "tag": "section",
  "data": {
    "header": {
      "component": "Heading",
      "tag": "h2",
      "data": "{{ product.title }}",
      "style": "text-xl font-semibold",
      "layout": "",
      "meta": { "locked": { "data": false, "style": true, "layout": false } }
    },
    "body": [
      {
        "component": "Paragraph",
        "tag": "p",
        "data": "{{ product.description }}",
        "style": "text-gray-600",
        "layout": "",
        "meta": {
          "locked": { "data": false, "style": false, "layout": false },
          "placeholder": "No description"
        }
      }
    ],
    "footer": {
      "component": "PriceTag",
      "tag": "div",
      "data": { "amount": "{{ product.price }}", "currency": "{{ product.currency }}" },
      "style": "text-lg font-bold",
      "layout": "mt-4",
      "meta": { "locked": { "data": true, "style": true, "layout": false } }
    },
    "_catchall": []  // anything not mapped to a named slot goes here
  },
  "style": "rounded-xl bg-white p-6 shadow",
  "layout": "col-span-6",
  "meta": { "locked": { "data": false, "style": false, "layout": false } }
}
```



## Fucking Complex Component

```json
{
  // hierarchy (in terms of locking)
  // when sth is locked in theme, it is locked in the next level
  // but the next level might be able to unlock it again?
  // Example: Component locks, Theme unlocks, App does not exist, Block Editor can move it ...
  // but if App locks it again, Editor cannot move it
  // ???
  
  "component": "Section",
  "tag": "section",
  "data": {
    "content": [
      {
        "component": "Grid",
        "tag": "div",
        "data": {
          "columns": [
            {
              "component": "Column",
              "tag": "div",
              "data": {
                "content": [
                  {
                    "component": "ImageGallery",
                    "tag": "div",
                    "data": { "images": "{{ product.images }}" },
                    "style": "",
                    "layout": "",
                    "meta": { "locked": { "data": true, "style": false, "layout": false } }
                  }
                ]
              },
              "style": "",
              "layout": "col-span-12 lg:col-span-6",
              "meta": { "locked": { "data": false, "style": false, "layout": false } }
            },
            {
              "component": "Column",
              "tag": "div",
              "data": {
                "content": [
                  {
                    "component": "Heading",
                    "tag": "h1",
                    "data": "{{ product.title }}",
                    "style": "text-3xl font-bold",
                    "layout": "",
                    "meta": {
                      "locked": { "data": false, "style": true, "layout": false },
                      "placeholder": "Product title",
                      "fallback": "No Product title"
                    }
                  },
                  {
                    "component": "Paragraph",
                    "tag": "p",
                    "data": "{{ product.description }}",
                    "style": "text-gray-600",
                    "layout": "mt-2",
                    "meta": {
                      "locked": { "data": false, "style": false, "layout": false },
                      "placeholder": "No description"
                    }
                  },
                  {
                    "component": "PriceTag",
                    "tag": "div",
                    "data": { "amount": "{{ product.price }}", "currency": "{{ product.currency }}" },
                    "style": "text-2xl font-semibold",
                    "layout": "mt-4",
                    "meta": { "locked": { "data": true, "style": true, "layout": false } }
                  },
                  
                  // Additional data from custom json or relation
                  // component: Table
                  // data comes from a Module
                  
                  {
                    "component": "AddToCartButton",
                    "tag": "button",
                    "data": { "productId": "{{ product.id }}" },
                    "style": "mt-6 btn btn-primary",
                    "layout": "",
                    "meta": { "locked": { 
                      "data": true, 
                      "design": false, // design instead of style 
                      "layout": false,
                      // is padding and margin style, layout or is there another (boxing)?
                      "move": true // order, nesting?
                    } }
                  }
                ]
              },
              "style": "",
              "layout": "col-span-12 lg:col-span-6",
              "meta": { "locked": { "data": false, "style": false, "layout": false } }
            }
          ]
        },
        "style": "gap-8",
        "layout": "grid grid-cols-12",
        "meta": { "locked": { "data": false, "style": false, "layout": false } }
      },
      {
        "component": "Section",
        "tag": "section",
        "data": {
          "content": [
            {
              "component": "AttributesTable",
              "tag": "div",
              "data": "{{ product.attributes_json }}",
              "style": "",
              "layout": "",
              "meta": { "locked": { "data": true, "style": false, "layout": false } }
            }
          ]
        },
        "style": "mt-12",
        "layout": "",
        "meta": { "locked": { "data": false, "style": false, "layout": false } }
      }
    ]
  },
  "style": "bg-white p-6",
  "layout": "container mx-auto",
  "meta": { "locked": { "data": false, "style": false, "layout": false } }
}
```







## **Folder Structure**



```
moox/
  Product/
    Frontend/
      ProductFrontend.php        <-- Entity Defaults

moox/
  Featherlight/
    Shop/
      ProductFrontend.php        <-- Theme Overrides

app/
  Frontend/
    Shop/
      ProductFrontend.php        <-- App Overrides
      
// Database ...

```



------



## **Example: Entity Default**



```php
namespace Moox\Product\Frontend;

class ProductFrontend
{
  	public function views(): array
    {
      //    single
      //  	list
      //		item_in_cart
      //		item_in_minicart
    }
    public function defaultSingleLayout(): array
    {
        return [
            "components" => [
                [
                    "type" => "Section",
                    "data" => [
                        [
                            "type" => "Heading",
                            "props" => [
                                "text" => "{{ product.title }}",
                                "tag" => "h1",
                                "locked" => true
                            ]
                        ],
                        [
                            "type" => "Paragraph",
                            "props" => [
                                "text" => "{{ product.description }}",
                                "placeholder" => "No description provided"
                            ]
                        ],
                        [
                            "type" => "ImageGallery",
                            "props" => [
                                "images" => "{{ product.images }}"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
```



------



## **Example: Theme Override**



```
namespace Moox\Featherlight\Shop;

class ProductFrontend
{
    public function singleOverride(): array
    {
        return [
            "components" => [
                [
                    "type" => "Section",
                    "props" => [
                        "background" => "bg-brand-50 p-6 shadow-lg",
                        "class" => "rounded-xl"
                    ],
                    "children" => [
                        [
                            "type" => "Grid",
                            "props" => [
                                "columns" => "lg:grid-cols-2",
                                "gap" => "gap-8"
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
```



------



## **Example: App Override**



```
namespace App\Frontend\Shop;

class ProductFrontend
{
    public function singleOverride(): array
    {
        return [
            "components" => [
                [
                    "type" => "Badge",
                    "props" => [
                        "text" => "Limited Edition",
                        "class" => "bg-red-600 text-white px-2 py-1 rounded"
                    ]
                ]
            ]
        ];
    }
}
```



------



## **Database Layer Example**



Saved JSON from Block Editor:

```
{
  "components": [
    {
      "type": "Section",
      "props": { "background": "bg-green-50" },
      "children": [
        { "type": "Heading", "props": { "text": "Custom Title" } }
      ]
    }
  ]
}
```



------



## **Rendering Resolver**



```
$entityClass = "\Moox\Product\Frontend\ProductFrontend";
$themeClass  = "\Moox\Featherlight\Shop\ProductFrontend";
$appClass    = "\App\Frontend\Shop\ProductFrontend";

$entityJson = (new $entityClass())->defaultSingleLayout();
$themeJson  = class_exists($themeClass) ? (new $themeClass())->singleOverride() : [];
$appJson    = class_exists($appClass) ? (new $appClass())->singleOverride() : [];
$dbJson     = $db->getOverride('product', $product->id);

$merged = $resolver->resolve($entityJson, $themeJson, $appJson, $dbJson);
```



------



## **Merge Rules**



- **Locked Props** (locked: true) → never overwritten.
- **Props** → shallow merge (single values overwritten).
- **Children** → deep merge, order from the top-most layer wins.
- **Catchall Slot** → any block without a matching named slot goes here.
- **Placeholders** → controlled by placeholder + usePlaceholderWhenEmpty.



------



## **Advantages**

- Fully **No-Code**: Block Editor can add new fields & layouts without code changes.
- Themes can **radically change** layouts without touching business logic.
- App layer allows **fast project-specific tweaks** without modifying core/theme.
- Consistent JSON → easy to debug and maintain.
- Fallback logic ensures layouts always render, even without overrides.



If you want, I can now give you the **final PHP LayoutResolver**

that automatically loads and merges all four layers in the correct order,

so in your controllers you just do:

```
$layout = Layout::for('product.single', $product->id);
```

