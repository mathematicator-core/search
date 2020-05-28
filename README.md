<h1 align="center">
    Mathematicator Search
</h1>

<p align="center">
    <a href="https://mathematicator.com" target="_blank">
        <img src="https://avatars3.githubusercontent.com/u/44620375?s=100&v=4">
    </a>
</p>

[![Integrity check](https://github.com/mathematicator-core/search/workflows/Integrity%20check/badge.svg)](https://github.com/mathematicator-core/search/actions?query=workflow%3A%22Integrity+check%22)
[![codecov](https://codecov.io/gh/mathematicator-core/search/branch/master/graph/badge.svg)](https://codecov.io/gh/mathematicator-core/search)
[![License: MIT](https://img.shields.io/badge/License-MIT-brightgreen.svg)](./LICENSE)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-enabled%20L8-brightgreen.svg?style=flat)](https://phpstan.org/)

This is official version of Mathematicator/VikiTron math search engine for computing your math problems.

Online demo: http://vikitron.com

> Please help improve this documentation by sending a Pull request.

Developed by [Baraja](https://baraja.cz)

## Contribution

### Tests

All new contributions should have its unit tests in `/tests` directory.

Before you send a PR, please, check all tests pass.

This package uses [Nette Tester](https://tester.nette.org/). You can run tests via command:
```bash
composer test
````

Before PR, please run complete code check via command:
```bash
composer cs:install # only first time
composer fix # otherwise pre-commit hook can fail
````
