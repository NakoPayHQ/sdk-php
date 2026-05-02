<?php

declare(strict_types=1);

namespace NakoPay\Resource;

class Rates extends Resource
{
    /**
     * @param array{base?: string, quotes?: string[]} $params
     */
    public function retrieve(array $params = []): mixed
    {
        $query = [
            'base' => $params['base'] ?? null,
            'quotes' => isset($params['quotes']) ? implode(',', $params['quotes']) : null,
        ];
        return $this->client->request('GET', '/rates-get', null, $query);
    }
}
