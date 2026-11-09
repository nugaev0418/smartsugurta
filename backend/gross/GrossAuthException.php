<?php

namespace backend\gross;

/**
 * Gross saytida sessiya yo'qligini bildiradi (masalan, kutilgan sahifa o'rniga login formasi keldi).
 * GrossOsago::call() buni ushlab, qayta login qilib urinishni takrorlaydi.
 */
class GrossAuthException extends \Exception
{
}
