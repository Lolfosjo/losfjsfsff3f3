<?php

namespace foxymc\ffa;

use pocketmine\plugin\PluginBase;
use foxymc\ffa\listener\Eventlistener;
use foxymc\ffa\scoreboard\scoreboard;
use foxymc\ffa\commands\start;

class Main extends PluginBase {

    private $scoreboard;

    public function onEnable(): void {

        $this->scoreboard = new Scoreboard($this);
        $this->getServer()->getPluginManager()->registerEvents(new Eventlistener($this), $this);
        $this->scoreboard = new scoreboard($this);
        $this->getServer()->getPluginManager()->registerEvents($this->scoreboard, $this);

        # Commands nur hier registrieren!

        $this->getServer()->getCommandMap()->register("start", new start());

    }
    public function onDisable(): void {
        if (isset($this->scoreboardManager)) {
            $this->scoreboard->shutdown();
        }
    }

     public function getScoreboardManager(): Scoreboard {
        return $this->scoreboard;
    }
}