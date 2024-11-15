# OEmbed For Laravel
### This is a WIP.

This package allows you to retrieve embed information for URLs from various oembed providers, similar to Wordpress.

## Installation

You can install the package via composer:

```
composer require webwizardsusa/laravel-oembed
```

#### Responsive Iframe CSS

This package makes responsive IFrames possible. To enabled them on your site, you need to add the following CSS to your site:

```css
.oembed-responsive-container {
  position: relative;
  width: 100%;
  height: 0;
  overflow: hidden;
}

.oembed-responsive-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

This package is developed by [WebWizardsUSA](https://webwizardsusa.com/).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
