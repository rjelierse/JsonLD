<?php

/*
 * (c) Markus Lanthaler <mail@markus-lanthaler.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ML\JsonLD\Test;

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;
use ML\JsonLD\Test\TestManifestIterator;


/**
 * The offical JSON-LD test suite.
 *
 * @author Markus Lanthaler <mail@markus-lanthaler.com>
 */
class JsonLDTestSuiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The base directory from which the test manifests, input, and output
     * files should be read.
     */
    private $basedir;


    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->basedir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
    }

    /**
     * Tests expansion.
     *
     * @param string $name    The test name.
     * @param object $test    The test definition.
     * @param object $options The options to configure the algorithms.
     *
     * @dataProvider expansionProvider
     */
    public function testExpansion($name, $test, $options)
    {
        $expected = json_decode(file_get_contents($this->basedir . $test->{'expect'}));
        $result = JsonLD::expand($this->basedir . $test->{'input'},
                                 $options);

        $this->assertJsonEquals($expected, $result);
    }

    /**
     * Provides expansion test cases.
     */
    public function expansionProvider()
    {
        return new TestManifestIterator($this->basedir . 'expand-manifest.jsonld');
    }

    /**
     * Tests compaction.
     *
     * @param string $name    The test name.
     * @param object $test    The test definition.
     * @param object $options The options to configure the algorithms.
     *
     * @dataProvider compactionProvider
     */
    public function testCompaction($name, $test, $options)
    {
        $expected = json_decode(file_get_contents($this->basedir . $test->{'expect'}));
        $result = JsonLD::compact($this->basedir . $test->{'input'},
                                  $this->basedir . $test->{'context'},
                                  $options);

        $this->assertJsonEquals($expected, $result);
    }


    /**
     * Provides compaction test cases.
     */
    public function compactionProvider()
    {
        return new TestManifestIterator($this->basedir . 'compact-manifest.jsonld');
    }

    /**
     * Tests framing.
     *
     * @param string $name    The test name.
     * @param object $test    The test definition.
     * @param object $options The options to configure the algorithms.
     *
     * @dataProvider framingProvider
     */
    public function testFraming($name, $test, $options)
    {
        if (in_array($test->{'input'}, array('frame-0005-in.jsonld', 'frame-0009-in.jsonld', 'frame-0010-in.jsonld',
                                             'frame-0012-in.jsonld', 'frame-0013-in.jsonld')))
        {
            $this->markTestSkipped('This implementation uses deep value matching and aggressive "re-embedding". See ISSUE-110 and ISSUE-119');
        }

        $expected = json_decode(file_get_contents($this->basedir . $test->{'expect'}));
        $result = JsonLD::frame($this->basedir . $test->{'input'},
                                $this->basedir . $test->{'frame'},
                                $options);

        $this->assertJsonEquals($expected, $result);
    }


    /**
     * Provides framing test cases.
     */
    public function framingProvider()
    {
        return new TestManifestIterator($this->basedir . 'frame-manifest.jsonld');
    }

    /**
     * Tests conversion to RDF quads.
     *
     * @param string $name    The test name.
     * @param object $test    The test definition.
     * @param object $options The options to configure the algorithms.
     *
     * @dataProvider toRdfProvider
     */
    public function testToRdf($name, $test, $options)
    {
        $expected = file_get_contents($this->basedir . $test->{'expect'});
        $quads = JsonLD::toRdf($this->basedir . $test->{'input'},
                               $options);

        $serializer = new NQuads();
        $result = $serializer->serialize($quads);

        $this->assertEquals($expected, $result);
    }


    /**
     * Provides conversion to RDF quads test cases.
     */
    public function toRdfProvider()
    {
        return new TestManifestIterator($this->basedir . 'toRdf-manifest.jsonld');
    }


    /**
     * Tests conversion from quads.
     *
     * @param string $name    The test name.
     * @param object $test    The test definition.
     * @param object $options The options to configure the algorithms.
     *
     * @dataProvider fromRdfProvider
     */
    public function testFromRdf($name, $test, $options)
    {
        $expected = json_decode(file_get_contents($this->basedir . $test->{'expect'}));

        $parser = new NQuads();
        $quads = $parser->parse(file_get_contents($this->basedir . $test->{'input'}));

        $result = JsonLD::fromRdf($quads, $options);

        $this->assertEquals($expected, $result);
    }


    /**
     * Provides conversion to quads test cases.
     */
    public function fromRdfProvider()
    {
        return new TestManifestIterator($this->basedir . 'fromRdf-manifest.jsonld');
    }


    /**
     * Asserts that two JSON structures are equal.
     *
     * @param  object|array $expected
     * @param  object|array $actual
     * @param  string $message
     */
    public static function assertJsonEquals($expected, $actual, $message = '')
    {
        $expected = self::normalizeJson($expected);
        $actual = self::normalizeJson($actual);

        self::assertEquals($expected, $actual, $message);
    }


    /**
     * Brings the keys of objects to a deterministic order to enable
     * comparison of JSON structures
     *
     * @param mixed $element The element to normalize.
     *
     * @return mixed The same data with all object keys ordered in a
     *               deterministic way.
     */
    private static function normalizeJson($element)
    {
        if (is_array($element))
        {
            foreach ($element as &$item)
            {
                $item = self::normalizeJson($item);
            }
        }
        elseif (is_object($element))
        {
            $element = (array) $element;
            ksort($element);
            $element = (object) $element;

            foreach ($element as $key => &$item)
            {
                $item = self::normalizeJson($item);
            }
        }

        return $element;
    }
}
