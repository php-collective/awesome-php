# Contribution Guidelines
Unfortunately, not every library, tool or framework can be considered for inclusion. The aim of Awesome PHP is to be a concise list of noteworthy and interesting software written in modern PHP. Therefore, suggested software should:

1. Be widely recommended
2. Well-known or discussed within the PHP community
3. Be unique in its approach or function
4. Fill a niche gap in the market

Self-promotion is frowned upon, so please consider seriously whether your project meets the criteria before opening a pull request, otherwise it may be closed without being reviewed.

Also, please ensure your pull request adheres to the following guidelines:

* Software that is PHP 8.0+, Composer-installable, PSR compliant, semantically versioned, unit tested, actively maintained, and well documented in English.
* Please search previous suggestions before making a new one, as yours may be a duplicate and will be closed.
* Enter a meaningful pull request description.
* Put a link to each library in your pull request ticket so it's easier to review.
* Please make an individual commit for each suggestion in a separate pull request.
* Use the following format for libraries: \[LIBRARY\]\(LINK\) - DESCRIPTION.
* Prefix duplicate library names with their vendor or namespace followed by a space: Foo\Bar would be Foo Bar.
* New categories, or improvements to the existing categorization, are always welcome.
* Please keep descriptions short, simple and unbiased. No buzzwords or marketing jargon please.
* End all descriptions with a full stop/period.
* Don't repeat the name in the description if possible.
* Check your spelling and grammar.
* Make sure your text editor is set to remove trailing whitespace.
* Your entry has been added alphabetically within the category.

Thank you for your suggestions!

## General tips

- If you want to suggest a framework-specific package, best check first if those are better suited under the specific awesome list for it, e.g. Symfony, Laravel, CakePHP ( https://github.com/sindresorhus/awesome#back-end-development ). This list should mainly be for general PHP agnostic libraries and software.

## Tips for creating PHP packages

* Follow https://github.com/php-pds/skeleton
* Make sure the README or docs contain installation and usage instructions. The more verbose, the better.
* The composer.json contains necessary dependencies including constraints (ideally using [semver](http://semver.org/) and `^` operator).
* Make sure to use (GithubActions) CI and include PHPStan/Psalm as well as code sniffer.
* If your package has dependencies also check [prefer-lowest](https://www.dereuromark.de/2019/01/04/test-composer-dependencies-with-prefer-lowest) to ensure a high quality and working dependencies as outlined in your composer.json file.
