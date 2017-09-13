# Fractal integration for Drupal / Twig

This module enables you to build a living style guide based on Fractal within
Drupal.


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


## Recommended packages

* https://github.com/netzstrategen/twig-drupal
