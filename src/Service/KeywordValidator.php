<?php

namespace ProductKeywordBundle\Service;

use ProductKeywordBundle\Exception\InvalidKeywordException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 关键词验证服务
 */
#[Autoconfigure(public: true)]
class KeywordValidator
{
    public function validate(string $keyword): void
    {
        // 长度验证
        $length = mb_strlen($keyword);
        if ($length < 1 || $length > 100) {
            throw InvalidKeywordException::forLength($keyword, 1, 100);
        }

        // 特殊字符过滤
        if (preg_match('/[<>"\']/', $keyword) > 0) {
            throw InvalidKeywordException::forInvalidCharacters($keyword);
        }
    }
}
