source 'https://rubygems.org'

ruby '2.0.0'

gem 'rake'
gem 'pry'

group :development do
  # PHP support in Pow/Rack
  gem 'rack-legacy'
  gem 'rack-rewrite'
end

group :test do
  # Guard
  gem 'guard'
  gem 'guard-bundler'
  gem 'guard-phpunit'
  gem 'guard-phpcs'

  # File watching
  gem 'rb-fsevent', require: false
  gem 'rb-inotify', require: false

  # Notifications
  gem 'growl'
  gem 'terminal-notifier-guard'
end
