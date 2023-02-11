<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions\SwimReport;

use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\LinePlot;
use Weph\ObsidianTools\Type\Duration;

final class AverageTimeChartGenerator
{
    /**
     * @psalm-suppress MixedMethodCall, MixedPropertyFetch
     */
    public function generate(SwimItems $items): string
    {
        if ($items->count() === 0) {
            throw new \RuntimeException('Can not generate chart without data');
        }

        $x = [];
        $y = [];
        foreach ($items->items() as $item) {
            $averageTime = $item->averageTime();
            if ($averageTime === null) {
                continue;
            }

            $x[] = strtotime($item->date);
            $y[] = $averageTime->inSeconds();
        }

        $graph = new Graph(800, 600);
        $graph->SetMargin(50, 50, 50, 100);
        $graph->SetScale('dateint');
        $graph->title->Show(false);

        $graph->yaxis->SetLabelFormatCallback(static fn (int $v) => Duration::fromSeconds($v)->asString());

        $graph->xaxis->scale->ticks->Set(24 * 60 * 60 * 7);
        $graph->xaxis->SetLabelAngle(90);

        $p1 = new LinePlot($y, $x);
        $p1->SetColor('red');
        $p1->SetWeight(2);
        $graph->Add($p1);

        $file = tempnam(sys_get_temp_dir(), 'average-time-chart');
        $graph->Stroke($file);
        $content = file_get_contents($file);
        unlink($file);

        return $content;
    }
}
