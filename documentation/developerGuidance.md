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

Should be presented in a line-sorted alphbetical order.

### Array indents
PSR-12 says nothing about array indents. For our purposes, in a multi-line declaration, array elements should be indented 4 spaces from the array declaration.

### Class Method Order
Class methods should primarily be declared in the following order sets:

* constructor/language level methods
* public methods
* protected methods
* private methods

And declared alphabetically within those sets.

## Annotations
### Annotation Style
Although consolidation/annotated-command now supports PHP attributes to define command functionality, we should use PHP annotation tags. drush continues to use the annotation tag style, and we should follow suit until drush migrates to PHP attributes.  

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

## I/O operations
### Use of $this->io()
Although the 'io()' method is deprecated in Symfony 3+ (dependency injection of the ConsoleIO object is preferred), we should still use it for console IO in Dockworker commands. drush continues to use io() control, and we should follow suit until drush migrates to using dependency injection.

## Code Organization
### Command Classes
#### Limit on Hook Functions
Commands classes should only define one hook per hook-level each. Multiple methods can be called within that hook method, but each method should be defined in a separate class. This allows for easier testing and reuse of the methods.
#### Hook Class Naming
Command class methods declared as hooks should be named in a standard fashion according to the hook type and parent class name:

```
      /**
       * Provides a pre-init hook that assigns core properties and configuration.
       *
       * @hook pre-init
       * @throws \Dockworker\DockworkerException
       */
      public function preInitDockworkerCommands() : void {
        $this->setCommandStartTime();
        $this->setCoreProperties();
        $this->setDockworkerDataDirs();
        $this->setGitRepo();
      }
```

### Traits
Traits should be written with no knowledge of the command classes. Any required command class properties or methods should be passed to the trait methods.

Traits should not assert any annotated-command hooks.
