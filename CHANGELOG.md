# Changelog

## [1.5.0] - 2025-11-12
- implemented `--fix` option to automatically add missing image dimensions
- fix works by re-saving posts to trigger TextFormatter with dimension detection
- supports fixing single post, discussion, or all posts

## [1.4.0] - 2025-11-09
- added scheduled automatic checks (daily/weekly/monthly)
- added admin panel for configuring scheduled checks
- added support for multiple email recipients
- added Ukrainian and English localizations

## [1.3.0] - 2025-11-09
- renamed the console command to `image-dimensions:check`
- added `--fast`, `--full`, and `--chunk` options with explicit scope requirements
- updated validator logic and reporting to support the new modes

## [1.2.0] - 2025-11-07
-  reverts to v1.0.0 functionality by removing AVIF support that was added in v1.1.0

## [1.1.0] - 2025-11-06
- added AVIF image format support
- AVIF images now recognized and processed automatically

## [1.0.0] - 2025-11-05
- initial release
