<?php

use MathParser\RationalMathParser;
use MathParser\Interpreting\Interpreter;
use MathParser\Interpreting\ASCIIPrinter;
use MathParser\Interpreting\Differentiator;
use MathParser\Parsing\Nodes\Node;
use MathParser\Parsing\Nodes\FunctionNode;
use MathParser\Parsing\Nodes\VariableNode;
use MathParser\Parsing\Nodes\ExpressionNode;
use MathParser\Parsing\Nodes\ConstantNode;

use MathParser\Parsing\Nodes\IntegerNode;
use MathParser\Parsing\Nodes\RationalNode;
use MathParser\Parsing\Nodes\NumberNode;

use MathParser\Exceptions\UnknownFunctionException;
use MathParser\Exceptions\UnknownOperatorException;
use MathParser\Exceptions\UnknownConstantException;
use MathParser\Exceptions\DivisionByZeroException;

class ASCIIPrinterTest extends PHPUnit_Framework_TestCase
{
    private $parser;
    private $printer;

    public function setUp()
    {
        $this->parser = new RationalMathParser();
        $this->printer = new ASCIIPrinter();
    }

    private function assertResult($input, $output)
    {
        $node = $this->parser->parse($input);
        $result = $node->accept($this->printer);

        $this->assertEquals($result, $output);
    }

    public function testCanPrintVariable()
    {
        $this->assertResult('x', 'x');
    }

    public function testCanPrintNumber()
    {
        $this->assertResult('4', '4');
        $this->assertResult('-2', '-2');
        $this->assertResult('1.5', '1.5');
        $this->assertResult('2/3', '2/3');
        $this->assertResult('4/6', '2/3');
        $this->assertResult('-1/2', '(-1/2)');
        $this->assertResult('4/2', '2');
        $this->assertResult('1/2+1/2', '1');
        $this->assertResult('1/(-2)+1/2', '0');
    }

    public function testCanPrintUnaryMinus()
    {
        $this->assertResult('-x', '-x');
        $this->assertResult('1+(-x)', '1+(-x)');
        $this->assertResult('1+(-2)', '-1');
    }

    public function testCanPrintAddition()
    {
        $this->assertResult('x+1', 'x+1');
        $this->assertResult('x+y', 'x+y');
    }

    public function testCanPrintDivision()
    {
        $this->assertResult('x/y', 'x/y');
        $this->assertResult('x/(y+z)', 'x/(y+z)');
        $this->assertResult('(x+y)/(y+z)', '(x+y)/(y+z)');
        $this->assertResult('(x+sin(x))/2', '(x+sin(x))/2');
    }

    public function testCanPrintMultiplication()
    {
        $this->assertResult('sin(x)*x', 'sin(x)*x');
        $this->assertResult('2(x+4)', '2*(x+4)');
        $this->assertResult('(x+1)(x+2)', '(x+1)*(x+2)');
    }

    public function testCanPrintExponentiation()
    {
        $this->assertResult('x^2', 'x^2');
        $this->assertResult('x^(2/3)', 'x^(2/3)');
        $this->assertResult('x^(y+z)', 'x^(y+z)');
    }

    public function testCanPrintFunctions()
    {
        $this->assertResult('sin(x)', 'sin(x)');
        $this->assertResult('(2+sin(x))/(1-1/2)', '(2+sin(x))/(1/2)');
        $this->assertResult('cos(x)', 'cos(x)');
        $this->assertResult('tan(x)', 'tan(x)');

        $this->assertResult('exp(x)', 'exp(x)');

        $this->assertResult('log(x)', 'log(x)');
        $this->assertResult('log(2+x)', 'log(2+x)');

        $this->assertResult('sqrt(x)', 'sqrt(x)');
        $this->assertResult('sqrt(x^2)', 'sqrt(x^2)');

        $this->assertResult('asin(x)', 'arcsin(x)');
    }

    public function testCanPrintConstant()
    {
        $this->assertResult('pi', 'pi');
        $this->assertResult('e', 'e');

        $node = new ConstantNode('xcv');
        $this->setExpectedException(UnknownConstantException::class);
        $node->accept($this->printer);
    }
}
