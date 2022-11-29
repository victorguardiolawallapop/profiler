<?php

declare(strict_types=1);

namespace SpiralPackages\Profiler\Driver;

final class XhprofSampleDriver implements DriverInterface
{
    private int $startTime = 0;

    public function __construct()
    {
        if (!\ini_get('xhprof.sampling_interval')) {
            \ini_set('xhprof.sampling_interval', 10000);
        }

        if (!\ini_get('xhprof.sampling_depth')) {
            \ini_set('xhprof.sampling_depth', 200);
        }
    }

    public function start(array $context = []): void
    {
        $this->startTime = \microtime(true);
        \xhprof_sample_enable();
    }

    public function end(): array
    {
        return $this->convertData(\xhprof_sample_disable());
    }

    private function convertData(array $data): array
    {
        $resultData = [];
        $prevTime = $this->startTime;
        foreach ($data as $time => $callStack) {
            $wt = (int)(($time - $prevTime) * 1000000);
            $functions = \explode('==>', $callStack);
            $prevIteration = 0;
            $mainKey = $functions[$prevIteration];
            if (!isset($resultData[$mainKey])) {
                $resultData[$mainKey] = [
                    'ct' => 0,
                    'wt' => 0,
                ];
            }
            $resultData[$mainKey]['ct']++;
            $resultData[$mainKey]['wt'] += $wt;

            $funcCnt = \count($functions);
            for ($i = 1; $i < $funcCnt; $i++) {
                $key = $functions[$prevIteration] . '==>' . $functions[$i];

                if (!isset($resultData[$key])) {
                    $resultData[$key] = [
                        'ct' => 0,
                        'wt' => 0,
                    ];
                }

                $resultData[$key]['wt'] += $wt;
                $resultData[$key]['ct']++;

                $prevIteration = $i;
            }

            $prevTime = $time;
        }

        return $resultData;
    }
}
