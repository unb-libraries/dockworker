# Developer Guidance

## General
We follow the lead of the [drush](https://www.drush.org/latest/contribute/CONTRIBUTING/) project wherever possible, as it a similar tool using similar frameworks.

## Code Style
### PHP
PHP should be formatted according to the [PSR-12](https://www.php-fig.org/psr/psr-12/) style. Validation is possible via [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer), which includes a PSR-12 inspection.

### Docblock indents
Despite PSR-12 dictating that code is to be indented 4 spaces, indents within PHP docblocks should only contain two spaces.

### List-ish structures and declarations
Grouped elements presented in pseudo-list style assertions:
 
* use statements
* array elements
* constant declarations
* property declarations

Should be presented in a line-sorted alphabetical order.

### Array indents
PSR-12 says nothing about array indents. For our purposes, in a multi-line declaration, array elements should be indented 4 spaces from the array declaration.

### Class Ordering
Class methods should primarily be declared in the following order sets:

* constructor/language level methods
* public methods
* protected methods
* private methods

And declared alphabetically within those sets.

## Annotations
### Annotation Style
Although consolidation/annotated-command now supports PHP attributes to define command functionality, we should continue to use PHP annotation tags. drush continues to use the annotation tag style, and we should follow suit until drush migrates to PHP attributes.  

### Annotation Tag Order
For sanity, PHP docblock annotation tags should be defined in the following order:

```angular2html
   * @param string $tag
   *
   * @option $no-cache
   *
   * @command docker:image:build-push
   * @usage prod
   * @aliases
   *
   * @return \Robo\ResultData
   * @throws \Exception
   *
   * @dockerimage
   * @dockerpush
```

## Code Organization
### Command Argument Validation
Command arguments should be validated with ```validate``` hooks where possible.

### Command Classes
Command classes should extend DockworkerCommands, an abstract class. All classes in the ```Dockworker\Robo\Plugin\Commands``` namespace will be auto-discovered.

Command classes should not extend other command classes, as parent classes containing commands and hooks appears to be a mortal sin with annotated-command applications - leading to recursion while bootstrapping, firing hooks multiple times.

This leaves us with a design wart. Since commands cannot inherit other commands, reusable functionality must come from traits. Although traits SHOULD be written with no knowledge of the command classes, nor should traits assert annotated-command hooks, the aforementioned issue dictates we do so.

### Data storage
3 classes of data storage Traits are available to Dockworker commands:

* ```ApplicationPersistentDataStorageTrait``` Application Level, stored and committed to repository. Used if application data should be shared between all Dockworker users.
* ```ApplicationLocalDataStorageTrait``` Application Level, stored on user disk. Used if application data is for one Dockworker user only.
* ```DockworkerPersistentDataStorageTrait``` Dockworker Level, stored on user disk. Used if application data is for one Dockworker user but all Applications.

All classes support configuration and will also support binary storage.

### IO
Commands wishing user IO should implement the ```DockworkerIOTrait```. It creates an instance of the ```DockworkerIO``` class at ```$this->dockworkerIO``` in a pre-init hook. ```DockworkerIO``` is a ```ConsoleIO```, which in turn is a ```SymfonyStyle```. It therefore has access to all the Robo and Symfony styling methods.

