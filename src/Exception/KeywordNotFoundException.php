<?php

namespace ProductKeywordBundle\Exception;

/**
 * 关键词未找到异常
 */
class KeywordNotFoundException extends KeywordException
{
    public function __construct(string $identifier, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('关键词ID %s 不存在', $identifier);
        parent::__construct($message, $code, $previous);
    }

    public static function forId(string $id): self
    {
        return new self($id);
    }

    public static function forKeyword(string $keyword): self
    {
        return new self($keyword);
    }
}
