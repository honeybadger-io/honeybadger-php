#!/usr/bin/env ruby
# ^ syntax detection

require 'rack'
require 'rack-legacy'
require 'rack-rewrite'

use Rack::Legacy::Php, 'examples'
run Rack::File.new 'examples'
