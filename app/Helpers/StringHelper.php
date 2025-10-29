<?php
declare(strict_types=1);

namespace App\Helpers;

final class StringHelper
{
    /**
     * Конвертация GPT‑Markdown в HTML для Telegram
     * + экранирование опасных символов
     * + разбивка на части по лимиту 4096 символов
     *
     * @return array<string> массив сообщений (каждый ≤ 4096 символов)
     */
    public static function gptMarkdownToTgHtml(string $string): array
    {
        // Нормализуем переносы строк
        $string = str_replace(["\r\n", "\r"], "\n", $string);

        // --- Блоки кода (```...```)
        $string = preg_replace('/```(.*?)```/s', '<pre>$1</pre>', $string);

        // --- Жирный + код (**`...`**)
        $string = preg_replace('/\*\*`(.*?)`\*\*/s', '<b><code>$1</code></b>', $string);

        // --- Инлайн-код (`...`)
        $string = preg_replace('/`([^`]+)`/s', '<code>$1</code>', $string);

        // --- Жирный (**...**)
        $string = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $string);

        // --- Курсив (*...* или _..._)
        $string = preg_replace('/\*(.*?)\*/s', '<i>$1</i>', $string);
        $string = preg_replace('/_(.*?)_/s', '<i>$1</i>', $string);

        // --- Подчёркивание (__...__)
        $string = preg_replace('/__(.*?)__/s', '<u>$1</u>', $string);

        // --- Зачёркивание (~~...~~ или ~...~)
        $string = preg_replace('/~~(.*?)~~/s', '<s>$1</s>', $string);
        $string = preg_replace('/~(.*?)~/s', '<s>$1</s>', $string);

        // --- Ссылки [text](url)
        $string = preg_replace('/

\[(.*?)\]

\((.*?)\)/s', '<a href="$2">$1</a>', $string);

        // --- Цитаты (> ...)
        $string = preg_replace('/^>\s?(.*)$/m', '<blockquote>$1</blockquote>', $string);

        // --- Списки
        $string = preg_replace('/^- (.*)$/m', '• $1', $string);
        $string = preg_replace('/^\d+\. (.*)$/m', '◦ $1', $string);

        // --- Экранируем угловые скобки
        $string = str_replace(['<', '>'], ['&lt;', '&gt;'], $string);

        // --- Возвращаем разрешённые теги
        $allowed = ['b', 'i', 'u', 's', 'code', 'pre', 'a', 'blockquote'];
        foreach ($allowed as $tag) {
            $string = str_replace(
                ["&lt;{$tag}&gt;", "&lt;/{$tag}&gt;"],
                ["<{$tag}>", "</{$tag}>"],
                $string
            );
        }

        // --- Разбивка на куски ≤ 4096 символов
        $chunks = [];
        $paragraphs = preg_split("/\n{2,}/", $string); // режем по абзацам
        $current = '';

        foreach ($paragraphs as $p) {
            $try = $current.($current ? "\n\n" : '').$p;
            if (mb_strlen($try) <= 4096) {
                $current = $try;
            } else {
                if ($current !== '') {
                    $chunks[] = $current;
                }
                // если абзац сам > 4096, режем по кускам
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
}
