<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Constant;

final class Rfmrgbpanel5x5
{
    const SEQUENCE_TYPE_IMAGE = 0;

    const SEQUENCE_TYPE_SEQUENCE = 1;

    const SEQUENCE_START_BYTE = '00';

    const SEQUENCE_RUN_BYTE = 'FF';

    const SEQUENCE_MAX_LENGTH = 512;

    const SEQUENCE_HEADER_LENGTH = 6;

    const SEQUENCE_PLAY_BYTE = '01';

    const SEQUENCE_PAUSE_BYTE = '02';

    const SEQUENCE_STOP_BYTE = '00';

    const SEQUENCE_BYTE = 'FF';

    const ADDRESSED_BYTE = '00';

    const UNADDRESSED_BYTE = '0F';

    const MESSAGE_BYTE = 'F0';

    const LED_COUNT = 25;

    const MAX_LED_COUNT_ADDRESSED_DATA = 16;
}
