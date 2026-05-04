<?php

namespace App\Services\FACTWM;

use App\Actions\Invoice\ExtractText;
use App\Actions\Invoice\SearchText;
use Exception;

class OCRService
{
    public function validate($params, $file, ?int $renderApi = null)
    {
        $text = ExtractText::extract($file, $renderApi);
        if (empty($text)) {
            throw new Exception("Failed to parsed file", 400);

            return false;
        }

        $searchText = SearchText::perform(strtolower($text), $params);

        return [
            'valid' => $searchText,
            'text' => $text,
        ];
    }
}
