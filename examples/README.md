# honeybadger-php examples

Within this directory you will find various examples for using Honeybadger in
your applications, both with and without a framework.

## Usage

You can test the files within this directory by copying `config.sample.php` to
`config.php` and updating the API key. Feel free to play with additional options
which you'll find in 
[Honeybadger\Config](https://github.com/honeybadger-io/honeybadger-php/blob/master/lib/Honeybadger/Config.php).

## Pow & rack-legacy

If you're running Pow or already have a Ruby environment set up but would like
to run these examples without setting up a full web server, ensure you have
PHP installed (there's a homebrew repo for OSX users) as well as Ruby.

Run a server for `path/to/honeybadger-php/config.ru` as usual:

    $ rackup
    [2013-04-01 14:31:14] INFO  WEBrick 1.3.1
    [2013-04-01 14:31:14] INFO  ruby 2.0.0 (2013-02-24) [x86_64-darwin12.2.1]
    [2013-04-01 14:31:14] INFO  WEBrick::HTTPServer#start: pid=78507 port=9292

Then browse to `/custom_notify.php` or similar.