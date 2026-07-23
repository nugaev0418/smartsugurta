<?php

namespace backend\component;

/**
 * Canonical 0-10 driver-relation code, shared by both OSAGO providers.
 * Gross's own contract form already numbers relations this way (its
 * <select name="...relativity"> options go 0..10), so that scheme is used
 * as-is for Gross. For EuroAsia, each code maps to the matching UUID from
 * its own GraphQL `relativesV2` catalogue (Ota, Ona, Er, Xotin, O'g'il,
 * Qiz, Katta aka, Kichik aka, Katta opa, Kichik opa, Qarindosh emas).
 */
class RelativeType
{
    public const NOT_RELATED = 0;
    public const FATHER = 1;
    public const MOTHER = 2;
    public const HUSBAND = 3;
    public const WIFE = 4;
    public const SON = 5;
    public const DAUGHTER = 6;
    public const OLDER_BROTHER = 7;
    public const YOUNGER_BROTHER = 8;
    public const OLDER_SISTER = 9;
    public const YOUNGER_SISTER = 10;

    private const EAI_IDS = [
        self::NOT_RELATED => 'ab3391d9-a5df-4b7d-ae85-79479e9ad10b',
        self::FATHER => '903da482-1fd9-4e90-a384-9e4a52b6545c',
        self::MOTHER => 'df286690-0d72-4cce-95e0-f27c30624174',
        self::HUSBAND => '94531b36-f72d-43b6-9e21-a63b251e0858',
        self::WIFE => '07147dd2-1c8f-424a-86e1-f79a38a5465e',
        self::SON => '6f3cb0a3-463c-498f-a0ef-09543a7c36c8',
        self::DAUGHTER => 'ce1ddb40-e938-40cb-8653-9881817ba5a7',
        self::OLDER_BROTHER => '44da9d2a-dee9-49b8-ad49-66f8c51d5cc1',
        self::YOUNGER_BROTHER => '10b0ac96-1004-4c71-99a1-82b2ff10847d',
        self::OLDER_SISTER => 'cee50656-1d4f-4b47-aa78-ffa259bf1776',
        self::YOUNGER_SISTER => '3e29b1ea-e10e-45dd-a73c-2e77c6e62052',
    ];

    public static function eaiId(int $code): string
    {
        return self::EAI_IDS[$code] ?? self::EAI_IDS[self::NOT_RELATED];
    }
}
