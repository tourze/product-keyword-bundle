<?php

namespace ProductKeywordBundle\Exception;

/**
 * 无效关键词异常
 */
class InvalidKeywordException extends KeywordException
{
    public static function forLength(string $keyword, int $min, int $max): self
    {
        $message = sprintf(
            '关键词"%s"长度必须在%d-%d之间，当前长度为%d',
            $keyword,
            $min,
            $max,
            mb_strlen($keyword)
        );

        return new self($message);
    }

    public static function forInvalidCharacters(string $keyword): self
    {
        $message = sprintf('关键词"%s"包含非法字符', $keyword);

        return new self($message);
    }
}
