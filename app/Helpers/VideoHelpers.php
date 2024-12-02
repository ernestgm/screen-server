<?php

namespace App\Helpers;

use getID3;

class VideoHelpers
{
    private getID3 $getID3;
    private array $video;

    public function __construct($filename)
    {
        $this->getID3 = new getID3();
        $this->video = $this->getID3->analyze($filename);
    }


    public function getDuration()
    {
        return $this->video['playtime_seconds'] ?? 0;
    }


    public function isDurationValid(): bool
    {
        return $this->getDuration() <= 30;
    }
}
