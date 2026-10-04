<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum CodeLanguage: string
{
    case Html = 'html';
    case Mjml = 'mjml';
    case Markdown = 'markdown';
    case Xml = 'xml';
    case Yaml = 'yaml';
    case Json = 'json';
    case Php = 'php';
    case Javascript = 'javascript';
    case Typescript = 'typescript';
    case Css = 'css';
    case Scss = 'scss';
    case Sql = 'sql';
    case Shell = 'shell';
    case Text = 'text';
}
