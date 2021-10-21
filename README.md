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

```sh
composer config repositories.twig_fractal git https://github.com/netzstrategen/twig_fractal.git

composer require drupal/twig_fractal:dev-master
```

### Standalone

Drupal Core did not split its components into separate repositories yet, so the files
need to be retrieved manually from the main repository. As soon as separate
repositories for Drupal Components and Core become available, you can try to use them
instead.

1. As the extension relies on some Drupal functionalities we need to grab the necessary
   files and autoload them: 

    ```sh
    composer config repositories.drupal-attributes git https://github.com/netzstrategen/drupal-attributes.git
    
    composer require netzstrategen/drupal-attributes:dev-master
    ```

2. Register the Twig extension in your project:

    ```
    // Add 'render' tag for pattern library components.
    $twig->addExtension(new \Drupal\twig_fractal\TwigFractal());
    ```

## Recommended packages

https://github.com/netzstrategen/twig_fractal (this package) adds support for the `{% render %}` tag to the Twig environment in PHP (of your application).

https://github.com/netzstrategen/fractal-twig adds support for the `{% render %}` tag to the Twig environment in JavaScript (of the Fractal UI).
