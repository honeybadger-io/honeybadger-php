<?php

namespace Honeybadger\Backtrace;

/**
 * Tests Honeybadger\Backtrace\Line.
 *
 * @group honeybadger
 */
class LineTest extends \PHPUnit\Framework\TestCase
{

    public function test_attributes_read_only()
    {
        $attributes = ['file', 'number', 'method', 'source',
                       'filtered_file', 'filtered_number', 'filtered_method'];
        $line       = new Line('foo', 'bar', 'baz');

        $thrown = [];

        foreach ($attributes as $attribute) {
            $line->$attribute;

            try {
                $line->$attribute = 'foo';
            } catch (\Exception $ex) {
                $thrown[] = $attribute;
            }
        }

        $this->assertEquals($attributes, $thrown);
    }

    public function test_parse_filters_with_provided_callbacks()
    {
        $scream = function ($line) {
            $line['file'] = strtoupper($line['file']);

            return $line;
        };

        $whisper = function ($line) {
            $line['function'] = strtolower($line['function']);

            return $line;
        };

        $lie = function ($line) {
            $line['line'] *= 3;

            return $line;
        };

        $callbacks = [$scream, $whisper, $lie];

        $data = [
            'file'     => 'whatisthis.php',
            'line'     => 4,
            'function' => 'I_DONT_EVEN',
        ];

        $line     = Line::parse($data, ['filters' => $callbacks]);
        $expected = new Line('WHATISTHIS.PHP', 12, 'i_dont_even');

        $this->assertEquals((string)$line, (string)$expected);
    }

    public function test_parse_returns_null_when_callback_returns_null()
    {
        $callback = function ($line) {
        };
        $line     = Line::parse(
            [], [
            'filters' => [$callback],
        ]
        );

        $this->assertNull($line);
    }

    public function provider_parse_returns_line()
    {
        return [
            [
                new Line('path/to/awesome.php', 14, 'failz'),
                [
                    'file'     => 'path/to/awesome.php',
                    'line'     => 14,
                    'function' => 'failz',
                ],
            ],
            [
                new Line('feeling', 'sorta', 'lucky'),
                [
                    'file'     => 'feeling',
                    'line'     => 'sorta',
                    'function' => 'lucky',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider_parse_returns_line
     */
    public function test_parse_returns_line($expected, $data)
    {
        $line = Line::parse($data);
        $this->assertTrue($line->equals($expected));
    }

    public function provider_string_conversion()
    {
        return [
            [
                "[PROJECT_ROOT]/app/models/user.php:14:in `find'",
                new Line('[PROJECT_ROOT]/app/models/user.php', 14, 'find'),
            ],
            [
                "{PHP internal call}:1:in `baz'",
                new Line('{PHP internal call}', 1, 'baz'),
            ],
            [
                "filtered:28:in `redacted'",
                new Line('once', 'upon', 'a time', 'filtered', 28, 'redacted'),
            ],
        ];
    }

    /**
     * @dataProvider provider_string_conversion
     */
    public function test_string_conversion_returns_ruby_style_backtrace_line($expected, $line)
    {
        $this->assertEquals($expected, (string)$line);
    }

    public function provider_is_application()
    {
        return [
            [
                true,
                new Line('[PROJECT_ROOT]/foo.php', 11, 'bar'),
            ],
            [
                false,
                new Line(' [PROJECT_ROOT]/bar.php', 22, 'baz'),
            ],
            [
                true,
                new Line('[PROJECT_ROOT]', 123, 'something'),
            ],
            [
                false,
                new Line('/var/www/baz.php', 58, 'echo'),
            ],
        ];
    }

    /**
     * @dataProvider provider_is_application
     */
    public function test_is_application($expected, $line)
    {
        $this->assertEquals($expected, $line->isApplication());
    }

    public function test_source()
    {
        $line = new Line(path_to_fixture('MyClass.php'), 1, 'does_amazing_things');
        $this->assertEquals(
            [
                '1' => '<?php',
                '2' => '',
                '3' => 'class MyClass',
                '4' => '{',
            ], $line->source
        );
    }

    public function test_source_replaces_tabs_with_spaces()
    {
        $line = new Line(path_to_fixture('MyClass.php'), 10, 'does_amazing_things');
        $this->assertEquals(
            [
                '7'  => '    {',
                '8'  => '        for ($i = 0; $i < 25; $i++) {',
                '9'  => '            echo "Check out this amazing stuff!\n";',
                '10' => '        }',
                '11' => ''
            ], $line->source
        );
    }

    public function test_source_returns_empty_array_for_non_existent()
    {
        $line = new Line(null, 123, 'something');
        $this->assertEmpty($line->source);
    }

    public function provider_to_array()
    {
        return [
            [
                [
                    'file'   => 'foo',
                    'number' => 'bar',
                    'method' => 'baz'
                ],
                new Line('foo', 'bar', 'baz'),
            ],
            [
                [
                    'file'   => 'this',
                    'number' => 'is',
                    'method' => 'filtered'
                ],
                new Line('foo', 'bar', 'baz', 'this', 'is', 'filtered'),
            ],
            [
                [
                    'file'   => 'this is',
                    'number' => 'partially',
                    'method' => 'filtered'
                ],
                new Line('this is', 'partially', 'baz', null, null, 'filtered'),
            ],
        ];
    }

    /**
     * @dataProvider provider_to_array
     */
    public function test_to_array($expectation, $line)
    {
        $this->assertEquals($expectation, $line->toArray());
    }
}
