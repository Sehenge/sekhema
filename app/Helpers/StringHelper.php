<?php
declare(strict_types=1);

namespace App\Helpers;

final class StringHelper
{
    public static function gptMarkdownToTgHtml(string $string): array
    {
        // Нормализуем переносы строк
        $string = str_replace(["\r\n", "\r"], "\n", $string);

        // --- Вырезаем блоки кода ```...```
        $codeBlocks = [];
        $string = preg_replace_callback('/```[a-zA-Z0-9]*\n(.*?)```/s', function ($m) use (&$codeBlocks) {
            $placeholder = '[[[CODEBLOCK_' . count($codeBlocks) . ']]]';
            $codeBlocks[$placeholder] = '<pre><code>' . htmlspecialchars($m[1], ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
            return $placeholder;
        }, $string);

        // --- Жирный + код (**`...`**)
        $string = preg_replace('/\*\*`(.*?)`\*\*/s', '<b><code>$1</code></b>', $string);

        // --- Инлайн-код (`...`)
        $string = preg_replace('/`([^`]+)`/s', '<code>$1</code>', $string);

        // --- Жирный (**...**)
        $string = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $string);

        // --- Подчёркивание (__...__)
        $string = preg_replace('/__(.*?)__/s', '<u>$1</u>', $string);

        // --- Курсив (*...*) и _..._ только если не внутри слова
        $string = preg_replace('/(?<=\s|\A)\*(.+?)\*(?=\s|\z)/s', '<i>$1</i>', $string);
        $string = preg_replace('/(?<=\s|\A)_(.+?)_(?=\s|\z)/s', '<i>$1</i>', $string);

        // --- Зачёркивание (~~...~~)
        $string = preg_replace('/~~(.*?)~~/s', '<s>$1</s>', $string);

        // --- Ссылки [text](url)
        $string = preg_replace('/

\[(.*?)\]

\((.*?)\)/s', '<a href="$2">$1</a>', $string);

        // --- Цитаты (> ...)
        $string = preg_replace('/^>\s?(.*)$/m', '— $1', $string);

        // --- Списки
        $string = preg_replace('/^- (.*)$/m', '• $1', $string);
        $string = preg_replace('/^\d+\. (.*)$/m', '◦ $1', $string);

        // --- Экранируем всё остальное
        $string = htmlspecialchars($string, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // --- Возвращаем разрешённые теги
        $allowed = ['b', 'i', 'u', 's', 'code', 'pre', 'a'];
        foreach ($allowed as $tag) {
            $string = str_replace(
                ["&lt;{$tag}&gt;", "&lt;/{$tag}&gt;"],
                ["<{$tag}>", "</{$tag}>"],
                $string
            );
        }

        // --- Вставляем кодовые блоки обратно
        foreach ($codeBlocks as $ph => $block) {
            $string = str_replace($ph, $block, $string);
        }

        // --- Разбивка на куски ≤ 4096 символов
        $chunks = [];
        $paragraphs = preg_split("/\n{2,}/", $string);
        $current = '';

        foreach ($paragraphs as $p) {
            $try = $current.($current ? "\n\n" : '').$p;
            if (mb_strlen($try) <= 4096) {
                $current = $try;
            } else {
                if ('' !== $current) {
                    $chunks[] = $current;
                }
                while (mb_strlen($p) > 4096) {
                    $chunks[] = mb_substr($p, 0, 4096);
                    $p = mb_substr($p, 4096);
                }
                $current = $p;
            }
        }
        if ('' !== $current) {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
