## Adding a DockWorker Alias in Your Shell
Adding an alias to your shell allows you to execute dockworker commands via ```dockworker``` instead of requiring the full ```./vendor/bin/dockworker```.

### Bash Shell
In ```~/.profile```, add:

```
function dockworker() {
  if [ "`git rev-parse --show-cdup 2> /dev/null`" != "" ]; then
    GIT_ROOT=$(git rev-parse --show-cdup)
  else
    GIT_ROOT="."
  fi

  if [ -f "$GIT_ROOT/vendor/bin/dockworker" ]; then
    $GIT_ROOT/vendor/bin/dockworker "$@"
  else
    echo "You must run this command from within a DockWorker project repository."
    return 1
  fi
}
```

Execute ```source ~/.profile``` to load the profile in the current shell.

### Fish Shell
Create ```~/.config/fish/functions/dockworker.fish```:

```
function dockworker --description "DockWorker Tool Alias"
    if test -n (git rev-parse --show-cdup)
        set --global GIT_ROOT (git rev-parse --show-cdup)
    else
        set --global GIT_ROOT "."
    end

    if test -f $GIT_ROOT/vendor/bin/dockworker
        eval $GIT_ROOT/vendor/bin/dockworker $argv
    else
        echo "You must run this command from within a DockWorker project repository."
    end
end
```
