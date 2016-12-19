<?php

namespace Tmilos\Lexer;

use Tmilos\Lexer\Config\LexerConfig;
use Tmilos\Lexer\Error\UnknownTokenException;

class Lexer
{
    /** @var LexerConfig */
    private $config;

    /** @var string */
    private $input;

    /** @var int */
    private $position;

    /** @var int */
    private $peek;

    /** @var Token[] */
    private $tokens;

    /** @var Token */
    private $lookahead;

    /** @var Token */
    private $token;

    /**
     * @param LexerConfig $config
     */
    public function __construct(LexerConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param LexerConfig $config
     * @param string     $input
     *
     * @return Token[]
     */
    public static function scan(LexerConfig $config, $input)
    {
        $tokens = [];
        $offset = 0;
        $position = 0;
        $matches = null;
        while (strlen($input)) {
            $anyMatch = false;
            foreach ($config->getTokenDefinitions() as $tokenDefinition) {
                if (preg_match($tokenDefinition->getRegex(), $input, $matches)) {
                    $str = $matches[0];
                    $len = strlen($str);
                    if (strlen($tokenDefinition->getName())>0) {
                        $tokens[] = new Token($tokenDefinition->getName(), $str, $offset, $position);
                        $position++;
                    }
                    $input = substr($input, $len);
                    $anyMatch = true;
                    $offset += $len;
                    break;
                }
            }
            if (!$anyMatch) {
                throw new UnknownTokenException(sprintf('At offset %s: %s', $offset, substr($input, 0, 16).'...'));
            }
        }

        return $tokens;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return Token
     */
    public function getLookahead()
    {
        return $this->lookahead;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    public function setInput($input)
    {
        $this->input = $input;
        $this->reset();
        $this->tokens = static::scan($this->config, $input);
    }

    public function reset()
    {
        $this->position = 0;
        $this->peek = 0;
        $this->token = null;
        $this->lookahead = null;
    }

    public function resetPeek()
    {
        $this->peek = 0;
    }

    public function resetPosition($position = 0)
    {
        $this->position = $position;
    }

    /**
     * @param string $tokenName
     *
     * @return bool
     */
    public function isNextToken($tokenName)
    {
        return null !== $this->lookahead && $this->lookahead->getName() === $tokenName;
    }

    /**
     * @param string[] $tokenNames
     *
     * @return bool
     */
    public function isNextTokenAny(array $tokenNames)
    {
        return null !== $this->lookahead && in_array($this->lookahead->getName(), $tokenNames, true);
    }

    /**
     * @return bool
     */
    public function moveNext()
    {
        $this->peek = 0;
        $this->token = $this->lookahead;
        $this->lookahead = (isset($this->tokens[$this->position]))
            ? $this->tokens[$this->position++]
            : null;

        return $this->lookahead !== null;
    }

    public function skipUntil($tokenName)
    {
        while ($this->lookahead !== null && $this->lookahead->getName() !== $tokenName) {
            $this->moveNext();
        }
    }

    /**
     * Moves the lookahead token forward.
     *
     * @return null|Token
     */
    public function peek()
    {
        if (isset($this->tokens[$this->position + $this->peek])) {
            return $this->tokens[$this->position + $this->peek++];
        } else {
            return null;
        }
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return null|Token
     */
    public function glimpse()
    {
        $peek = $this->peek();
        $this->peek = 0;

        return $peek;
    }
}