<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

final class StringHelper
{
    public static function gptMarkdownToTgHtml(string $string): array
    {
        // Нормализуем переносы строк
        $string = str_replace(["\r\n", "\r"], "\n", $string);

        // --- Вырезаем блоки кода ```...```
        $codeBlocks = [];
        $string = preg_replace_callback('/```[a-zA-Z0-9]*\n(.*?)```/s', function ($m) use (&$codeBlocks) {
            $placeholder = '[[[CODEBLOCK_'.count($codeBlocks).']]]';
            $codeBlocks[$placeholder] = '<pre><code>'.htmlspecialchars($m[1], ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code></pre>';

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
                if ($current !== '') {
                    $chunks[] = $current;
                }
                while (mb_strlen($p) > 4096) {
                    $chunks[] = mb_substr($p, 0, 4096);
                    $p = mb_substr($p, 4096);
                }
                $current = $p;
            }
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    public static function gptMarkdownToTgMarkdown(string $string): string
    {
        //        $string = preg_replace('/(?<!\*)\*(?!\*)/', '\*', $string); @\w+
        //        $string = preg_replace('/\s+/', ' ', $string);
        //        if (!$onBoarding) {
        preg_match_all('/@\w+/', $string, $accounts);
        foreach ($accounts as $account) {
            $newAccount = str_replace('_', '\\_', $account); // TODO: TG bots
            $string = str_replace($account, $newAccount, $string);
        }

        preg_match_all("/(\b(?:(?:http(s)?|ftp):\/\/|(www\.)))([a-zA-Z0-9+&@#\/\-%?=~_|!:,.;]*[a-zA-Z0-9+&@#\/%=:\-~_|]+)/", $string, $urls);
        foreach ($urls as $url) {
            $newUrl = str_replace('_', '\\_', $url); // TODO: urls
            $string = str_replace($url, $newUrl, $string);
        }
        //        }
        // Placeholders
        $phStrike = 'PLACEHOLDER-STRIKE-PLACEHOLDER';
        $phItalic = 'PLACEHOLDER-ITALIC-PLACEHOLDER';
        $phBold = 'PLACEHOLDER-BOLD-PLACEHOLDER';
        $phUnder = 'PLACEHOLDER-UNDER-PLACEHOLDER';

        $patterns = [
            '/~~(.*?)~~/' => "$phStrike\$1$phStrike",
            //            '/\*\*\*(.*?)\*\*\*/' => "$phBold$phItalic\$1$phItalic$phBold",
            '/\*\*(.*?)\*\*/' => "$phBold\$1$phBold",
            '/\*(.*?)\*/' => "$phItalic \$1 $phItalic",
            '/\_\_(.*?)\_\_/' => "$phUnder\$1$phUnder",
            '/\<u\>(.*?)\<\/u\>/' => "$phUnder\$1$phUnder",
        ];
        foreach ($patterns as $pattern => $replacement) {
            $string = preg_replace($pattern, $replacement, (string) $string);
        }

        $replacements = [
            //            "$phItalic$phUnder" => '__',
            //            "$phUnder$phItalic" => '__',
            $phBold => '*',
            $phItalic => '_',
            $phUnder => '__',
            $phStrike => '~',
        ];
        foreach ($replacements as $search => $replace) {
            $string = str_replace($search, $replace, $string);
        }

        return $string;
    }

    public static function gptMarkdownToTgMarkdown2(string $string): string
    {
        Log::info('Original $string: >> '.$string);
        $string = str_replace('\\n', "\n", $string);
        $string = self::gptMarkdownToTgMarkdown($string);
        //        Log::info('After first markdown $string: >> '.$string);
        $map = [
            // Mandatory to escape always by documentation
            '/',
            '(',
            ')',
            '#',
            '.',
            '!',
            '-',
            '[',
            ']',
            '{',
            '}',
            '+',
            '=',
            // Do not need to escape because used in formatting
            // '_', // Italic
            // '__', // underline
            // '*', // bold
            // '~', // strike
            // '`', // mono
            // '|', // spoiler
        ];

        foreach ($map as $char) {
            $string = str_replace($char, "\\$char", $string);
        }

        /**
         * Escape only if not at the start of the line
         * > – quote
         */
        $string = str_replace('\\\\', '\\', $string);
        //        Log::info("\n\n" . 'Final $string: >> ' . $string . "\n\n");

        return preg_replace('/(?<!^)>/m', '\\>', $string);
    }
}
