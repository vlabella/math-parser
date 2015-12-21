<?php

use MathParser\Lexing\Token;
use MathParser\Lexing\TokenType;
use MathParser\Lexing\TokenPrecedence;
use MathParser\StdMathParser;

use MathParser\Parsing\Nodes\Node;
use MathParser\Parsing\Nodes\ConstantNode;
use MathParser\Parsing\Nodes\ExpressionNode;
use MathParser\Parsing\Nodes\FunctionNode;
use MathParser\Parsing\Nodes\NumberNode;
use MathParser\Parsing\Nodes\VariableNode;

class StdMathParserTest extends PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new StdMathParser();
    }


    private function compareNodes($node1, $node2)
    {
            if ($node1 === null && $node2 === null) return true;
            if ($node1 === null || $node2 === null) return false;

            if ($node1 instanceof ConstantNode) {
                if (!($node2 instanceof ConstantNode)) return false;
                return $node1->getValue() == $node2->getValue();
            }
            if ($node1 instanceof ExpressionNode) {
                if (!($node2 instanceof ExpressionNode)) return false;
                return $this->compareNodes($node1->getRight(), $node2->getRight()) && $this->compareNodes($node1->getLeft(), $node2->getLeft());
            }
            if ($node1 instanceof FunctionNode) {
                if (!($node2 instanceof FunctionNode)) return false;
                return $this->compareNodes($node1->getOperand(), $node2->getOperand());
            }
            if ($node1 instanceof NumberNode) {
                if (!($node2 instanceof NumberNode)) return false;
                return $node1->getValue() == $node2->getValue();
            }
            if ($node1 instanceof VariableNode) {
                if (!($node2 instanceof VariableNode)) return false;
                return $node1->getName() == $node2->getName();
            }


            throw new \Exception("Unknown node type");
    }

    private function assertNodesEqual($node1, $node2)
    {
        $message = "Node1: ".var_export($node1,true)."\nNode 2: ".var_export($node2, true)."\n";

        $this->assertTrue($this->compareNodes($node1, $node2), $message);
    }

    private function assertNumberNode($node, $value)
    {
        $this->assertInstanceOf('MathParser\Parsing\Nodes\NumberNode', $node);
        $this->assertEquals($value, $node->getValue());
    }

    private function assertVariableNode($node, $value)
    {
        $this->assertInstanceOf('MathParser\Parsing\Nodes\VariableNode', $node);
        $this->assertEquals($value, $node->getName());
    }

    private function assertCompareNodes($text)
    {
        $node1 = $this->parser->parse($text);
        $node2 = $this->parser->parse($text);

        $this->assertNodesEqual($node1, $node2);
    }

    public function testCanCompareNodes()
    {
        $this->assertCompareNodes("3");
        $this->assertCompareNodes("x");
        $this->assertCompareNodes("x+y");
        $this->assertCompareNodes("sin(x)");
        $this->assertCompareNodes("(x)");
        $this->assertCompareNodes("1+2+3");
    }

    public function testCanParseSingleNumberExpression()
    {
        $node = $this->parser->parse("3");

        $shouldBe = new NumberNode(3);

        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testCanParseSingleVariable()
    {
        $node = $this->parser->parse('x');
        $shouldBe = new VariableNode('x');

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse('(x)');
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse('((x))');
        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testCanParseSingleConstant()
    {
        $node = $this->parser->parse('pi');
        $shouldBe = new ConstantNode('pi');

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse('(pi)');
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse('((pi))');
        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testCanParseBinaryExpression()
    {
        $node = $this->parser->parse("3+5");
        $shouldBe = new ExpressionNode(new NumberNode(3), '+', new NumberNode(5));

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("3-5");
        $shouldBe = new ExpressionNode(new NumberNode(3), '-', new NumberNode(5));

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("3*5");
        $shouldBe = new ExpressionNode(new NumberNode(3), '*', new NumberNode(5));

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("3/5");
        $shouldBe = new ExpressionNode(new NumberNode(3), '/', new NumberNode(5));

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("3^5");
        $shouldBe = new ExpressionNode(new NumberNode(3), '^', new NumberNode(5));

        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testCanParseWithCorrectAssociativity()
    {
        $node = $this->parser->parse("1+2+3");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new NumberNode(1), '+', new NumberNode(2)),
            '+',
            new NumberNode(3)
        );
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("1-2-3");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new NumberNode(1), '-', new NumberNode(2)),
            '-',
            new NumberNode(3)
        );
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("1*2*3");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new NumberNode(1), '*', new NumberNode(2)),
            '*',
            new NumberNode(3)
        );
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("1/2/3");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new NumberNode(1), '/', new NumberNode(2)),
            '/',
            new NumberNode(3)
        );
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("1^2^3");
        $shouldBe = new ExpressionNode(
            new NumberNode(1),
            '^',
            new ExpressionNode(new NumberNode(2), '+', new NumberNode(3))
        );
        $this->assertNodesEqual($node, $shouldBe);

    }

    public function testCanParseWithCorrectPrecedence()
    {
        $node = $this->parser->parse("3+5*7");

        $factors = new ExpressionNode(new NumberNode(5), '*', new NumberNode(7));
        $shouldBe = new ExpressionNode(new NumberNode(3), '+', $factors);

        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("3*5+7");
        $factors = new ExpressionNode(new NumberNode(3), '*', new NumberNode(5));
        $shouldBe = new ExpressionNode($factors, '+', new NumberNode(7));

        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testCanParseParentheses()
    {
        $node = $this->parser->parse("(x)");
        $shouldBe = new VariableNode('x');
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("((x))");
        $shouldBe = new VariableNode('x');
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(x+1)");
        $shouldBe = $this->parser->parse("x+1");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(x*1)");
        $shouldBe = $this->parser->parse("x*1");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(x^1)");
        $shouldBe = $this->parser->parse("x^1");
        $this->assertNodesEqual($node, $shouldBe);
    }

    public function testImplicitMultiplication()
    {
        $node = $this->parser->parse("2x");
        $shouldBe = $this->parser->parse("2*x");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("2xy");
        $shouldBe = $this->parser->parse("2*x*y");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("2x^2");
        $shouldBe = $this->parser->parse("2*x^2");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("2x^2y");
        $shouldBe = $this->parser->parse("2*x^2*y");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(-x)2");
        $shouldBe = $this->parser->parse("(-x)*2");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("x^2y^2");
        $shouldBe = $this->parser->parse("x^2*y^2");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(x+1)(x-1)");
        $shouldBe = $this->parser->parse("(x+1)*(x-1)");
        $this->assertNodesEqual($node, $shouldBe);

    }

    public function testCanParseUnaryOperators()
    {
        $node = $this->parser->parse("-x");
        $shouldBe = new ExpressionNode(new VariableNode('x'), '-', null);
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("+x");
        $shouldBe = new VariableNode('x');
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("-x+y");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new VariableNode('x'), '-', null),
            '+',
            new VariableNode('y')
        );
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("-x*y");
        $shouldBe = $this->parser->parse("-(x*y)");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("-x^y");
        $shouldBe = $this->parser->parse("-(x^y)");
        $this->assertNodesEqual($node, $shouldBe);

        $node = $this->parser->parse("(-x)^y");
        $shouldBe = new ExpressionNode(
            new ExpressionNode(new VariableNode('x'), '-', null),
            '^',
            new VariableNode('y')
        );
        $this->assertNodesEqual($node, $shouldBe);

    }
}
