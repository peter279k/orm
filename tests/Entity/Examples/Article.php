<?php

namespace ORM\Test\Entity\Examples;

use ORM\Entity;

class Article extends Entity
{
    protected static $relations = [
        'categories' => [Category::class, ['id' => 'article_id'], 'articles', 'article_category'],
    ];

    public function getIntro()
    {
        return implode('', array_slice(
            preg_split(
                '/([\s,\.;\?\!]+)/',
                $this->text,
                61,
                PREG_SPLIT_DELIM_CAPTURE
            ),
            0,
            59
        ));
    }
}
