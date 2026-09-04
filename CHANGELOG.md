# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased](https://github.com/carstingaxion/gatherpress-statistics/compare/0.2.0...HEAD)

## [0.2.0](https://github.com/carstingaxion/gatherpress-statistics/compare/0.1.0...0.2.0) - 2026-09-04

### 🚀 Added

- Update required WP (previous commit fixed #43) ([#47](https://github.com/carstingaxion/gatherpress-statistics/pull/47))
- Restructure docs: focused README, developer docs, in-code hook examples ([#46](https://github.com/carstingaxion/gatherpress-statistics/pull/46))
- Allow to show context aware stats ([#35](https://github.com/carstingaxion/gatherpress-statistics/pull/35))

### Dependency Updates & Maintenance

- Bump immutable from 5.1.4 to 5.1.9 ([#57](https://github.com/carstingaxion/gatherpress-statistics/pull/57))
- Bump the composer group across 1 directory with 2 updates ([#56](https://github.com/carstingaxion/gatherpress-statistics/pull/56))
- Bump svgo from 3.3.2 to 3.3.5 ([#55](https://github.com/carstingaxion/gatherpress-statistics/pull/55))
- Bump @babel/plugin-transform-modules-systemjs from 7.28.5 to 7.29.8 ([#54](https://github.com/carstingaxion/gatherpress-statistics/pull/54))
- Bump nanoid from 3.3.11 to 3.3.18 ([#53](https://github.com/carstingaxion/gatherpress-statistics/pull/53))
- Bump websocket-driver from 0.7.4 to 0.7.5 ([#52](https://github.com/carstingaxion/gatherpress-statistics/pull/52))
- Bump postcss-selector-parser ([#51](https://github.com/carstingaxion/gatherpress-statistics/pull/51))
- Bump fast-uri from 3.1.0 to 3.1.7 ([#49](https://github.com/carstingaxion/gatherpress-statistics/pull/49))
- Bump postcss from 8.5.6 to 8.5.28 ([#50](https://github.com/carstingaxion/gatherpress-statistics/pull/50))
- Bump follow-redirects from 1.15.11 to 1.16.0 ([#34](https://github.com/carstingaxion/gatherpress-statistics/pull/34))
- Update wp deps ([#48](https://github.com/carstingaxion/gatherpress-statistics/pull/48))

## [0.1.0](https://github.com/carstingaxion/gatherpress-statistics/compare/0.1.0...0.1.0) - 2026-08-11

* Initial release
* Support GatherPress post types & with any taxonomies
* Filtering for upcoming or past events
* Smart caching system with automatic invalidation
* Scheduled cache regeneration system
* Four display style variations including animated Confetti style
* Full theme.json integration
* Comprehensive taxonomy filtering
* Attendee count statistics
* Conditional prefix/suffix formatting
* Comprehensive documentation and developer hooks
* Semantic HTML structure with `<figure>`, `<data>`, and `<figcaption>` elements
* Admin page (Dashboard → Statistics Archive) with a manual "generate archive for month" form, filter and sort UI, and a Chart.js-based trend chart
