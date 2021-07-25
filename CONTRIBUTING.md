# Contributing
Contributions are always **welcome** :tada:.

We accept contributions through Pull Requests on [Github](https://github.com/nijens/openapi-bundle).


## Issues
Please [create an issue](https://github.com/nijens/openapi-bundle/issues/new) before submitting a Pull Request. This way we can discuss the new feature or problem and come to the best solution before 'wasting time' coding.


## Pull Requests
Please follow the following guidelines when creating a pull request:

- **[Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html)** - See [Coding standards and naming conventions](#coding-standards-and-naming-conventions) for more information.

- **Consider our release cycle** - We follow [Semantic Versioning 2.0.0](https://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

- **Symfony supported versions** - An effort is made to always support the currently maintained [Symfony versions](https://symfony.com/releases).


## Coding standards and naming conventions
This project follows the [Symfony code standards](https://symfony.com/doc/current/contributing/code/standards.html) with one exception:

- No [Yoda conditions](https://en.wikipedia.org/wiki/Yoda_conditions). We believe in creating unit tests instead.

Code style standards are best fixed with the [PHP Coding Standards Fixer](https://cs.symfony.com/).
Please check your code before creating a commit by running the following command:

```bash
make code-style-fix
```


## Running Tests
Run the entire test suite with the following commands to see if everything works like it should after your changes:

```bash
make switch-symfony version=5.3 test
```

If you'd like to test your changes against an older Symfony version (eg. 4.4), you can do so by using the following command:

```bash
make switch-symfony version=4.4 test
```
