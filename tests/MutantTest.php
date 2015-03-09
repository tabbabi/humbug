<?php

/**
 *
 * @category   Humbug
 * @package    Humbug
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/humbug/blob/master/LICENSE New BSD License
 * @author     Thibaud Fabre
 */
namespace Humbug\Test;

use Humbug\Exception\NoCoveringTestsException;
use Humbug\Mutant;
use Humbug\Mutation;
use Humbug\TestSuite\Mutant\FileGenerator;
use Humbug\Utility\CoverageData;
use Prophecy\Argument;

class MutantTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return FileGenerator
     */
    private function getFileGenerator()
    {
        $generator = $this->prophesize('Humbug\TestSuite\Mutant\FileGenerator');
        $generator->generateFile(Argument::type('Humbug\Mutation'))
            ->willReturn(__DIR__ . '/_files/mutants/mutant.pĥp');

        return $generator->reveal();
    }

    /**
     * @param array $tests
     * @param array $testMethods
     *
     * @return CoverageData
     */
    public function getCoverageData(array $tests = [], array $testMethods = [])
    {
        $coverageData = $this->prophesize('Humbug\Utility\CoverageData');

        $coverageData->getTestClasses(Argument::any(), Argument::any())
            ->willReturn($tests);
        $coverageData->getTestMethods(Argument::any(), Argument::any())
            ->willReturn($testMethods);

        return $coverageData->reveal();
    }

    /**
     * @param array $testMethods
     *
     * @return CoverageData
     */
    public function getExceptionRaisingCoverageData(array $testMethods = [])
    {
        $coverageData = $this->prophesize('Humbug\Utility\CoverageData');

        $coverageData->getTestClasses(Argument::any(), Argument::any())
            ->willThrow(new NoCoveringTestsException());
        $coverageData->getTestMethods(Argument::any(), Argument::any())
            ->willReturn($testMethods);

        return $coverageData->reveal();
    }

    public function getMutation()
    {
        return new Mutation(
            __DIR__ . '/_files/mutables/Math.php',
            8,
            'Phpunit_MM1_Math',
            'add',
            1,
            '\Humbug\Mutator\Arithmetic\Addition'
        );
    }

    public function testProperties()
    {
        $mutation = $this->getMutation();
        $mutant = new Mutant(
            $mutation,
            $this->getFileGenerator(),
            $this->getCoverageData([ 'dummy' ], [ 'dummyMethod' ]),
            __DIR__ . '/_files/mutables/'
        );

        $this->assertEquals($mutation, $mutant->getMutation());
        $this->assertEquals(__DIR__ . '/_files/mutants/mutant.pĥp', $mutant->getFile());
        $this->assertEquals(['dummy'], $mutant->getTests());
    }

    /**
     * @expectedException \Humbug\Exception\NoCoveringTestsException
     */
    public function testConstructorFailsWithoutCoverage()
    {
        new Mutant(
            $this->getMutation(),
            $this->getFileGenerator(),
            $this->getExceptionRaisingCoverageData(),
            __DIR__ . '/_files/mutables/'
        );
    }

    public function testToArray()
    {
        $mutation = $this->getMutation();
        $mutant = new Mutant(
            $mutation,
            new FileGenerator(__DIR__ . '/_files/mutants/'),
            $this->getCoverageData([ 'dummy' ], [ 'dummyMethod' ]),
            __DIR__ . '/_files/mutables/'
        );

        $diff = $this->prophesize('Humbug\Utility\Diff');
        $diff->difference(Argument::any(), Argument::any())
            ->willReturn('diff');

        $mutant->setDiffGenerator($diff->reveal());

        $expected = [
            'file' => 'Math.php',
            'mutator' => '\Humbug\Mutator\Arithmetic\Addition',
            'class' => 'Phpunit_MM1_Math',
            'method' => 'add',
            'line' => 8,
            'tests' => [ 'dummyMethod' ],
            'diff' => 'diff'
        ];

        $actual = $mutant->toArray();

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
