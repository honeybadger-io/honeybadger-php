#!/usr/bin/env ruby
# ^ syntax detection

require 'rack'
require 'rack-legacy'
require 'rack-rewrite'
require 'pathname'

PROJECT_ROOT = Pathname.new(File.expand_path('../examples', __FILE__))

##
# Magic monkey patch sick 'em!
# Credit to https://gist.github.com/brookr/2396356
module Rack
  module Legacy
    class Php
      def run(env, path)
        config = {'cgi.force_redirect' => 0}
        config.merge! HtAccess.merge_all(path, public_dir) if @htaccess_enabled
        config = config.collect {|(key, value)| "#{key}=#{value}"}
        config.collect! {|kv| ['-d', kv]}

        script, info = *path_parts(path)
        env['SCRIPT_FILENAME'] = script
        env['SCRIPT_NAME'] = strip_public script
        env['PATH_INFO'] = info
        env['REQUEST_URI'] = strip_public path
        env['REQUEST_URI'] = env['ORIG_PATH_INFO'] unless env['ORIG_PATH_INFO'].nil?

        super env, @php_exe, *config.flatten
      end
    end
  end
end

use Rack::Rewrite do
  rewrite %r{^/slim(.*)$}, lambda { |match, rack_env|
    rack_env['ORIG_PATH_INFO'] = rack_env['PATH_INFO']

    if !File.exists?(PROJECT_ROOT.join('slim', rack_env['PATH_INFO']))
      return "/slim/index.php#{match[0]}"
    end

    rack_env['PATH_INFO']
  }
end

use Rack::Legacy::Php, 'examples'
run Rack::File.new 'examples'
