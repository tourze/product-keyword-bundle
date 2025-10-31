<?php

namespace ProductKeywordBundle\Exception;

/**
 * 重复关键词异常
 */
class DuplicateKeywordException extends KeywordException
{
    public function __construct(string $keyword, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('关键词"%s"已存在', $keyword);
        parent::__construct($message, $code, $previous);
    }

    public static function forKeyword(string $keyword): self
    {
        return new self($keyword);
    }
}
