<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper\Ssd1306;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Module\Hc\Model\Ssd1306\Pixel;
use GibsonOS\Module\Hc\Service\Slave\Ssd1306Service;

class PixelMapper
{
    /**
     * @param array<int, array<int, array<int, bool>>> $data
     *
     * @throws GetError
     *
     * @return array<int, array<int, array<int, Pixel>>>
     */
    public function mapFromDataArray(array $data): array
    {
        $newData = [];

        foreach ($data as $page => $columns) {
            if (!isset($newData[$page])) {
                $newData[$page] = [];
            }

            foreach ($columns as $column => $bits) {
                if (!isset($newData[$page][$column])) {
                    $newData[$page][$column] = [];
                }

                foreach ($bits as $bit => $on) {
                    $newData[$page][$column][$bit] = (new Pixel())
                        ->setPage($page)
                        ->setColumn($column)
                        ->setBit($bit)
                        ->setOn($on)
                    ;
                }
            }
        }

        return $newData;
    }

    /**
     * @param array<int, array<int, array<int, Pixel>>> $data
     *
     * @throws GetError
     *
     * @return array<int, array<int, array<int, Pixel>>>
     */
    public function completePixels(array $data): array
    {
        $list = [];

        for ($page = 0; $page <= Ssd1306Service::MAX_PAGE; ++$page) {
            $list[$page] = [];

            for ($column = 0; $column <= Ssd1306Service::MAX_COLUMN; ++$column) {
                $list[$page][$column] = [];

                for ($bit = 0; $bit <= Ssd1306Service::MAX_BIT; ++$bit) {
                    $list[$page][$column][$bit] =
                        $data[$page][$column][$bit] ??
                        (new Pixel())
                            ->setPage($page)
                            ->setColumn($column)
                            ->setBit($bit)
                    ;
                }
            }
        }

        return $list;
    }
}
