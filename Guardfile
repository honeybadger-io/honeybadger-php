#!/usr/bin/env ruby
#^syntax detection

guard 'bundler' do
  watch('Gemfile')
end

guard 'phpunit', tests_path: 'tests', cli: '--colors --bootstrap tests/test_helper.php' do
  watch(%r{^.+Test\.php$})
  watch(%r{^lib/(.+)\.php$}) { |m| "tests/#{m[1]}Test.php" }
end

guard 'phpcs', :standard => '../coding-standards/PHP/CodeSniffer/Standards/Kohana' do
  watch(%r{^.+\.php$})
end
