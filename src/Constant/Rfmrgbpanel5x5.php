<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Constant;

final class Rfmrgbpanel5x5
{
    public const SEQUENCE_TYPE_IMAGE = 0;

    public const SEQUENCE_TYPE_SEQUENCE = 1;

    public const SEQUENCE_START_BYTE = '00';

    public const SEQUENCE_RUN_BYTE = 'FF';

    public const SEQUENCE_MAX_LENGTH = 512;

    public const SEQUENCE_HEADER_LENGTH = 6;

    public const SEQUENCE_PLAY_BYTE = '01';

    public const SEQUENCE_PAUSE_BYTE = '02';

    public const SEQUENCE_STOP_BYTE = '00';

    public const SEQUENCE_BYTE = 'FF';

    public const ADDRESSED_BYTE = '00';

    public const UNADDRESSED_BYTE = '0F';

    public const MESSAGE_BYTE = 'F0';

    public const LED_COUNT = 25;

    public const MAX_LED_COUNT_ADDRESSED_DATA = 16;
}
