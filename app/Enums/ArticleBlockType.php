<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleBlockType: string
{
    case Paragraph = 'paragraph';
    case Image = 'image';
    case ListItem = 'list_item';
    case Reference = 'reference';
}
