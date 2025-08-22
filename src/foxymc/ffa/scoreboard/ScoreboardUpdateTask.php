<?php

namespace foxymc\ffa\scoreboard;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;

class ScoreboardUpdateTask extends Task
{
    private Player $player;
    private Scoreboard $scoreboard;

    public function __construct(Player $player, Scoreboard $scoreboard)
    {
        $this->player = $player;
        $this->scoreboard = $scoreboard;
    }

    public function onRun(): void
    {
        if ($this->player->isOnline()) {
            $this->scoreboard->updateScoreboardContent($this->player);
        } else {
            $this->scoreboard->stopScoreboardUpdates($this->player);
        }
    }
}