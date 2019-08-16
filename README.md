# Fractal integration for Twig projects

This module enables you to build a living style guide based on Fractal components using the Twig templating language.
It can either be used as a Drupal module or as a standalone Twig extension.

## Features

1. Consistency: Respect component definitions.

    The default variables of a component are read from its component definition
    file (usually `*.config.yml`).

    The class name of a component (or a variant of it) is not manually passed to
    the template each time it is used.  Instead, every usage of the component
    ensures that that at least the component's class names are output (which
    should follow BEM).

    ```twig
    {% render '@atoms/button.twig' with { name: 'submit' } %}

    {% render '@atoms/button--primary.twig' with { label: 'Save' } %}

    {% render [
      '@pages/section--trythis.twig',
      '@pages/section--alternative.twig',
      '@pages/section.twig',
    ] %}
    
    
    ```

    By design, the class name of a component or variant cannot be removed, it
    may only be amended.

2. Structure: Drupal Attributes.

    Any template context variable with "attributes" in its name is treated as a
    Drupal Attributes object.  Use them intuitively in your Fractal components.

    ```yml
    context:
      attributes:
        class: article--teaser
      title: Dummy article title
      title_attributes:
        class: article__heading
    ```
    ```twig
    <article{{ attributes }}>
      <h1{{ title_attributes }}>{{ title }}</h1>
    </article>
    ```

3. Variance: Modifiers. Multiple modifiers.

    A variant is identified by its modifier name, as specified in the referenced
    component name:

    ```twig
    {% render '@atoms/button--primary.twig' %}
    ```

    This will look up the variant identified by modifier 'primary' in the
    'button' atom:

    ```yml
    context:
      attributes:
        class: button

    variants:
      - modifier: primary
        context:
          label: Save
          attributes:
            class: button--primary
    ```

    Components may appear in multiple variations and states.

    ```twig
    {% render '@atoms/button--primary--disabled.twig' with { label: 'Save' } %}
    ```

## Installation

Add the following in your project `composer.json` file:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/netzstrategen/twig_fractal"
  }
],
"require": {
  "drupal/twig_fractal": "dev-master"
}
```

### Standalone

Drupal Core did not split its components into separate repositories yet, so the files
need to be retrieved manually from the main repository. As soon as a separate
repositories for Drupal Components and Core become available, this workaround will be
replaced with regular dependencies.

1. As the extension relies on some Drupal functionalities we need to grab the necessary
   files and autoload them: 

    ```json
    "scripts": {
      "pre-install-cmd": [
        "php -r \"file_exists('vendor/drupal/core') || mkdir('vendor/drupal/core', 0775, TRUE);\"",
        "curl -so vendor/drupal/core/Attribute.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Core/Template/Attribute.php",
        "curl -so vendor/drupal/core/AttributeArray.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Core/Template/AttributeArray.php",
        "curl -so vendor/drupal/core/AttributeBoolean.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Core/Template/AttributeBoolean.php",
        "curl -so vendor/drupal/core/AttributeString.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Core/Template/AttributeString.php",
        "curl -so vendor/drupal/core/AttributeValueBase.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Core/Template/AttributeValueBase.php",
        "curl -so vendor/drupal/core/Html.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Component/Utility/Html.php",
        "curl -so vendor/drupal/core/MarkupInterface.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Component/Render/MarkupInterface.php",
        "curl -so vendor/drupal/core/PlainTextOutput.php https://raw.githubusercontent.com/drupal/drupal/8.6.x/core/lib/Drupal/Component/Render/PlainTextOutput.php"
      ]
    },
    "autoload": {
      "classmap": [
        "vendor/drupal/core/"
      ]
    }
    ```

2. Register the Twig extension in your project:

    ```
    // Add 'render' tag for pattern library components.
    $twig->addExtension(new \Drupal\twig_fractal\TwigFractal());
    ```

## Recommended packages

* https://github.com/netzstrategen/twig-drupal
