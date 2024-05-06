# Snowball Stemmer for Drupal Core Search and Search API

Stemmer service built with PHP Stemmer, supporting: English, French, German,
Italian, Spanish, Portuguese, Russian, Romanian, Dutch, Swedish, Norwegian,
Danish, Catalan; and any additional languages added to
https://github.com/wamania/php-stemmer#languages.

Includes Search API processor, and core search module integration.

The module uses composer to add the required library, so must be installed using
it. If you're not using it yet check the composer Drupal documentation.

## Alternative

The English only stemmer processor included with Search API English
Porter-Stemmer module for core search Using Search API Solr Multilingual
which includes Solr native configuration for stemming and much more. If you
are using Solr this is probably your best option.

## Installation

For core search just install this module. For Search API you will need to enable
it as a processor on your index.  If you have already indexed your site you will
need to re-index.

## API

Not all language codes in Drupal are exactly the same as those used by the
stemmer. The module provides a `SetLanguageEvent` where the language code can
be altered. There are implementations of this `RegionalLanguageCodeSubscriber`
to reduce localized languages to their base ('pt-br' to 'pt') and to resolve
Norwegian 'nb' 'nn' to 'no'. If you implement an event that you think more
people will need submit an issue for it.

## Testing

To run tests require drupal/search_api module.
