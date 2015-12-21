<?php namespace MathParser\Lexing;

use MathParser\Lexing\TokenType;
use MathParser\Lexing\TokenPrecedence;
use MathParser\Lexing\TokenAssociativity;

class Token
{
    private $value;
    private $type;
    private $precedence;
    private $associativity;

    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
        $this->precedence = TokenPrecedence::get($type);
        $this->associativity = TokenAssociativity::get($type);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getPrecedence()
    {
        return $this->precedence;
    }

    public function getAssociativity()
    {
        return $this->associativity;
    }

    public function getArity()
    {
        switch($this->type) {
            case TokenType::UnaryMinus:
                return 1;
            case TokenType::PosInt;
            case TokenType::Constant:
            case TokenType::Identifier:
                return 0;
            default:
                return 2;
        }
    }

    public function isOperator()
    {
        if (
            $this->type == TokenType::FunctionName ||
            $this->type == TokenType::PosInt ||
            $this->type == TokenType::Constant ||
            $this->type == TokenType::Identifier
        ) return false;

        return true;
    }

    public function __toString()
    {
        return "Token: [$this->value, $this->type]";
    }

    public static function canFactorsInImplicitMultiplication($token1, $token2)
    {
        $check1 = (
            $token1->type == TokenType::PosInt ||
            $token1->type == TokenType::Constant ||
            $token1->type == TokenType::Identifier ||
            $token1->type == TokenType::FunctionName ||
            $token1->type == TokenType::CloseParenthesis
        );

        if (!$check1) return false;

        $check2 = (
            $token2->type == TokenType::PosInt ||
            $token2->type == TokenType::Constant ||
            $token2->type == TokenType::Identifier ||
            $token2->type == TokenType::FunctionName ||
            $token2->type == TokenType::OpenParenthesis
        );

        if (!$check2) return false;

        if ($token1->type == TokenType::FunctionName && $token2->type == TokenType::OpenParenthesis)
            return false;

        return true;
    }
}
