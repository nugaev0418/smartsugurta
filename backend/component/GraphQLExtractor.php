<?php

namespace backend\component;

class GraphQLExtractor
{
    public static function extract(
        array    $apiResponse,
        string   $dataPath,
        array    $map,
        callable $postProcess = null
    ): array
    {
        // 1. GraphQL error
        if (!empty($apiResponse['errors'])) {
            return [
                'success' => false,
                'error' => $apiResponse['errors'][0]['message'] ?? 'Unknown error',
            ];
        }

        // 2. Data olish
        $data = self::getByPath($apiResponse, $dataPath);



        if (empty($data)) {
            return [
                'success' => false,
                'error' => 'Maʼlumot topilmadi',
            ];
        }


        // 3. Mapping asosida extract
        $result = ['success' => true];

        foreach ($map as $key => $path) {
            $result[$key] = self::getByPath($data, $path);
        }

        // 4. Qo‘shimcha ishlov (optional)
        if ($postProcess) {
            $result = $postProcess($result, $data);
        }

        return $result;
    }

    private static function getByPath(array $data, string $path)
    {
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if (!isset($data[$segment])) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }
}
