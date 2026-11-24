# Third-Party Notices — HelpDesk

HelpDesk is proprietary software. It uses the following direct dependencies, all
under permissive licences compatible with the project rules (MIT, BSD,
Apache-2.0, ISC; fonts under OFL). No GPL/AGPL/LGPL-only dependency is used.

## PHP (Composer)

Runtime:

| Package | Version | Licence |
| --- | --- | --- |
| laravel/framework | 13.43.0 | MIT |
| laravel/tinker | 3.2.0 | MIT |

Development/testing:

| Package | Version | Licence |
| --- | --- | --- |
| fakerphp/faker | 1.24.1 | MIT |
| laravel/pail | 1.3.0 | MIT |
| laravel/pao | 1.1.6 | MIT |
| laravel/pint | 1.32.3 | MIT |
| mockery/mockery | 1.6.15 | BSD-3-Clause |
| nunomaduro/collision | 8.9.6 | MIT |
| phpunit/phpunit | 12.5.45 | BSD-3-Clause |

Transitive PHP packages (Carbon, Symfony, Guzzle, Flysystem, Doctrine, Monolog,
etc.) are MIT/BSD/Apache-2.0/ISC. `nette/schema` and `nette/utils` are
dual-licensed; this project relies on their **BSD-3-Clause** option. The full
resolved tree and licence metadata are recorded in `composer.lock` and available
with:

```powershell
composer licenses
```

## JavaScript (npm)

| Package | Version | Licence |
| --- | --- | --- |
| alpinejs | 3.17.5 | MIT |
| @alpinejs/csp | 3.17.5 | MIT |
| tailwindcss | 4.3.5 | MIT |
| @tailwindcss/vite | 4.3.5 | MIT |
| laravel-vite-plugin | 3.2.1 | MIT |
| vite | 8.3.8 | MIT |
| @fontsource/almarai | 5.3.0 | OFL-1.1 |
| @fontsource/noto-kufi-arabic | 5.3.0 | OFL-1.1 |

The full resolved tree is recorded in `package-lock.json`; `npm audit` reports no
vulnerabilities.

## Fonts

| Asset | Licence |
| --- | --- |
| Almarai (Arabic + Latin woff2), via `@fontsource/almarai` | SIL Open Font License 1.1 |
| Noto Kufi Arabic (Arabic + Latin woff2), via `@fontsource/noto-kufi-arabic` | SIL Open Font License 1.1 |

The fonts are imported in `resources/js/app.js` and bundled by Vite into
`public/build/assets/`. The complete licence texts are kept at
`public/fonts/OFL-Almarai.txt` and `public/fonts/OFL-NotoKufiArabic.txt`. Fonts
are served from this server; no Google Fonts or other CDN is contacted at runtime.

## Icons and branding

- The HelpDesk logo mark (overlapping speech bubbles), favicon, palette and
  interface are original to this project and owned by Fedaa Mohamed.
- Icons are simple inline SVG shapes written for this project; no third-party icon
  set or logo is bundled.

## No trackers or remote assets

There is no analytics, advertising, tag manager, external font, external
stylesheet or third-party script. The only network request needed to use the app
is to the application server itself.

## Licence texts

Where a dependency's licence requires retaining copyright and permission notices,
the notices are available in the installed `vendor/` / `node_modules/` directories
and in each package's repository. This file records direct dependencies and the
licence families; it is not legal advice.
